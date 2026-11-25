#include <initializer_list>
#include "adapter.hpp"
#include <cassert>
#include <cstdlib>
#include <new>

#ifndef APPEND
#define APPEND(items, ...) items.append_fields(__VA_ARGS__)
#endif

static int budget = -1;
void* operator new(std::size_t n) {
    if (budget == 0) throw std::bad_alloc();
    if (budget > 0) --budget;
    if (auto p = std::malloc(n ? n : 1)) return p;
    throw std::bad_alloc();
}
void operator delete(void* p) noexcept { std::free(p); }
void operator delete(void* p, std::size_t) noexcept { std::free(p); }

int main() {
    decltype(Result::tokens) items;
    for (int fail : {0, 1}) {
        budget = fail;
        try { APPEND(items, 1, 2, 3); assert(false); } catch (const std::bad_alloc&) {}
        budget = -1;
        assert(items.size() == 0);
    }
    items.reserve(4);
    budget = 0;
    for (int i = 0; i < 4; ++i) APPEND(items, i, 2, 3);
    try { APPEND(items, 4, 2, 3); assert(false); } catch (const std::bad_alloc&) {}
    budget = -1;
    assert(items.size() == 4 && items.at(3).offset == 3);
    APPEND(items, 4, 2, 3);
    assert(items.size() == 5 && items.at(4).offset == 4);
}
