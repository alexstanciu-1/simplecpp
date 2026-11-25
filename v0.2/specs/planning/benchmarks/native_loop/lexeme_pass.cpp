// Linux/x86-64. Run from the directory produced by lexeme_input.php.
#define PCRE2_CODE_UNIT_WIDTH 8
#include <pcre2.h>
#include <algorithm>
#include <atomic>
#include <chrono>
#include <cstdio>
#include <cstdlib>
#include <fstream>
#include <immintrin.h>
#include <iterator>
#include <latch>
#include <sched.h>
#include <string>
#include <thread>
#include <ctime>
#include <vector>

using Clock = std::chrono::steady_clock;
std::string read_file(const std::string& path) {
    std::ifstream in(path, std::ios::binary);
    if (!in) throw std::runtime_error(path);
    return {std::istreambuf_iterator<char>(in), {}};
}
void pin(int cpu) {
    cpu_set_t mask;
    CPU_ZERO(&mask);
    CPU_SET(cpu, &mask);
    if (sched_setaffinity(0, sizeof(mask), &mask)) { std::perror("pin"); std::abort(); }
}
struct File {
    std::string bytes;
    std::vector<size_t> ends, expected;
};
struct alignas(64) Worker {
    std::atomic<int> done{0};
};
struct alignas(64) ScanTime {
    Clock::time_point start, finish;
    long cpu_ns;
};
long thread_cpu_ns() {
    timespec ts;
    if (clock_gettime(CLOCK_THREAD_CPUTIME_ID, &ts)) std::abort();
    return ts.tv_sec * 1000000000L + ts.tv_nsec;
}
int main(int argc, char** argv) {
    // Diagnostic arguments: participant count, first slice (0=A), caller CPU.
    const bool trace = argc == 4;
    if (!trace && argc != 2) return 1;
    const int count = std::stoi(argv[1]);
    if (count < 1 || count > 8) return 1;
    const int first_slice = trace ? std::stoi(argv[2]) : 0;
    const int first_cpu = trace ? std::stoi(argv[3]) : 0;
    if (trace && (count > 2 || first_slice < 0 || first_slice > 1 || first_cpu < 0 || first_cpu > 1)) return 1;
    const int slices = trace ? 2 : count;
    const auto pattern = read_file("pattern");
    int error;
    size_t offset;
    auto* code = pcre2_compile(reinterpret_cast<PCRE2_SPTR>(pattern.data()), pattern.size(),
                               PCRE2_UTF | PCRE2_UCP, &error, &offset, nullptr);
    if (!code || pcre2_jit_compile(code, PCRE2_JIT_COMPLETE)) return 2;
    std::vector<File> files(std::stoi(read_file("count")));
    std::vector<size_t> prefix{0};
    for (size_t i = 0; i < files.size(); ++i) {
        auto& file = files[i];
        file.bytes = read_file(std::to_string(i) + ".src");
        std::ifstream ends(std::to_string(i) + ".ends");
        if (!ends) return 3;
        for (size_t end; ends >> end;) file.expected.push_back(end);
        prefix.push_back(prefix.back() + file.bytes.size());
    }
    std::vector<size_t> cuts;
    for (int i = 0; i <= slices; ++i) {
        cuts.push_back(std::lower_bound(prefix.begin(), prefix.end(), prefix.back()*i/slices) - prefix.begin());
    }
    auto scan = [&](int id, pcre2_match_data* data) {
        for (size_t i = cuts[id]; i < cuts[id+1]; ++i) {
            auto& file = files[i];
            file.ends.clear();
            const auto bytes = reinterpret_cast<PCRE2_SPTR>(file.bytes.data());
            for (size_t pos = 0; pos < file.bytes.size();) {
                // PCRE validates each UTF-8 subject on its first match.
                int rc = pcre2_match(code, bytes, file.bytes.size(), pos, pos ? PCRE2_NO_UTF_CHECK : 0, data, nullptr);
                if (rc < 1) std::abort();
                const auto span = pcre2_get_ovector_pointer(data);
                if (span[0] != pos || span[1] <= pos) std::abort();
                pos = span[1];
                file.ends.push_back(pos);
            }
        }
    };
    std::vector<ScanTime> times(count);
    auto run = [&](int id, pcre2_match_data* data) {
        if (!trace) { scan(id, data); return; }
        auto& time = times[id];
        const long cpu_start = thread_cpu_ns();
        time.start = Clock::now();
        scan(first_slice ^ id, data);
        time.finish = Clock::now();
        time.cpu_ns = thread_cpu_ns() - cpu_start;
    };
    std::vector<Worker> workers(count);
    std::vector<std::thread> threads;
    std::latch ready(count);
    std::atomic<int> epoch{0};
    pin(first_cpu);
    auto* main_data = pcre2_match_data_create_from_pattern(code, nullptr);
    const auto start = Clock::now();
    for (int id = 1; id < count; ++id) {
        threads.emplace_back([&, id] {
            pin(first_cpu ^ id);
            auto* data = pcre2_match_data_create_from_pattern(code, nullptr);
            ready.count_down();
            for (int prev = 0;;) {
                int next;
                while ((next = epoch.load(std::memory_order_acquire)) == prev) _mm_pause();
                if (next < 0) break;
                run(id, data);
                workers[id].done.store(next, std::memory_order_release);
                prev = next;
            }
            pcre2_match_data_free(data);
        });
    }
    ready.arrive_and_wait();
    const double setup = std::chrono::duration<double,std::milli>(Clock::now()-start).count();
    int generation = 0;
    auto pass = [&] {
        ++generation;
        if (count > 1) epoch.store(generation, std::memory_order_release);
        run(0, main_data);
        for (int id = 1; id < count; ++id)
            while (workers[id].done.load(std::memory_order_acquire) != generation) _mm_pause();
    };
    pass();
    auto verify = [&] {
        for (int id = 0; id < count; ++id) {
            const int slice = first_slice ^ id;
            for (size_t i = cuts[slice]; i < cuts[slice+1]; ++i)
                if (files[i].ends != files[i].expected) std::abort();
        }
    };
    verify();
    if (trace) {
        auto us = [](auto interval) { return std::chrono::duration<double, std::micro>(interval).count(); };
        for (int sample = 0; sample < 36; ++sample) {
            const auto begin = Clock::now();
            pass();
            const auto end = Clock::now();
            if (sample < 4) continue;
            for (int id = 0; id < count; ++id) {
                const auto& t = times[id];
                std::printf("%d,%d,%d,%d,%.3f,%.3f,%.3f,%.3f\n", count, sample-4,
                            first_slice ^ id, first_cpu ^ id, us(t.start-begin), us(t.finish-begin),
                            t.cpu_ns/1000., us(end-begin));
            }
        }
        verify();
    } else for (int sample = 0; sample < 7; ++sample) {
        constexpr int passes = 32;
        const auto begin = Clock::now();
        for (int p = 0; p < passes; ++p) pass();
        const double ms = std::chrono::duration<double,std::milli>(Clock::now()-begin).count()/passes;
        verify();
        std::printf("%d,%d,%zu,%zu,%f,%f\n", count, sample, files.size(), prefix.back(), setup, ms);
    }
    epoch.store(-1, std::memory_order_release);
    for (auto& thread : threads) thread.join();
    pcre2_match_data_free(main_data);
    pcre2_code_free(code);
}
