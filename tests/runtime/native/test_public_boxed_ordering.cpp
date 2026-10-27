#include "public_api_guard.hpp"
#include "scpp/operators/less_mixed.hpp"
#include "scpp/operators/less_dynamic.hpp"
#include "scpp/operators/less_equal_mixed.hpp"
#include "scpp/operators/less_equal_dynamic.hpp"
#include "scpp/operators/greater_mixed.hpp"
#include "scpp/operators/greater_dynamic.hpp"
#include "scpp/operators/greater_equal_mixed.hpp"
#include "scpp/operators/greater_equal_dynamic.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <limits>
#include <initializer_list>
using namespace scpp;
static unsigned allocations = 0;
static bool counting = false;
void *operator new(std::size_t n) { if (counting) ++allocations; if (auto p = std::malloc(n ? n : 1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p, std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if (!(x)) { std::fprintf(stderr, "boxed ordering failure %d\n", __LINE__); std::abort(); } } while (false)
template<class Box>
static void compare(const Box &a, const Box &b, bool lt, bool le, bool gt, bool ge)
{
    allocations = 0; counting = true;
    CHECK(numeric_less(a,b).native_value() == lt);
    CHECK(numeric_less_equal(a,b).native_value() == le);
    CHECK(numeric_greater(a,b).native_value() == gt);
    CHECK(numeric_greater_equal(a,b).native_value() == ge);
    counting = false; CHECK(allocations == 0);
}
template<class Box>
static void family(const Box &container)
{
    for (int lf = 0; lf < 2; ++lf) for (int rf = 0; rf < 2; ++rf) {
        const Box a = lf ? Box(float64(3)) : Box(int64(3));
        const Box b = rf ? Box(float64(7)) : Box(int64(7));
        compare(a,b,true,true,false,false);
        compare(b,a,false,false,true,true);
        compare(a,a,false,true,false,true);
        CHECK((lf ? a.get_float().native_value() : a.get_int().native_value()) == 3);
    }
    compare(Box(int64(9007199254740993LL)), Box(float64(9007199254740992.0)),false,true,false,true);
    compare(Box(float64(-0.0)),Box(float64(0.0)),false,true,false,true);
    const Box inf{float64(std::numeric_limits<double>::infinity())};
    compare(Box(int64(1)),inf,true,true,false,false);
    const Box nan{float64(std::numeric_limits<double>::quiet_NaN())};
    compare(nan,inf,false,false,false,false);
    compare(inf,nan,false,false,false,false);
    for (const Box &invalid : {Box{}, Box(boolean(false)), Box(string("3")), container}) {
        for (int side = 0; side < 2; ++side) {
            const Box &a = side ? invalid : inf;
            const Box &b = side ? inf : invalid;
            unsigned thrown = 0;
            try { (void)numeric_less(a,b); } catch (...) { ++thrown; }
            try { (void)numeric_less_equal(a,b); } catch (...) { ++thrown; }
            try { (void)numeric_greater(a,b); } catch (...) { ++thrown; }
            try { (void)numeric_greater_equal(a,b); } catch (...) { ++thrown; }
            CHECK(thrown == 4);
        }
    }
}
int main() {
    family(make_mixed_table()); family(make_dynamic_container());
    std::puts("PASS: boxed ordering, numeric kinds, rounding, NaN, rejection; successful allocations=0");
}
