#include "public_api_guard.hpp"
#include "scpp/string_factory.hpp"
#include "scpp/integer_factory.hpp"
#include "scpp/mixed_keyed_initializer.hpp"
#include "scpp/dynamic_keyed_initializer.hpp"
#include "scpp/observations/count_mixed.hpp"
#include "scpp/observations/count_dynamic.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <initializer_list>
using namespace scpp;
bool counting = false;
unsigned allocations = 0;
static int fail_after = -1;
void *operator new(std::size_t size)
{
    if (fail_after == 0) { fail_after = -1; throw std::bad_alloc(); }
    if (fail_after > 0) --fail_after;
    if (counting) ++allocations;
    if (void *memory = std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
#define CHECK(x) do { if (!(x)) { std::fprintf(stderr, "keyed factory failure %d\n", __LINE__); std::abort(); } } while (false)
extern "C" unsigned native_keyed_allocations();
int main()
{
    string_entries empty_strings(uint64(0)); integer_entries empty_integers(uint64(0));
    mixed_entries empty_mixed(uint64(0)); dynamic_entries empty_dynamic(uint64(0));
    counting = true;
    auto empty_s = string_factory::from_key_value(empty_strings);
    auto empty_i = integer_factory::from_key_value(empty_integers);
    counting = false; CHECK(allocations == 0 && empty_s.empty() && empty_i.empty());
    auto empty_m = mixed_keyed_initializer::from_key_value(empty_mixed);
    auto empty_d = dynamic_keyed_initializer::from_key_value(empty_dynamic);
    CHECK(empty_m.is_hash() && count(empty_m) == int64(0));
    CHECK(empty_d.is_hash() && count(empty_d) == int64(0));
    const string key("A sufficiently long key to measure owned factory copies");
    const string value("A sufficiently long value to measure owned factory copies");
    string_hash escaped;
    {
        string_entries input(uint64(1));
        bool failed = false;
        try { (void)string_factory::from_key_value(input); } catch (...) { failed = true; }
        CHECK(failed); input.append(string_entry(key, value));
        allocations = 0; counting = true;
        escaped = string_factory::from_key_value(input);
        counting = false; const unsigned public_count = allocations;
        const unsigned native_count = native_keyed_allocations();
        CHECK(public_count == native_count);
        std::printf("keyed string allocations native=%u public=%u\n", native_count, public_count);
        // Every allocation point can fail without publishing a partial result.
        for (unsigned point = 0; point < public_count; ++point) {
            fail_after = int(point); failed = false;
            try { escaped = string_factory::from_key_value(input); }
            catch (const std::bad_alloc &) { failed = true; }
            CHECK(failed && fail_after == -1 && escaped.at(key) == value);
        }
    }
    CHECK(escaped.at(key) == value);
    integer_entries integers(uint64(2));
    integers.append(integer_entry(int64(8), int64(12)));
    integers.append(integer_entry(int64(3), int64(9)));
    auto numbers = integer_factory::from_key_value(integers);
    CHECK(numbers.at(int64(8)) == int64(12) && numbers.at(int64(3)) == int64(9));
    integer_entries duplicate_int(uint64(2));
    duplicate_int.append(integer_entry(int64(8), int64(0)));
    duplicate_int.append(integer_entry(int64(8), int64(9)));
    bool failed = false;
    try { (void)integer_factory::from_key_value(duplicate_int); } catch (...) { failed = true; }
    CHECK(failed);
    string_entries duplicate_string(uint64(2));
    duplicate_string.append(string_entry(key, value)); duplicate_string.append(string_entry(key, string{}));
    failed = false;
    try { (void)string_factory::from_key_value(duplicate_string); } catch (...) { failed = true; }
    CHECK(failed);
    mixed copied;
    dynamic retained;
    {
        auto child_m = make_mixed_table(); child_m.set(int64(0), mixed(int64(1)));
        auto child_d = make_dynamic_container(); child_d.set(int64(0), dynamic(int64(1)));
        mixed_entries m(uint64(2)); dynamic_entries d(uint64(2));
        m.append(mixed_entry(mixed(int64(4)), mixed{}));
        m.append(mixed_entry(mixed(int64(9)), child_m));
        d.append(dynamic_entry(mixed(int64(4)), dynamic{}));
        d.append(dynamic_entry(mixed(int64(9)), child_d));
        copied = mixed_keyed_initializer::from_key_value(m);
        retained = dynamic_keyed_initializer::from_key_value(d);
        auto fresh = dynamic_keyed_initializer::from_key_value(d);
        retained.set(int64(4), dynamic(int64(7)));
        CHECK(fresh.get(int64(4)).is_null());
        child_m.set(int64(0), mixed(int64(2))); child_d.set(int64(0), dynamic(int64(2)));
        CHECK(copied.get(int64(9)).get(int64(0)).get_int() == int64(1));
        CHECK(retained.get(int64(9)).get(int64(0)).get_int() == int64(2));
    }
    CHECK(copied.get(int64(4)).is_null());
    CHECK(retained.get(int64(9)).get(int64(0)).get_int() == int64(2));
    // Duplicate detection uses presence, including null and false stored values.
    mixed_entries duplicate_m(uint64(2)); dynamic_entries duplicate_d(uint64(2));
    duplicate_m.append(mixed_entry(mixed(int64(4)), mixed{}));
    duplicate_m.append(mixed_entry(mixed(int64(4)), mixed(int64(1))));
    duplicate_d.append(dynamic_entry(mixed(int64(4)), dynamic(boolean(false))));
    duplicate_d.append(dynamic_entry(mixed(int64(4)), dynamic(int64(1))));
    bool failed_m = false, failed_d = false;
    try { (void)mixed_keyed_initializer::from_key_value(duplicate_m); } catch (...) { failed_m = true; }
    try { (void)dynamic_keyed_initializer::from_key_value(duplicate_d); } catch (...) { failed_d = true; }
    CHECK(failed_m && failed_d);
    // This SDK uses non-PHP key policy: integer 10 and string "10" stay distinct.
    mixed_entries keys_m(uint64(3)); dynamic_entries keys_d(uint64(3));
    keys_m.append(mixed_entry(mixed(int64(10)), mixed(int64(1))));
    keys_m.append(mixed_entry(mixed(string("10")), mixed(int64(2))));
    keys_m.append(mixed_entry(mixed(string("08")), mixed(int64(3))));
    keys_d.append(dynamic_entry(mixed(int64(10)), dynamic(int64(1))));
    keys_d.append(dynamic_entry(mixed(string("10")), dynamic(int64(2))));
    keys_d.append(dynamic_entry(mixed(string("08")), dynamic(int64(3))));
    auto km = mixed_keyed_initializer::from_key_value(keys_m);
    auto kd = dynamic_keyed_initializer::from_key_value(keys_d);
    CHECK(count(km) == int64(3) && count(kd) == int64(3));
    CHECK(km.get(string("10")).get_int() == int64(2) && kd.get(int64(10)).get_int() == int64(1));
    for (const auto &bad : {mixed{}, mixed(boolean(false)), mixed(float64(1.5))}) {
        mixed_entries im(uint64(1)); dynamic_entries id(uint64(1));
        im.append(mixed_entry(bad, mixed{})); id.append(dynamic_entry(bad, dynamic{}));
        failed_m = failed_d = false;
        try { (void)mixed_keyed_initializer::from_key_value(im); } catch (...) { failed_m = true; }
        try { (void)dynamic_keyed_initializer::from_key_value(id); } catch (...) { failed_d = true; }
        CHECK(failed_m && failed_d);
    }
    std::puts("PASS: public keyed factories, duplicate keys, ownership, allocation parity and failure cleanup");
}
