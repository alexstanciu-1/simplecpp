#include "public_api_guard.hpp"
#include "scpp/mixed.hpp"
#include "scpp/dynamic.hpp"
#include "scpp/observations/count_mixed.hpp"
#include "scpp/observations/count_dynamic.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>

using namespace scpp;
static unsigned allocations = 0;
static bool counting = false;
void *operator new(std::size_t size)
{
    if (counting) ++allocations;
    if (void *memory = std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
#define CHECK(x) do { if (!(x)) { std::fprintf(stderr, "index read failure %d\n", __LINE__); std::abort(); } } while (false)

int main()
{
    const mixed integer_key(int64(3)), normalized_key(string("3"));
    const mixed text_key(string("child")), missing_key(string("missing"));
    auto table = make_mixed_table();
    auto object = make_dynamic_container();
    table.set(int64(3), mixed(int64(42)));
    object.set(int64(3), dynamic(int64(42)));
    // String interning is existing storage behavior; measure warmed observations.
    (void)table.get(normalized_key); (void)object.get(normalized_key);
    (void)table.get(missing_key); (void)object.get(missing_key);
    counting = true;
    for (int i = 0; i < 100; ++i) {
        CHECK(table.get(integer_key).get_int() == int64(42));
        CHECK(object.get(integer_key).get_int() == int64(42));
#if SCPP_TEST_PHP_KEYS
        CHECK(table.get(normalized_key).get_int() == int64(42));
        CHECK(object.get(normalized_key).get_int() == int64(42));
#else
        CHECK(table.get(normalized_key).is_null());
        CHECK(object.get(normalized_key).is_null());
#endif
        CHECK(table.get(missing_key).is_null());
        CHECK(object.get(missing_key).is_null());
        CHECK(mixed{}.get(integer_key).is_null());
        CHECK(dynamic{}.get(integer_key).is_null());
    }
    counting = false;
    CHECK(allocations == 0);
    CHECK(count(table) == int64(1) && count(object) == int64(1));

    // Invalid runtime keys throw even when the receiver is null.
    const mixed invalid_keys[] = {mixed{}, mixed(boolean(false)), mixed(float64(3.0)), make_mixed_table()};
    for (const auto &key : invalid_keys) {
        bool mixed_null = false, dynamic_null = false, mixed_table = false, dynamic_table = false;
        try { (void)mixed{}.get(key); } catch (...) { mixed_null = true; }
        try { (void)dynamic{}.get(key); } catch (...) { dynamic_null = true; }
        try { (void)table.get(key); } catch (...) { mixed_table = true; }
        try { (void)object.get(key); } catch (...) { dynamic_table = true; }
        CHECK(mixed_null && dynamic_null && mixed_table && dynamic_table);
    }
    bool mixed_scalar = false, dynamic_scalar = false;
    try { (void)mixed(int64(1)).get(integer_key); } catch (...) { mixed_scalar = true; }
    try { (void)dynamic(int64(1)).get(integer_key); } catch (...) { dynamic_scalar = true; }
    CHECK(mixed_scalar && dynamic_scalar);

    auto mixed_child = make_mixed_table();
    auto dynamic_child = make_dynamic_container();
    mixed_child.set(int64(3), mixed(int64(7)));
    dynamic_child.set(int64(3), dynamic(int64(7)));
    table.set(string("child"), mixed_child);
    object.set(string("child"), dynamic_child);
    auto mixed_read = table.get(text_key);
    auto dynamic_read = object.get(text_key);
    CHECK(table.get(text_key).get(integer_key).get_int() == int64(7));
    CHECK(object.get(text_key).get(integer_key).get_int() == int64(7));
    CHECK(table.get(missing_key).get(integer_key).is_null());
    CHECK(object.get(missing_key).get(integer_key).is_null());
    mixed_read.set(int64(3), mixed(int64(8)));
    dynamic_read.set(int64(3), dynamic(int64(8)));
    CHECK(table.get(text_key).get(integer_key).get_int() == int64(7));
    CHECK(object.get(text_key).get(integer_key).get_int() == int64(8));
    table.reset(); object.reset(); mixed_child.reset(); dynamic_child.reset();
    CHECK(mixed_read.get(integer_key).get_int() == int64(8));
    CHECK(dynamic_read.get(integer_key).get_int() == int64(8));
    CHECK(make_mixed_table().get(missing_key).is_null());
    CHECK(make_dynamic_container().get(missing_key).is_null());
    std::puts("PASS: checked mixed keys, read ownership, family semantics and zero lookup allocations");
}
