// Standalone comparison of batch, fresh and prestarted thread dispatch.
#include <algorithm>
#include <array>
#include <atomic>
#include <latch>
#include <immintrin.h>
#include <bit>
#include <chrono>
#include <cstdint>
#include <condition_variable>
#include <cstdio>
#include <cstdlib>
#include <ctime>
#include <mutex>
#include <sched.h>
#include <string>
#include <thread>
#include <vector>

using Word = std::uint64_t;
#include "record_read.hpp"
#include "dispatch_profile.hpp"

#ifdef ROTATION_TELEMETRY
#include "rotation_telemetry.hpp"
#endif

#ifdef SMT_LOAD
extern "C" std::uint64_t integer_load(const std::atomic<bool>&);
double cpu_seconds(clockid_t id) {
    timespec ts{};
    if (clock_gettime(id, &ts)) std::abort();
    return ts.tv_sec + ts.tv_nsec * 1e-9;
}
#endif

#ifdef ADAPT_PLACEMENT
#include "adaptive_placement.hpp"
#endif

struct alignas(64) WorkerState {
    std::array<Word, 24> chain{};
    alignas(64) std::atomic<unsigned> finished{0};
};

// Each round doubles the number of peers whose arrivals a worker has observed.
struct PeerBarrier {
    struct alignas(64) Flag { std::atomic<unsigned> step{0}; };
    std::array<std::array<Flag, 3>, 8> inbox;
    void arrive(unsigned id, unsigned count, unsigned step) {
        for (unsigned round = 0, distance = 1; distance < count; ++round, distance *= 2) {
            inbox[(id + distance) % count][round].step.store(step, std::memory_order_release);
            while (inbox[id][round].step.load(std::memory_order_acquire) < step) _mm_pause();
        }
    }
};

void pin(int cpu) {
    cpu_set_t mask;
    CPU_ZERO(&mask);
    CPU_SET(cpu, &mask);
    if (sched_setaffinity(0, sizeof(mask), &mask)) {
        std::perror("sched_setaffinity");
        std::exit(1);
    }
}

int main(int argc, char** argv) {
    if (argc < 3 || argc > 10) return 1;
    const unsigned workers = argc - 2;
    const unsigned passes = std::stoul(argv[1]);
    if (!passes) return 1;
    const bool active = std::getenv("LOOP_ACTIVE") != nullptr;
    const bool peers = active && std::getenv("LOOP_PEER_BARRIER");
    PeerBarrier barrier;
    const bool pooled = std::getenv("LOOP_POOL") != nullptr;
    const unsigned batch = active || pooled || std::getenv("LOOP_PER_PASS") ? 1 : passes;
    std::vector<Record> rows(record_count);
    for (unsigned i = 0; i < record_count; ++i) rows[i].sku = i;
    std::array<WorkerState, 8> state{};
    std::array<int, 8> cpu{};
    for (unsigned w = 0; w < workers; ++w) {
        cpu[w] = std::stoi(argv[2 + w]);
        for (unsigned k = w * 24 / workers; k < (w + 1) * 24 / workers; ++k)
            state[w].chain[k] = k + 1;
    }
#ifdef EIGHT_PASSES
    const char* setting = std::getenv("LOOP_RECORD_STEPS");
    if (!setting || !active || passes != 8) return 1;
    const unsigned steps = std::stoul(setting);
    if (!steps) return 1;
#endif
#ifdef ROTATION_TRACE
    if (!active || peers || workers != 8) return 1;
    const unsigned rotation = std::stoul(std::getenv("LOOP_SLICE_SHIFT"));
    struct Stamp {
        Clock::time_point start, finish;
#ifdef ROTATION_TELEMETRY
        long long cpu_ns;
        long involuntary, voluntary;
#endif
    };
#ifdef ROTATION_TELEMETRY
    std::array<TaskSpan, 8> spans{};
#endif
    std::array<std::vector<Stamp>, 8> stamps;
    std::vector<Clock::time_point> origins((passes + 31) / 32);
    for (auto& trace : stamps) trace.resize(origins.size());
#endif
#ifdef EFFICIENCY_TRACE
    struct SliceStamp { Clock::time_point begin, end; };
    const unsigned measurements = (passes - 1) / 32;
    std::array<std::vector<SliceStamp>, 8> slices;
    for (auto& v : slices) v.resize(measurements);
    std::vector<SliceStamp> full_pass(measurements);
#endif
    auto work = [&](unsigned w) {
#ifdef ROTATION_TRACE
        w = (w + rotation) % workers;
#endif
#ifdef EIGHT_PASSES
        advance_records<true, true, true>(rows.data(), w * 24 / workers, (w + 1) * 24 / workers,
                                          batch, state[w].chain, steps);
#else
        advance_records<true, true>(rows.data(), w * 24 / workers, (w + 1) * 24 / workers,
                                    batch, state[w].chain);
#endif
    };
    DispatchTiming timing;
#ifdef TAIL_TRACE
    std::array<Clock::time_point, 8> finished_at{};
    double outstanding = 0, after_finish = 0;
    unsigned sampled = 0;
#endif
#ifdef ROTATION_TRACE
    auto timed_work = [&](unsigned w, unsigned pass) {
        if (pass % 32 || pass == 0) { work(w); return; }
#ifdef ROTATION_TELEMETRY
        auto before = task_counters();
#endif
        const auto start = Clock::now();
        work(w);
        const auto finish = Clock::now();
#ifdef ROTATION_TELEMETRY
        auto after = task_counters();
        stamps[w][pass / 32] = {start, finish, after.cpu_ns - before.cpu_ns,
                                after.involuntary - before.involuntary, after.voluntary - before.voluntary};
#else
        stamps[w][pass / 32] = {start, finish};
#endif
    };
#endif
#ifdef EFFICIENCY_TRACE
    auto measured_slice = [&](unsigned w, unsigned pass) {
        if (!pass || pass % 32) { work(w); return; }
        const auto begin = Clock::now();
        work(w);
        const auto end = Clock::now();
        slices[w][pass / 32 - 1] = {begin, end};
    };
#endif
    auto background = [&](unsigned w, unsigned pass) {
        if (profile_dispatch && pass % 32 == 0) {
            timing.workers[w].start_delay += micros(Clock::now() - timing.pass_start);
            double cpu_before = thread_cpu_us();
            work(w);
            timing.workers[w].kernel_cpu += thread_cpu_us() - cpu_before;
        } else {
#ifdef ROTATION_TRACE
            timed_work(w, pass);
#elif defined(EFFICIENCY_TRACE)
            measured_slice(w, pass);
#else
            work(w);
#endif
        }
#ifdef TAIL_TRACE
        if (pass % 32 == 0) finished_at[w] = Clock::now();
#endif
    };
    std::vector<std::thread> threads;
    threads.reserve(workers - 1);
    std::mutex mutex;
    std::condition_variable next_pass, completed;
    unsigned generation = 0, ready = 0, pending = 0;
    bool stopping = false;
    std::atomic<unsigned> published{0};
    std::latch active_ready(workers - 1);
#ifdef ADAPT_PLACEMENT
    PlacementDetector detector;
    const bool adapt = std::getenv("LOOP_ADAPT") != nullptr;
    const unsigned onset = std::stoul(std::getenv("LOOP_ONSET"));
    std::atomic<bool> release_load{false};
    Clock::time_point load_started{}, migration_end{};
    double migration_us = 0;
#endif
    if (active) {
        for (unsigned w = 1; w < workers; ++w) {
            threads.emplace_back([&, w] {
                pin(cpu[w]);
                active_ready.count_down();
                for (unsigned step = 1; step <= passes; ++step) {
                    if (!peers || step == 1)
                        while (published.load(std::memory_order_acquire) < step) _mm_pause();
#ifdef ROTATION_TELEMETRY
                    if (step == 1) {
                        spans[w].begin = task_counters();
                        spans[w].start = Clock::now();
                    }
#endif
#ifdef ADAPT_PLACEMENT
                    // The pass release publishes the decision; its completion protects the next write.
                    if (w == 3 && detector.moved_pass == int(step - 1)) {
                        auto before = Clock::now();
                        pin(detector.target);
                        migration_end = Clock::now();
                        if (sched_getcpu() != detector.target) std::abort();
                        migration_us = micros(migration_end - before);
                    }
#endif
                    background(w, step - 1);
#ifdef ROTATION_TELEMETRY
                    if (step == passes) {
                        spans[w].finish = Clock::now();
                        spans[w].end = task_counters();
                    }
#endif
                    if (peers) barrier.arrive(w, workers, step);
                    else state[w].finished.store(step, std::memory_order_release);
                }
            });
        }
        active_ready.wait();
    }
    if (pooled && workers > 1) {
        for (unsigned w = 1; w < workers; ++w) {
            threads.emplace_back([&, w] {
                pin(cpu[w]);
                std::unique_lock lock(mutex);
                if (++ready == workers - 1) completed.notify_one();
                unsigned seen = 0;
                for (;;) {
                    next_pass.wait(lock, [&] { return stopping || generation != seen; });
                    if (stopping) return;
                    seen = generation;
                    lock.unlock();
                    background(w, seen - 1);
                    lock.lock();
                    if (--pending == 0) completed.notify_one();
                }
            });
        }
        std::unique_lock lock(mutex);
        completed.wait(lock, [&] { return ready == workers - 1; });
    }
#ifdef ROTATION_TELEMETRY
    HostCounters host_begin;
    spans[0].begin = task_counters();
    spans[0].start = Clock::now();
#endif
#ifdef SMT_LOAD
    std::atomic<bool> stop_load{false};
    std::latch load_ready(1);
    std::thread load;
    clockid_t load_clock{};
    Word load_result = 0;
    double load_begin = 0;
    if (const char* placement = std::getenv("LOOP_LOAD_CPU")) {
        const int load_cpu = std::stoi(placement);
        load = std::thread([&, load_cpu] {
            pin(load_cpu);
            load_ready.count_down();
#ifdef ADAPT_PLACEMENT
            release_load.wait(false);
            load_started = Clock::now();
#endif
            load_result = integer_load(stop_load);
        });
        load_ready.wait();
        if (pthread_getcpuclockid(load.native_handle(), &load_clock)) std::abort();
        load_begin = cpu_seconds(load_clock);
    }
#endif
    auto start = Clock::now();
    const auto cpu_start = std::clock();
    for (unsigned pass = 0; pass < passes; pass += batch) {
        const bool sample = profile_dispatch && pass % 32 == 0;
#ifdef ROTATION_TRACE
        if (pass && pass % 32 == 0) origins[pass / 32] = Clock::now();
#endif
#ifdef ADAPT_PLACEMENT
        if (pass == onset && load.joinable()) {
            release_load.store(true);
            release_load.notify_one();
        }
        if (adapt && detector.target < 0 && pass % 512 == 0) detector.poll(pass);
#endif
#ifdef EFFICIENCY_TRACE
        if (pass && pass % 32 == 0) full_pass[pass / 32 - 1].begin = Clock::now();
#endif
        if (sample) timing.begin();
        if (active) {
            if (!peers || pass == 0) published.store(pass + 1, std::memory_order_release);
        } else if (pooled && workers > 1) {
            {
                std::lock_guard lock(mutex);
                pending = workers - 1;
                ++generation;
            }
            next_pass.notify_all();
        } else {
            for (unsigned w = 1; w < workers; ++w)
                threads.emplace_back([&, w, pass] { pin(cpu[w]); background(w, pass); });
        }
        if (pass == 0) pin(cpu[0]);
        if (sample) timing.before_caller();
#ifdef ROTATION_TRACE
        timed_work(0, pass);
#elif defined(EFFICIENCY_TRACE)
        measured_slice(0, pass);
#else
        work(0);
#endif
#ifdef TAIL_TRACE
        Clock::time_point wait_begin;
        if (pass % 32 == 0) wait_begin = Clock::now();
#endif
        if (sample) timing.before_wait();
        if (peers) {
            barrier.arrive(0, workers, pass + 1);
        } else if (active) {
            for (unsigned w = 1; w < workers; ++w)
                while (state[w].finished.load(std::memory_order_acquire) < pass + 1) _mm_pause();
        } else if (pooled && workers > 1) {
            std::unique_lock lock(mutex);
            completed.wait(lock, [&] { return pending == 0; });
        } else {
            for (auto& thread : threads) thread.join();
            threads.clear();
        }
#ifdef EFFICIENCY_TRACE
        if (pass && pass % 32 == 0) full_pass[pass / 32 - 1].end = Clock::now();
#endif
        if (sample) timing.end();
#ifdef TAIL_TRACE
        if (pass % 32 == 0) {
            const auto end = Clock::now();
            auto last = wait_begin;
            for (unsigned w = 1; w < workers; ++w) last = std::max(last, finished_at[w]);
            outstanding += micros(last - wait_begin);
            after_finish += micros(end - last);
            ++sampled;
        }
#endif
    }
    Word checksum = 0;
    for (const auto& worker : state)
        for (Word value : worker.chain) checksum += value;
    const double cpu_s = double(std::clock() - cpu_start) / CLOCKS_PER_SEC;
    const double wall = std::chrono::duration<double>(Clock::now() - start).count();
#ifdef SMT_LOAD
    const double load_cpu_s = load.joinable() ? cpu_seconds(load_clock) - load_begin : 0;
    if (load.joinable()) {
        stop_load.store(true, std::memory_order_relaxed);
        load.join();
    }
#endif
#ifdef ROTATION_TELEMETRY
    spans[0].finish = Clock::now();
    spans[0].end = task_counters();
    HostCounters host_end;
#endif
    if (pooled && workers > 1) {
        {
            std::lock_guard lock(mutex);
            stopping = true;
        }
        next_pass.notify_all();
        for (auto& thread : threads) thread.join();
    }
    if (active) for (auto& thread : threads) thread.join();
    for (unsigned i = 0; i < record_count; ++i)
        if (rows[i].sku != Word{i} + passes) return 2;
    std::printf("%.9f,%.9f,%llu", wall, cpu_s, static_cast<unsigned long long>(checksum));
#ifdef SMT_LOAD
    std::printf(",%.9f,%llu", load_cpu_s, static_cast<unsigned long long>(load_result));
#endif
#ifdef ADAPT_PLACEMENT
    auto time_us = [&](Clock::time_point stamp) {
        return stamp == Clock::time_point{} ? -1.0 : micros(stamp-start);
    };
    std::printf(",%u,%d,%d,%.3f,%.3f,%.3f,%.3f,%.3f,%.2f", detector.polls, detector.moved_pass,
                detector.target, detector.polling_us, time_us(load_started), time_us(detector.decision),
                time_us(migration_end), migration_us, detector.observed_busy);
#endif
#ifdef EFFICIENCY_TRACE
    double total = 0, longest = 0, aggregate = 0;
    for (unsigned i = 0; i < measurements; ++i) {
        total += micros(full_pass[i].end - full_pass[i].begin);
        double maximum = 0;
        for (unsigned w = 0; w < workers; ++w) {
            const auto duration = micros(slices[w][i].end - slices[w][i].begin);
            maximum = std::max(maximum, duration);
            aggregate += duration;
        }
        longest += maximum;
    }
    std::printf(",%.5f,%.5f,%.5f", total / measurements, longest / measurements, aggregate / measurements);
#endif
    if (profile_dispatch) timing.print(workers);
#ifdef TAIL_TRACE
    std::printf(",%.3f,%.3f", outstanding / sampled, after_finish / sampled);
#endif
    std::putchar('\n');
#ifdef ROTATION_TRACE
#ifdef ROTATION_TELEMETRY
    for (unsigned w = 0; w < workers; ++w) {
        auto a = host_begin.cores[cpu[w]], b = host_end.cores[cpu[w]];
        const auto& span = spans[w];
        std::printf("%u,%d,%lld,%lld,%lld,%lld,%lld,%lld,%ld,%ld\n", w, cpu[w], b.ref-a.ref,
                    b.delivered-a.delivered, b.sibling_busy-a.sibling_busy, b.sibling_total-a.sibling_total,
                    (long long)std::chrono::duration_cast<std::chrono::nanoseconds>(span.finish-span.start).count(),
                    span.end.cpu_ns-span.begin.cpu_ns, span.end.involuntary-span.begin.involuntary,
                    span.end.voluntary-span.begin.voluntary);
    }
#endif
    for (unsigned j = 1; j < origins.size(); ++j) {
        std::printf("%u", j * 32);
        for (unsigned w = 0; w < workers; ++w) {
            auto stamp = stamps[w][j];
            auto ns = [&](Clock::time_point t) {
                return std::chrono::duration_cast<std::chrono::nanoseconds>(t - origins[j]).count();
            };
            std::printf(",%lld,%lld", (long long)ns(stamp.start), (long long)ns(stamp.finish));
#ifdef ROTATION_TELEMETRY
            std::printf(",%lld,%ld,%ld", stamp.cpu_ns, stamp.involuntary, stamp.voluntary);
#endif
        }
        std::putchar('\n');
    }
#endif
}
