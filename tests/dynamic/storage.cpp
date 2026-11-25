#include "boxed_conversion.hpp"
#include "../hash/allocation_probe.hpp"
#include <cmath>
#include <cstdio>
#include <cstdlib>
#include <limits>
#include <stdexcept>
#include <type_traits>
#include <utility>
using namespace scpp::detail;
using value = dynamic_value;
using kind = value::kind;
void require(bool ok) { if (!ok) std::abort(); }
template<class E, class F> void throws(F f) {
    bool caught = false;
    try { f(); } catch (const E&) { caught = true; }
    require(caught);
}
template<class T, class U> concept admits_element = requires(T t, U u) { t.set(0, u); t.append(u); };
static_assert(!std::is_same_v<mixed_value, dynamic_value>);
static_assert(!std::is_constructible_v<dynamic_value, mixed_value>);
static_assert(!std::is_constructible_v<mixed_value, dynamic_value>);
static_assert(!std::is_assignable_v<dynamic_value&, mixed_value>);
static_assert(!std::is_assignable_v<mixed_value&, dynamic_value>);
static_assert(!admits_element<mixed_value, dynamic_value> && !admits_element<dynamic_value, mixed_value>);
static_assert(admits_element<dynamic_value, dynamic_value> && admits_element<mixed_value, mixed_value>);
static_assert(std::is_nothrow_move_constructible_v<value> && std::is_nothrow_move_assignable_v<value>);

void scalar_and_null() {
    const auto before = allocation_probe::calls;
    value null;
    auto boolean = value::boolean(false), integer = value::integer(-5), floating = value::floating(1.5);
    auto copy = integer;
    copy = value::integer(6);
    require(integer.get_integer() == -5 && copy.get_integer() == 6);
    require(boolean.type() == kind::boolean && !boolean.get_boolean() && floating.get_floating() == 1.5);
    require(null.read(0).type() == kind::null && !null.contains("never-pooled") && !null.remove(0));
    require(allocation_probe::calls == before);
    throws<std::runtime_error>([&] { null.set(0, value::integer(1)); });
    throws<std::runtime_error>([&] { (void)null.append(value{}); });
    throws<std::out_of_range>([&] { (void)null.read(-1); });
    throws<std::out_of_range>([&] { null.set(2147483648LL, value{}); });
    require(null.type() == kind::null);
    throws<std::runtime_error>([&] { (void)integer.read(0); });
    throws<std::runtime_error>([&] { (void)integer.get_boolean(); });
    throws<std::runtime_error>([&] { floating.set("x", value{}); });
    auto text = value::string(std::string_view("a\0b", 3));
    auto text_copy = text;
    auto extracted = text_copy.get_string(); extracted[0] = 'z';
    text_copy = value::string("other");
    require(text.get_string() == std::string("a\0b", 3));
    value moved(std::move(text));
    require(text.type() == kind::null && moved.type() == kind::string);
    text = std::move(moved); require(moved.type() == kind::null);
    auto* self = &text; text = *self; text = std::move(*self);
    require(text.get_string() == std::string("a\0b", 3));
}
void mutate_parameter(value parameter) { parameter.set(0, value::integer(8)); parameter = value::integer(99); }
void identity() {
    auto original = value::container();
    require(original.type() == kind::container && original.container_size() == 0);
    require(original.append(value::integer(1)) == 0);
    auto before = allocation_probe::calls;
    auto alias = original;
    alias.set(0, value::integer(2));
    require(allocation_probe::calls == before); // shared replacement must not clone
    require(original.read(0).get_integer() == 2);
    mutate_parameter(original);
    require(original.read(0).get_integer() == 8);
    alias.clean(); require(alias.type() == kind::null && original.contains(0));
    alias = original; original = value::boolean(true);
    require(alias.read(0).get_integer() == 8 && original.get_boolean());
    auto moved = std::move(alias);
    require(alias.type() == kind::null && moved.read(0).get_integer() == 8);
    auto child = value::container(); child.set("x", value::integer(10));
    auto root = value::container(); root.set("child", child);
    auto root_alias = root;
    auto child_read = root_alias.read("child"); child_read.set("x", value::integer(11));
    require(root.read("child").read("x").get_integer() == 11 && child.read("x").get_integer() == 11);
    child_read = value::integer(12);
    require(root.read("child").type() == kind::container);
    root_alias.set("child", value::integer(13));
    require(root.read("child").get_integer() == 13 && child.read("x").get_integer() == 11);
    root.set("12", value::integer(12)); root.set(12, value::integer(13));
    require(root.read("12").get_integer() == 12 && root.read(12).get_integer() == 13);
    root.set("null", value{}); require(root.contains("null") && root.read("null").type() == kind::null);
    before = allocation_probe::calls;
    require(!root.read("unknown-key").contains(0) && !root.remove("unknown-key"));
    require(allocation_probe::calls == before);
    require(root_alias.remove(12) && !root.contains(12));
    auto limit = value::container(); limit.set(2147483647, value::integer(4)); auto limit_alias = limit;
    throws<std::overflow_error>([&] { (void)limit_alias.append(value{}); });
    require(limit.container_size() == 1 && limit.read(2147483647).get_integer() == 4);
    limit_alias.remove(2147483647); require(limit.append(value{}) == 0);
    auto self = value::container(); self.set("self", self); auto self_alias = self;
    self.read("self").set(0, value::integer(7));
    require(self_alias.read(0).get_integer() == 7);
    require(self.remove("self")); // explicitly break the supported strong cycle
    auto left = value::container(), right = value::container();
    left.set("right", right); right.set("left", left);
    right.read("left").set(0, value::integer(5));
    require(left.read(0).get_integer() == 5);
    left.remove("right"); right.remove("left");
}
void conversion() {
    using M = mixed_value;
    require(dynamic_from_mixed_scalar(M{}).type() == kind::null);
    require(mixed_from_dynamic_scalar(value{}).type() == M::kind::null);
    require(!dynamic_from_mixed_scalar(M::boolean(false)).get_boolean());
    require(mixed_from_dynamic_scalar(value::boolean(true)).get_boolean());
    require(dynamic_from_mixed_scalar(M::integer(std::numeric_limits<std::int64_t>::min())).get_integer() == std::numeric_limits<std::int64_t>::min());
    require(mixed_from_dynamic_scalar(value::integer(std::numeric_limits<std::int64_t>::max())).get_integer() == std::numeric_limits<std::int64_t>::max());
    require(std::signbit(dynamic_from_mixed_scalar(M::floating(-0.0)).get_floating()));
    require(std::isnan(mixed_from_dynamic_scalar(value::floating(std::numeric_limits<double>::quiet_NaN())).get_floating()));
    auto bytes = std::string("a\0b", 3);
    require(dynamic_from_mixed_scalar(M::string(bytes)).get_string() == bytes);
    require(mixed_from_dynamic_scalar(value::string(bytes)).get_string() == bytes);
    auto target_dynamic = value::integer(1); auto target_mixed = M::integer(2);
    throws<std::runtime_error>([&] { target_dynamic = dynamic_from_mixed_scalar(M::container()); });
    throws<std::runtime_error>([&] { target_mixed = mixed_from_dynamic_scalar(value::container()); });
    require(target_dynamic.get_integer() == 1 && target_mixed.get_integer() == 2);
}
void failures() {
    bool failure_seen = false, success_seen = false;
    for (long n = 0; n < 18; ++n) {
        auto original = value::container(); original.append(value::integer(1)); original.append(value::integer(2));
        auto alias = original;
        allocation_probe::fail_after = n;
        bool failed = false;
        try { alias.set(5, value::integer(30)); } catch (const std::bad_alloc&) { failed = true; }
        allocation_probe::fail_after = -1;
        require(original.read(0).get_integer() == 1 && alias.read(1).get_integer() == 2);
        if (failed) {
            failure_seen = true;
            require(original.container_size() == 2 && alias.container_size() == 2 && !original.contains(5));
        } else { success_seen = true; require(original.read(5).get_integer() == 30); }
        alias.set(5, value::integer(31)); require(original.read(5).get_integer() == 31);
    }
    require(failure_seen && success_seen);
    bool string_failed = false;
    for (long n = 0; n < 8; ++n) {
        auto target = value::integer(7);
        auto source = mixed_value::string("long-text-for-explicit-scalar-family-conversion");
        allocation_probe::fail_after = n;
        bool failed = false;
        try { target = dynamic_from_mixed_scalar(source); } catch (const std::bad_alloc&) { failed = true; }
        allocation_probe::fail_after = -1;
        if (failed) { string_failed = true; require(target.get_integer() == 7); }
        else require(target.get_string() == source.get_string());
    }
    require(string_failed);
}
int main() {
    scalar_and_null(); identity(); conversion(); failures();
    std::printf("dynamic ownership passed; mixed_bytes=%zu dynamic_bytes=%zu\n", sizeof(mixed_value), sizeof(dynamic_value));
}
