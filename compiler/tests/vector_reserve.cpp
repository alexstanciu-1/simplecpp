// Include adapter.hpp from the scanner proof to exercise its actual concrete token vector.
#include "adapter.hpp"
#include <scpp/vectors.hpp>
#include <scpp/value_error.hpp>
#include <cassert>
#include <limits>
#include <stdexcept>
#include <cstdlib>
#include <new>

static int budget = -1;
void* operator new(std::size_t n) {
    if (budget == 0) throw std::bad_alloc();
    if (budget > 0) --budget;
    if (auto p = std::malloc(n ? n : 1)) return p;
    throw std::bad_alloc();
}
void operator delete(void* p) noexcept { std::free(p); }
void operator delete(void* p, std::size_t) noexcept { std::free(p); }

template<class V, class T, class Check>
void verify(V values, const T& item, Check check) {
    using I = scpp::int64;
    budget = 0;
    values.reserve(I{0});
    budget = -1;
    for (int fail : {0, 1}) {
        budget = fail;
        try { values.reserve(I{8}); assert(false); } catch (const std::bad_alloc&) {}
        budget = -1;
        assert(values.size() == 0);
    }
    values.reserve(I{8});
    assert(!values.has(I{0}));
    try { (void)values.at(I{0}); assert(false); } catch (const std::out_of_range&) {}
    try { values.set(I{0}, item); assert(false); } catch (const std::out_of_range&) {}
    budget = 0;
    for (int i=0;i<8;++i) values.append(item);
    values.reserve(I{3});
    try { values.reserve(I{100}); assert(false); } catch (const std::bad_alloc&) {}
    budget = -1;
    try { values.reserve(I{-1}); assert(false); } catch (const scpp::ValueError&) {}
    try { values.reserve(std::numeric_limits<I>::max()); assert(false); } catch (const std::length_error&) {}
    values.reserve(I{100});
    assert(values.size() == 8 && !values.has(I{8}));
    for (I i=0;i<8;++i) check(values.at(i));
}
int main() {
    verify(scpp::vector_int64{}, scpp::int64{42}, [](auto n){ assert(n==42); });
    App::scpp_records::Token t{};
    t.offset=42;
    verify(decltype(Result::tokens){}, t, [](auto x){ assert(x.offset==42); });
}
