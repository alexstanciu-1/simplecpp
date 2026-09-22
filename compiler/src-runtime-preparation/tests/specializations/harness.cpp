#include <chrono>
#include <cstdint>
#include <cstdio>
#include <cstdlib>
#include <cstring>
#include <new>

extern "C" std::uint64_t probe_run(std::uint64_t, std::uint64_t, std::uint64_t);
extern "C" int probe_check();
extern "C" void probe_bad();

#ifdef COUNT_ALLOCATIONS
// Single-threaded, separately instrumented execution; never use these timings.
static std::uint64_t allocations = 0, releases = 0, allocated_bytes = 0;
void* operator new(std::size_t size) {
    void* result = std::malloc(size ? size : 1);
    if (!result) throw std::bad_alloc();
    ++allocations;
    allocated_bytes += size;
    return result;
}
void operator delete(void* value) noexcept {
    if (value) ++releases;
    std::free(value);
}
void operator delete(void* value, std::size_t) noexcept { operator delete(value); }
#endif

int main(int argc, char** argv) {
    if (argc == 2 && std::strcmp(argv[1], "bad") == 0) { probe_bad(); return 90; }
    if (probe_check() != 0) return 91;
    if (argc != 5) return 92;
    auto n = std::strtoull(argv[1], nullptr, 10);
    auto iterations = std::strtoull(argv[2], nullptr, 10);
    auto rounds = std::strtoull(argv[3], nullptr, 10);
    auto seed = std::strtoull(argv[4], nullptr, 10);
    if (n == 0 || iterations == 0 || rounds == 0) return 93;
#ifdef COUNT_ALLOCATIONS
    allocations = releases = allocated_bytes = 0;
#endif
    std::uint64_t sum = 0;
    auto start = std::chrono::steady_clock::now();
    for (std::uint64_t r = 0; r < rounds; ++r) sum += probe_run(n, iterations, seed + r);
    auto elapsed = std::chrono::duration_cast<std::chrono::nanoseconds>(std::chrono::steady_clock::now() - start).count();
    std::printf("{\"checksum\":%llu,\"nanoseconds\":%lld", (unsigned long long)sum, (long long)elapsed);
#ifdef COUNT_ALLOCATIONS
    std::printf(",\"allocations\":%llu,\"releases\":%llu,\"allocated_bytes\":%llu",
        (unsigned long long)allocations, (unsigned long long)releases, (unsigned long long)allocated_bytes);
#endif
    std::puts("}");
}
