#include "public_api_guard.hpp"
#include "scpp/operators/add_mixed.hpp"
#include "scpp/operators/subtract_mixed.hpp"
#include "scpp/operators/multiply_mixed.hpp"
#include "scpp/operators/divide_mixed.hpp"
#include "scpp/operators/remainder_mixed.hpp"
#include "scpp/operators/add_dynamic.hpp"
#include "scpp/operators/subtract_dynamic.hpp"
#include "scpp/operators/multiply_dynamic.hpp"
#include "scpp/operators/divide_dynamic.hpp"
#include "scpp/operators/remainder_dynamic.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <initializer_list>
#include <type_traits>
using namespace scpp;
static unsigned allocations = 0;
static bool counting = false;
void *operator new(std::size_t n) { if (counting) ++allocations; if (auto p = std::malloc(n ? n : 1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p, std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if (!(x)) { std::fprintf(stderr, "boxed arithmetic failure %d\n", __LINE__); std::abort(); } } while (false)

template<class Box, class Operation>
static void numeric_cases(Operation operation, double expected)
{
    for (int lf = 0; lf < 2; ++lf) for (int rf = 0; rf < 2; ++rf) {
        const Box left = lf ? Box(float64(12.0)) : Box(int64(12));
        const Box right = rf ? Box(float64(3.0)) : Box(int64(3));
        allocations = 0; counting = true;
        const auto result = operation(left, right);
        counting = false;
        static_assert(std::is_same_v<decltype(result), const Box>);
        CHECK(allocations == 0);
        if (lf || rf) CHECK(result.get_float().native_value() == expected);
        else CHECK(result.get_int().native_value() == expected);
        CHECK((lf ? left.get_float().native_value() : left.get_int().native_value()) == 12);
        CHECK((rf ? right.get_float().native_value() : right.get_int().native_value()) == 3);
    }
}

template<class Operation, class Box>
static void rejects(Operation operation, const Box &left, const Box &right)
{
    bool failed = false;
    try { (void)operation(left, right); } catch (...) { failed = true; }
    CHECK(failed);
}

template<class Box>
static void family(const Box &container)
{
    auto add = [](const Box &a, const Box &b) { return numeric_add(a, b); };
    auto sub = [](const Box &a, const Box &b) { return numeric_subtract(a, b); };
    auto mul = [](const Box &a, const Box &b) { return numeric_multiply(a, b); };
    auto div = [](const Box &a, const Box &b) { return numeric_divide(a, b); };
    auto mod = [](const Box &a, const Box &b) { return numeric_remainder(a, b); };
    numeric_cases<Box>(add, 15); numeric_cases<Box>(sub, 9);
    numeric_cases<Box>(mul, 36); numeric_cases<Box>(div, 4);
    const Box integer(int64(12));
    allocations = 0; counting = true;
    const auto remainder = mod(integer, Box(int64(5)));
    counting = false; CHECK(allocations == 0 && remainder.get_int().native_value() == 2);
    for (const Box &invalid : {Box{}, Box(boolean(false)), Box(string("12")), container}) {
        rejects(add, invalid, integer); rejects(add, integer, invalid);
        rejects(sub, invalid, integer); rejects(sub, integer, invalid);
        rejects(mul, invalid, integer); rejects(mul, integer, invalid);
        rejects(div, invalid, integer); rejects(div, integer, invalid);
        rejects(mod, invalid, integer); rejects(mod, integer, invalid);
    }
    rejects(div, integer, Box(int64(0)));
    rejects(div, Box(float64(12)), Box(float64(-0.0)));
    rejects(mod, integer, Box(int64(0)));
    rejects(mod, integer, Box(float64(3)));
    rejects(mod, Box(float64(12)), integer);
    // The calculation consumes snapshots; later RHS effects cannot change the saved left.
    Box target(int64(10)); const auto left = target;
    target = Box(float64(100)); const Box right(int64(3));
    const auto result = add(left, right);
    CHECK(result.get_int().native_value() == 13);
    CHECK(target.get_float().native_value() == 100);
}

int main()
{
    family(make_mixed_table()); family(make_dynamic_container());
    std::puts("PASS: both boxed arithmetic families, numeric kind combinations, rejection, snapshots; successful allocations=0");
}
