#include "public_api_guard.hpp"
#include "scpp/access/require_write_key_mixed.hpp"
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
#define CHECK(x) do { if (!(x)) { std::fprintf(stderr, "index write failure %d\n", __LINE__); std::abort(); } } while (false)

int main()
{
    const mixed key(int64(1));
    const mixed text_key(string("entry"));
    mixed table;
    auto object = make_dynamic_container();
    require_write_key(key);
    table.set(key, mixed(int64(7)));
    object.set(key, dynamic(int64(7)));
    CHECK(table.get(key).get_int() == int64(7));
    CHECK(object.get(key).get_int() == int64(7));
    counting = true;
    for (int i = 0; i < 100; ++i) {
        require_write_key(key);
        table.set(key, mixed(int64(8)));
        object.set(key, dynamic(int64(8)));
    }
    counting = false;
    CHECK(allocations == 0);
    CHECK(count(table) == int64(1) && count(object) == int64(1));
    require_write_key(text_key);
    table.set(text_key, mixed(string("owned text")));
    object.set(text_key, dynamic(string("owned text")));
    CHECK(table.get(text_key).get_string().equals("owned text"));
    CHECK(object.get(text_key).get_string().equals("owned text"));

    // Explicit staging models the compiler boundary; C++ argument order is not used.
    const mixed invalid_keys[] = {mixed{}, mixed(boolean(false)), mixed(float64(1.0)), make_mixed_table()};
    for (const auto &invalid : invalid_keys) {
        int rhs_effects = 0;
        bool rejected = false;
        try {
            const mixed saved_key = invalid;
            require_write_key(saved_key);
            ++rhs_effects;
            table.set(saved_key, mixed(int64(99)));
        } catch (...) { rejected = true; }
        CHECK(rejected && rhs_effects == 0);
        mixed absent;
        dynamic absent_dynamic;
        bool mixed_failed = false, dynamic_failed = false;
        try { absent.set(invalid, mixed{}); } catch (...) { mixed_failed = true; }
        try { absent_dynamic.set(invalid, dynamic{}); } catch (...) { dynamic_failed = true; }
        CHECK(mixed_failed && dynamic_failed && absent.is_null() && absent_dynamic.is_null());
    }

    // Valid keys do not validate receiver storage until after RHS evaluation.
    int rhs_effects = 0;
    dynamic absent;
    bool rejected = false;
    try {
        require_write_key(key);
        ++rhs_effects;
        absent.set(key, dynamic(int64(9)));
    } catch (...) { rejected = true; }
    CHECK(rejected && rhs_effects == 1 && absent.is_null());
    mixed untouched;
    try {
        require_write_key(key);
        throw 1; // RHS evaluation/conversion failed before the store.
        untouched.set(key, mixed(int64(9)));
    } catch (...) {}
    CHECK(untouched.is_null());

    // Replacing the root during RHS evaluation redirects this local-root write.
    mixed source_key(int64(4));
    const mixed saved_key = source_key;
    require_write_key(saved_key);
    source_key = int64(5);
    auto old_object = object;
    object = make_dynamic_container();
    object.set(saved_key, dynamic(int64(12)));
    CHECK(old_object.get(saved_key).is_null());
    CHECK(object.get(saved_key).get_int() == int64(12));
    CHECK(object.get(source_key).is_null());

    auto independent = table;
    table.set(key, mixed(int64(21)));
    CHECK(independent.get(key).get_int() == int64(8));
    auto alias = object;
    object.set(saved_key, dynamic(int64(22)));
    CHECK(alias.get(saved_key).get_int() == int64(22));
    mixed self;
    self.set(key, self);
    CHECK(self.get(key).is_null());
    self.set(text_key, self);
    CHECK(self.get(text_key).get(key).is_null());
    std::puts("PASS: checked write staging, same-family storage, self-aliasing and zero replacement allocations");
}
