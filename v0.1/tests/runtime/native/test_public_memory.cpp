#include "public_api_guard.hpp"
#include "scpp/memory/memory_get_usage.hpp"
#include "scpp/memory/memory_get_usage_real.hpp"
#include "scpp/memory/memory_get_peak_usage.hpp"
#include "scpp/memory/memory_get_peak_usage_real.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <initializer_list>

unsigned memory_observation_allocations = 0;
void *operator new(std::size_t size)
{
    ++memory_observation_allocations;
    if (auto memory = std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
unsigned native_memory_observation_allocations(bool peak);
#define CHECK(value) do { if (!(value)) { std::fprintf(stderr, "memory observation failure %d\n", __LINE__); std::abort(); } } while(false)

int main()
{
    using namespace scpp;
    // These are process observations, not stable allocator counters. Do not assert
    // equal values across calls, exact deltas, or nonzero platform availability.
    for (int i = 0; i < 10; ++i) {
        CHECK(process_memory_usage().native_value() >= 0);
        CHECK(process_memory_usage(boolean(false)).native_value() >= 0);
        CHECK(process_memory_usage(boolean(true)).native_value() >= 0);
        CHECK(process_peak_memory_usage().native_value() >= 0);
        CHECK(process_peak_memory_usage(boolean(false)).native_value() >= 0);
        CHECK(process_peak_memory_usage(boolean(true)).native_value() >= 0);
    }
    for (bool peak : {false, true}) {
        const auto native_count = native_memory_observation_allocations(peak);
        const auto before = memory_observation_allocations;
        if (peak) (void)process_peak_memory_usage();
        else (void)process_memory_usage();
        const auto public_count = memory_observation_allocations - before;
        CHECK(public_count == native_count);
        std::printf("Memory observation allocations (%s): native=%u public=%u\n", peak ? "peak" : "current", native_count, public_count);
    }
    std::puts("PASS: process-memory forms, nonnegative/zero fallback and allocation-neutral boundary");
}
