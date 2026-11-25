// Standalone Linux benchmark. No project headers or runtime.
#include <algorithm>
#include <array>
#include <bit>
#include <chrono>
#include <cstdint>
#include <cstdio>
#include <cstdlib>
#include <ctime>
#include <sched.h>
#include <string>
#include <thread>
#include <vector>

using Clock = std::chrono::steady_clock;
using Word = std::uint64_t;

#ifdef RECORD_SWEEP
#include "record_read.hpp"
#endif

Word compute(unsigned first, unsigned end, unsigned iterations) {
    Word sum = 0;
    for (unsigned chain = first; chain < end; ++chain) {
        Word x = chain + 1;
        for (unsigned i = 0; i < iterations; ++i) {
            x ^= x >> 27;
            x = std::rotl(x * 0x2545f4914f6cdd1dULL, 17);
        }
        sum += x;
    }
    return sum;
}

void pin(int cpu) {
    cpu_set_t mask;
    CPU_ZERO(&mask);
    CPU_SET(cpu, &mask);
    if (sched_setaffinity(0, sizeof(mask), &mask)) {
        std::perror("sched_setaffinity");
        std::exit(1);
    }
}

double thread_seconds() {
    timespec t{};
    if (clock_gettime(CLOCK_THREAD_CPUTIME_ID, &t)) std::abort();
    return t.tv_sec + t.tv_nsec * 1e-9;
}

struct alignas(64) Result {
    Word sum;
    double cpu;
#ifdef TRACE_LOOP
    Clock::time_point begin, end;
#endif
};

int main(int argc, char** argv) {
    if (argc < 3 || argc > 10) return 1;
    unsigned iterations = std::stoul(argv[1]);
    unsigned workers = argc - 2;
    std::array<Result, 8> results{};
    std::vector<int> cpus;
    for (int i = 2; i < argc; ++i) cpus.push_back(std::stoi(argv[i]));
#ifdef RECORD_SWEEP
    std::vector<Record> rows(record_count);
    for (unsigned i = 0; i < record_count; ++i) rows[i].sku = i;
    const bool tiled = std::getenv("LOOP_SERIAL_TILES") != nullptr;
    if (tiled && workers != 1) return 1;
    const bool write_back = std::getenv("LOOP_WRITE_BACK") != nullptr;
    auto kernel = std::getenv("LOOP_INDEX_CONTROL") ? record_passes<false> : record_passes<true>;
    if (write_back) kernel = record_passes<true, true>;
#endif
    auto worker = [&](unsigned n) {
        pin(cpus[n]);
        double start = thread_seconds();
#ifdef TRACE_LOOP
        results[n].begin = Clock::now();
#endif
#ifdef RECORD_SWEEP
        if (tiled) {
            for (unsigned chain = 0; chain < 24; chain += 3)
                results[n].sum += kernel(rows.data(), chain, chain + 3, iterations);
        } else {
            results[n].sum = kernel(rows.data(), n * 24 / workers, (n + 1) * 24 / workers, iterations);
        }
#else
        results[n].sum = compute(n * 24 / workers, (n + 1) * 24 / workers, iterations);
#endif
#ifdef TRACE_LOOP
        results[n].end = Clock::now();
#endif
        results[n].cpu = thread_seconds() - start;
    };

    std::vector<std::thread> threads;
    bool wait_only = std::getenv("LOOP_WAIT_ONLY") != nullptr;
    threads.reserve(wait_only ? workers : workers - 1);
    auto start = Clock::now();
    for (unsigned n = wait_only ? 0 : 1; n < workers; ++n) threads.emplace_back(worker, n);
    if (!wait_only) worker(0);
    for (auto& t : threads) t.join();
    Word sum = 0;
    double cpu = 0;
    for (auto result : results) {
        sum += result.sum;
        cpu += result.cpu;
    }
    double wall = std::chrono::duration<double>(Clock::now() - start).count();
#ifdef RECORD_SWEEP
    for (unsigned i = 0; i < record_count; ++i) {
        if (rows[i].sku != Word{i} + (write_back ? iterations : 0)) {
            std::fprintf(stderr, "Unexpected field at record %u\n", i);
            return 2;
        }
    }
#endif
    std::printf("%.9f,%.9f,%llu", wall, cpu, static_cast<unsigned long long>(sum));
#ifdef TRACE_LOOP
    auto first = results[0].begin, latest = first, finish = results[0].end;
    for (unsigned i = 1; i < workers; ++i) {
        first = std::min(first, results[i].begin);
        latest = std::max(latest, results[i].begin);
        finish = std::max(finish, results[i].end);
    }
    auto seconds = [](Clock::duration d) { return std::chrono::duration<double>(d).count(); };
    std::printf(",%.9f,%.9f,%.9f", seconds(first - start), seconds(latest - first),
                wall - seconds(finish - start));
#endif
    std::putchar('\n');
}
