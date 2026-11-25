#pragma once
#include <atomic>
#include <cstdlib>
#include <new>

// Include once per independently linked proof executable. Ordinary-aligned allocations.
static std::atomic<long> allocations{0}, releases{0};
static std::atomic<bool> fail_allocation{false};
void *operator new(std::size_t size) {
    if (fail_allocation.exchange(false)) throw std::bad_alloc();
    if (void *p = std::malloc(size ? size : 1)) { ++allocations; return p; }
    throw std::bad_alloc();
}
void operator delete(void *p) noexcept { if (p) { ++releases; std::free(p); } }
void *operator new[](std::size_t n) { return ::operator new(n); }
void operator delete[](void *p) noexcept { ::operator delete(p); }
void operator delete(void *p, std::size_t) noexcept { ::operator delete(p); }
void operator delete[](void *p, std::size_t) noexcept { ::operator delete(p); }
