#include "public_api_guard.hpp"
#include "scpp/operators/add_int8.hpp"
#include "scpp/operators/add_int64.hpp"
#include "scpp/operators/add_uint64.hpp"
#include "scpp/operators/add_float64.hpp"
#include "scpp/operators/equal_mixed.hpp"
#include "scpp/operators/not_equal_mixed.hpp"
#include "scpp/operators/equal_dynamic.hpp"
#include "scpp/operators/not_equal_dynamic.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <stdexcept>
#include <limits>

static unsigned allocations = 0;
static bool count_allocations = false;
void *operator new(std::size_t size)
{
    if (count_allocations) ++allocations;
    if (auto *p = std::malloc(size ? size : 1)) return p;
    throw std::bad_alloc();
}
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p, std::size_t) noexcept { std::free(p); }
static void check(bool value, int line)
{
    if (!value) { std::fprintf(stderr, "global operator check failed: %d\n", line); std::abort(); }
}
#define CHECK(value) check(static_cast<bool>(value), __LINE__)

int main()
{
    using namespace scpp;
    CHECK(numeric_add(int8(2), int8(3)).native_value() == 5);
    // Lowering supplies conversions selected by the metadata resolver, left first.
    const int16 original_left(300);
    const int32 original_right(70000);
    const int64 left(original_left.native_value());
    const int64 right(original_right.native_value());
    CHECK(numeric_add(left, right).native_value() == 70300);
    CHECK(numeric_add(uint64(5), uint64(6)).native_value() == 11);
    CHECK(numeric_add(float64(1.5), float64(2.0)).native_value() == 3.5);
    const mixed values[] = {mixed(), mixed(boolean(false)), mixed(boolean(true)), mixed(int64(0)),
        mixed(int64(2)), mixed(float64(2.0)), mixed(string("")), mixed(string("0")), mixed(string("2"))};
    unsigned rejected = 0;
    for (unsigned i = 0; i != 9; ++i) for (unsigned j = 0; j != 9; ++j) {
        const auto a = to_dynamic(values[i]), b = to_dynamic(values[j]);
        const bool same_group = (i == 0 && j == 0) || (i >= 1 && i <= 2 && j >= 1 && j <= 2)
            || (i >= 3 && i <= 5 && j >= 3 && j <= 5) || (i >= 6 && j >= 6);
        const bool admitted = same_group || i == 0 || j == 0;
        if (admitted) {
            const auto equal = value_equal(values[i], values[j]);
            CHECK(equal.native_value() == value_equal(a,b).native_value());
            CHECK(equal.native_value() != value_not_equal(values[i],values[j]).native_value());
            CHECK(equal.native_value() != value_not_equal(a,b).native_value());
            if (i == 0 || j == 0) CHECK(equal.native_value() == (i == j));
        } else {
            unsigned caught = 0;
            try { (void)value_equal(values[i],values[j]); } catch (const std::runtime_error &) { ++caught; }
            try { (void)value_not_equal(values[i],values[j]); } catch (const std::runtime_error &) { ++caught; }
            try { (void)value_equal(a,b); } catch (const std::runtime_error &) { ++caught; }
            try { (void)value_not_equal(a,b); } catch (const std::runtime_error &) { ++caught; }
            CHECK(caught == 4); ++rejected;
        }
    }
    CHECK(rejected != 0);
    CHECK(value_equal(values[4], values[5]));
    const mixed nan{float64{std::numeric_limits<double>::quiet_NaN()}};
    CHECK(!value_equal(nan,nan));
    const string bytes("a\0b", uint64(3));
    const mixed text(bytes), same(bytes), shorter(string("a"));
    const dynamic dynamic_text(bytes), dynamic_same(bytes);
    allocations = 0; count_allocations = true;
    for (int i = 0; i != 100; ++i) {
        CHECK(numeric_add(left,right).native_value() == 70300);
        CHECK(value_equal(text,same));
        CHECK(value_not_equal(text,shorter));
        CHECK(value_equal(dynamic_text,dynamic_same));
    }
    count_allocations = false;
    CHECK(allocations == 0);
    std::printf("PASS: 81 boxed pairs, both families and operators; %u rejected pairs; success allocations=%u\n", rejected, allocations);
}
