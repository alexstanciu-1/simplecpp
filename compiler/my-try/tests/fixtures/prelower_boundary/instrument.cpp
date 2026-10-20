#include "instrument.hpp"
Counters counts;
bool measuring = false;
int live_values = 0;
int copies_before_throw = -1;
static void* allocate(std::size_t n, std::size_t alignment) {
    void* p = nullptr;
    if(alignment) { if(posix_memalign(&p, alignment, n ? n : 1)) throw std::bad_alloc(); }
    else { p=std::malloc(n ? n : 1); if(!p) throw std::bad_alloc(); }
    if(measuring) { ++counts.allocations; counts.bytes += n; }
    return p;
}
static void release(void* p) noexcept { if(p && measuring) ++counts.frees; std::free(p); }
void* operator new(std::size_t n) { return allocate(n, 0); }
void* operator new[](std::size_t n) { return allocate(n, 0); }
void* operator new(std::size_t n, std::align_val_t a) { return allocate(n, static_cast<std::size_t>(a)); }
void* operator new[](std::size_t n, std::align_val_t a) { return allocate(n, static_cast<std::size_t>(a)); }
void operator delete(void* p) noexcept { release(p); }
void operator delete[](void* p) noexcept { release(p); }
void operator delete(void* p, std::size_t) noexcept { release(p); }
void operator delete[](void* p, std::size_t) noexcept { release(p); }
void operator delete(void* p, std::align_val_t) noexcept { release(p); }
void operator delete[](void* p, std::align_val_t) noexcept { release(p); }
void operator delete(void* p, std::size_t, std::align_val_t) noexcept { release(p); }
void operator delete[](void* p, std::size_t, std::align_val_t) noexcept { release(p); }
