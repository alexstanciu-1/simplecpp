#include "operators/memory_usage/memory_usage.hpp"

extern unsigned memory_observation_allocations;
unsigned native_memory_observation_allocations(bool peak)
{
    const auto before = memory_observation_allocations;
    if (peak) (void)scpp::memory_get_peak_usage();
    else (void)scpp::memory_get_usage();
    return memory_observation_allocations - before;
}
