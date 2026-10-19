#pragma once
#include <cstddef>
#include <cstdio>
#include <cstdlib>
#include <new>

struct Counters {
    long allocations = 0, bytes = 0, frees = 0;
    long constructed = 0, copied = 0, moved = 0, destroyed = 0;
};
extern Counters counts;
extern bool measuring;
extern int live_values;
extern int copies_before_throw;
struct CopyFailure {};

// Overalignment deliberately tests more than pointer-aligned opaque storage.
struct alignas(64) Tracked {
    int value = 0;
    explicit Tracked(int v = 0) : value(v) { ++live_values; if(measuring) ++counts.constructed; }
    Tracked(const Tracked& x) : value(x.value) {
        if(copies_before_throw == 0) throw CopyFailure{};
        if(copies_before_throw > 0) --copies_before_throw;
        ++live_values; if(measuring) ++counts.copied;
    }
    Tracked(Tracked&& x) noexcept : value(x.value) { ++live_values; if(measuring) ++counts.moved; }
    Tracked& operator=(const Tracked& x) { value=x.value; if(measuring) ++counts.copied; return *this; }
    Tracked& operator=(Tracked&& x) noexcept { value=x.value; if(measuring) ++counts.moved; return *this; }
    ~Tracked() { --live_values; if(measuring) ++counts.destroyed; }
};
inline void require(bool ok) { if(!ok) std::abort(); }
inline void begin() { counts={}; measuring=true; }
inline void report(const char* phase) {
    measuring=false;
    std::printf("%s %ld %ld %ld %ld %ld %ld %ld\n", phase, counts.allocations,
        counts.bytes, counts.frees, counts.constructed, counts.copied, counts.moved, counts.destroyed);
}
