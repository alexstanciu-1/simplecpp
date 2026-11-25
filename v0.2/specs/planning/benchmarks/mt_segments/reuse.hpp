// Benchmark-only executor. Each worker owns one job slot for the duration of a batch.
#pragma once
#include <scpp/mt_segments.hpp>
#include <thread>
#include <cstdlib>
#include <sched.h>
#include <condition_variable>
#include <mutex>
#include <exception>
#include <vector>
#include <stdexcept>

namespace bench {
class pair_executor {
    std::mutex mutex;
    std::condition_variable work, done;
    scpp::mt::job* batch = nullptr;
    unsigned generation = 0, ready = 0, pending = 0;
    bool stopping = false;
    std::vector<std::exception_ptr> errors;
    std::vector<std::thread> threads;

    void worker(unsigned index) {
        if (std::getenv("MT_BENCH_PIN_POOL")) {
            cpu_set_t allowed;
            if (sched_getaffinity(0, sizeof(allowed), &allowed)) std::terminate();
            unsigned ordinal = 0;
            for (int cpu = 0; cpu < CPU_SETSIZE; ++cpu) {
                if (!CPU_ISSET(cpu, &allowed) || ordinal++ != index) continue;
                CPU_ZERO(&allowed);
                CPU_SET(cpu, &allowed);
                if (sched_setaffinity(0, sizeof(allowed), &allowed)) std::terminate();
                break;
            }
        }
        std::unique_lock lock(mutex);
        unsigned seen = 0;
        ++ready;
        done.notify_one();
        for (;;) {
            work.wait(lock, [&] { return stopping || generation != seen; });
            if (stopping) return;
            seen = generation;
            auto& job = batch[index];
            lock.unlock();
            try { job.execute(); }
            catch (...) { errors[index] = std::current_exception(); }
            lock.lock();
            if (--pending == 0) done.notify_one();
        }
    }
    void stop() {
        { std::lock_guard lock(mutex); stopping = true; }
        work.notify_all();
        for (auto& t : threads) if (t.joinable()) t.join();
    }
public:
    explicit pair_executor(unsigned count = 2) : errors(count), threads(count) {
        try {
            for (unsigned i = 0; i < threads.size(); ++i) threads[i] = std::thread(&pair_executor::worker, this, i);
        } catch (...) { stop(); throw; }
        std::unique_lock lock(mutex);
        done.wait(lock, [&] { return ready == threads.size(); });
    }
    ~pair_executor() { stop(); }
    void run(scpp::mt::job* jobs, std::size_t count) {
        if (count != threads.size()) throw std::logic_error("job count differs from executor size");
        std::unique_lock lock(mutex);
        batch = jobs;
        for (auto& error : errors) error = nullptr;
        pending = count;
        ++generation;
        work.notify_all();
        done.wait(lock, [&] { return pending == 0; });
        batch = nullptr;
        for (const auto& error : errors) if (error) std::rethrow_exception(error);
    }
};
inline pair_executor* selected = nullptr; // Read and written only by the coordinator.
}
