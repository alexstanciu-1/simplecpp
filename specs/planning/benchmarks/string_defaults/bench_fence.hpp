#pragma once
namespace scpp_bench {
// Benchmark instrumentation only; same memory barrier as the direct C++ harness.
inline void fence() { asm volatile("" ::: "memory"); }
}
