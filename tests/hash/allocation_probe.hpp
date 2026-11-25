#pragma once
#include <cstdlib>
#include <new>
#include <cstddef>
namespace allocation_probe {
inline long fail_after = -1;
inline std::size_t calls = 0;
inline void* allocate(std::size_t size) {
    if (fail_after == 0) throw std::bad_alloc{};
    if (fail_after > 0) --fail_after;
    ++calls;
    if (auto* ptr = std::malloc(size ? size : 1)) return ptr;
    throw std::bad_alloc{};
}
}
void* operator new(std::size_t n) { return allocation_probe::allocate(n); }
void* operator new[](std::size_t n) { return allocation_probe::allocate(n); }
void operator delete(void* p) noexcept { std::free(p); }
void operator delete[](void* p) noexcept { std::free(p); }
void operator delete(void* p, std::size_t) noexcept { std::free(p); }
void operator delete[](void* p, std::size_t) noexcept { std::free(p); }
