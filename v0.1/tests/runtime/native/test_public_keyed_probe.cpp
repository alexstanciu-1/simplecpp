#include "public_api_guard.hpp"
#include "scpp/access/keyed_ready_mixed.hpp"
#include "scpp/access/keyed_ready_dynamic.hpp"
#include "scpp/access/keyed_probe_mixed.hpp"
#include "scpp/access/keyed_probe_dynamic.hpp"
#include "scpp/access/reject_empty_operand.hpp"
#include "scpp/exception_info.hpp"
#include "scpp/observations/count_mixed.hpp"
#include "scpp/observations/count_dynamic.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <initializer_list>

using namespace scpp;
static bool counting = false;
static unsigned allocations = 0;
void *operator new(std::size_t size)
{
    if (counting) ++allocations;
    if (void *memory = std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
#define CHECK(x) do { if (!(x)) { std::fprintf(stderr, "keyed probe failure %d\n", __LINE__); std::abort(); } } while (false)

int main()
{
    const boolean probing(false), removing(true), presence(false), emptiness(true);
    const mixed null_key(int64(0)), value_key(int64(1)), empty_key(int64(2)), missing_key(int64(3));
    auto table = make_mixed_table();
    auto object = make_dynamic_container();
    table.set(null_key, mixed{}); object.set(null_key, dynamic{});
    table.set(value_key, mixed(boolean(false))); object.set(value_key, dynamic(boolean(false)));
    table.set(empty_key, mixed(string(""))); object.set(empty_key, dynamic(string("")));
    const mixed long_key(int64(4));
    const string long_text("A nonempty string longer than inline storage, observed without payload copying");
    table.set(long_key, mixed(long_text)); object.set(long_key, dynamic(long_text));
    counting = true;
    for (int i = 0; i < 100; ++i) {
        CHECK(!keyed_probe(table, long_key, emptiness) && !keyed_probe(object, long_key, emptiness));
        CHECK(keyed_ready(table, probing) && keyed_ready(object, probing));
        CHECK(!keyed_probe(table, null_key, presence) && !keyed_probe(object, null_key, presence));
        CHECK(!keyed_probe(table, missing_key, presence) && !keyed_probe(object, missing_key, presence));
        CHECK(keyed_probe(table, value_key, presence) && keyed_probe(object, value_key, presence));
        CHECK(keyed_probe(table, empty_key, presence) && keyed_probe(object, empty_key, presence));
        CHECK(keyed_probe(table, empty_key, emptiness) && keyed_probe(object, empty_key, emptiness));
    }
    counting = false;
    CHECK(allocations == 0);
    CHECK(count(table) == int64(4) && count(object) == int64(4));

    const mixed invalid_keys[] = {mixed{}, mixed(boolean(false)), mixed(float64(1.0)), make_mixed_table()};
    for (const auto &key : invalid_keys) {
        CHECK(!keyed_probe(table, key, presence) && !keyed_probe(object, key, presence));
        bool m = false, d = false, rm = false, rd = false;
        try { (void)keyed_probe(table, key, emptiness); }
        catch (...) { m = true; CHECK(capture_runtime_exception().code().equals("invalid_empty_operand")); }
        try { (void)keyed_probe(object, key, emptiness); }
        catch (...) { d = true; CHECK(capture_runtime_exception().code().equals("invalid_empty_operand")); }
        try { (void)table.remove(key); } catch (...) { rm = true; }
        try { (void)object.remove(key); } catch (...) { rd = true; }
        CHECK(m && d && rm && rd);
    }
    for (const auto &key : {null_key, missing_key, value_key}) {
        bool m = false, d = false;
        try { (void)keyed_probe(table, key, emptiness); }
        catch (...) { m = true; CHECK(capture_runtime_exception().code().equals("invalid_empty_operand")); }
        try { (void)keyed_probe(object, key, emptiness); }
        catch (...) { d = true; CHECK(capture_runtime_exception().code().equals("invalid_empty_operand")); }
        CHECK(m && d);
    }

    // Readiness runs before any source key expression; absent roots skip it.
    int key_effects = 0;
    mixed absent;
    dynamic absent_object;
    if (keyed_ready(absent, probing)) { ++key_effects; (void)keyed_probe(absent, value_key, presence); }
    if (keyed_ready(absent_object, removing)) { ++key_effects; (void)absent_object.remove(value_key); }
    CHECK(key_effects == 0 && absent.is_null() && absent_object.is_null());
    bool rejected = false;
    try {
        if (!keyed_ready(absent, probing)) reject_empty_operand();
        ++key_effects;
        (void)keyed_probe(absent, value_key, emptiness);
    } catch (...) { rejected = true; CHECK(capture_runtime_exception().code().equals("invalid_empty_operand")); }
    CHECK(rejected && key_effects == 0);
    CHECK(!keyed_ready(mixed(int64(1)), probing));
    CHECK(!keyed_ready(dynamic(int64(1)), probing));
    bool scalar_m = false, scalar_d = false;
    try { (void)keyed_ready(mixed(int64(1)), removing); } catch (...) { scalar_m = true; }
    try { (void)keyed_ready(dynamic(int64(1)), removing); } catch (...) { scalar_d = true; }
    CHECK(scalar_m && scalar_d);

    // No receiver pointer is cached across a key expression that changes the root.
    CHECK(keyed_ready(table, probing));
    table.reset(); ++key_effects;
    CHECK(!keyed_probe(table, value_key, presence));
    CHECK(keyed_ready(object, probing));
    object.reset(); ++key_effects;
    CHECK(!keyed_probe(object, value_key, presence));
    CHECK(key_effects == 2);

    table = make_mixed_table(); object = make_dynamic_container();
    table.set(null_key, mixed{}); object.set(null_key, dynamic{});
    auto independent = table;
    auto alias = object;
    CHECK(table.remove(null_key) && object.remove(null_key));
    CHECK(count(independent) == int64(1) && count(alias) == int64(0));
    CHECK(!table.remove(null_key) && !object.remove(null_key));
    counting = true;
    for (int i = 0; i < 100; ++i) {
        CHECK(!table.remove(missing_key) && !object.remove(missing_key));
    }
    counting = false;
    CHECK(allocations == 0);
    std::puts("PASS: null-sensitive probes, lazy root readiness, checked removal and no probe allocations");
}
