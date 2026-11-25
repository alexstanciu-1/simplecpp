// Drive the emitted application function with a preloaded external corpus.
#include <scpp/string/type.hpp>
#include <cstdio>
#include <cstdlib>
#include <ctime>
#include <fstream>
#include <iterator>
#include <string>


namespace App { void analyze_(scpp::string&); }

#ifdef COUNT_ALLOCATIONS
static bool counting;
static unsigned long allocations;
static unsigned long requested_bytes;
extern "C" void* __real__Znwm(unsigned long);
extern "C" void* __wrap__Znwm(unsigned long size) {
    void* result = __real__Znwm(size);
    if (counting) {
        ++allocations;
        requested_bytes += size;
    }
    return result;
}
#endif

int main(int argc, char** argv) {
    if (argc != 3) return 2;
    int passes = std::atoi(argv[2]);
    std::ifstream stream(argv[1], std::ios::binary);
    if (!stream || passes < 1) return 2;
    std::string bytes{std::istreambuf_iterator<char>(stream), {}};
    scpp::string input(bytes.data(), static_cast<scpp::int64>(bytes.size()));
    if (!std::freopen("/dev/null", "w", stdout)) return 2;
    App::analyze_(input);
    std::fflush(stdout);
    timespec start{}, finish{};
    if (clock_gettime(CLOCK_PROCESS_CPUTIME_ID, &start)) return 2;
#ifdef COUNT_ALLOCATIONS
    counting = true;
#endif
    for (int i = 0; i < passes; ++i) App::analyze_(input);
    std::fflush(stdout);
#ifdef COUNT_ALLOCATIONS
    counting = false;
#else
    constexpr unsigned long allocations = 0, requested_bytes = 0;
#endif
    if (clock_gettime(CLOCK_PROCESS_CPUTIME_ID, &finish)) return 2;
    // Linux VmHWM belongs to this executable. ru_maxrss can include the
    // resident Python runner inherited across fork before the child execs.
    std::ifstream status("/proc/self/status");
    std::string field;
    long peak_kib = 0;
    while (status >> field) {
        if (field == "VmHWM:") {
            status >> peak_kib;
            break;
        }
        std::getline(status, field);
    }
    if (peak_kib <= 0) return 2;
    long long ns = (finish.tv_sec - start.tv_sec) * 1000000000LL + finish.tv_nsec - start.tv_nsec;
    std::fprintf(stderr, "%lld %ld %lu %lu\n", ns, peak_kib, allocations, requested_bytes);
}
