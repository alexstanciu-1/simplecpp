#include "mixed_value.hpp"
#include "../hash/allocation_probe.hpp"
#include <cstdio>
#include <cstdlib>
#include <limits>
#include <stdexcept>
#include <type_traits>
#include <utility>
using value = scpp::detail::mixed_value;
using kind = value::kind;
void require(bool ok) { if (!ok) std::abort(); }
template<class E, class F> void throws(F f) {
    bool caught = false;
    try { f(); } catch (const E&) { caught = true; }
    require(caught);
}
static_assert(std::is_nothrow_move_constructible_v<value> && std::is_nothrow_move_assignable_v<value>);
static_assert(!std::is_constructible_v<value, bool> && !std::is_constructible_v<value, const char*>);
void scalars() {
    auto before = allocation_probe::calls;
    value n;
    auto b = value::boolean(false);
    auto i = value::integer(std::numeric_limits<std::int64_t>::max());
    auto f = value::floating(1.25);
    auto copied = i;
    require(n.type() == kind::null && b.type() == kind::boolean && !b.get_boolean());
    require(i.get_integer() == std::numeric_limits<std::int64_t>::max() && f.get_floating() == 1.25);
    copied = value::integer(-1);
    require(i.get_integer() != copied.get_integer());
    require(allocation_probe::calls == before);
    throws<std::runtime_error>([&] { (void)b.get_integer(); });
    throws<std::runtime_error>([&] { (void)i.get_floating(); });
    throws<std::runtime_error>([&] { (void)n.get_string(); });
    auto s = value::string(std::string_view("a\0b", 3));
    before = allocation_probe::calls;
    auto s2 = s;
    require(allocation_probe::calls == before);
    auto text = s2.get_string(); text[0] = 'z';
    require(s.get_string() == std::string("a\0b", 3));
    s2 = value::string("replacement");
    require(s.get_string() == std::string("a\0b", 3));
    value moved(std::move(s));
    require(s.type() == kind::null && moved.type() == kind::string);
    s = std::move(moved);
    require(moved.type() == kind::null);
    value* self = &s;
    s = *self; s = std::move(*self);
    require(s.get_string() == std::string("a\0b", 3));
    s.clean(); require(s.type() == kind::null);
}
void containers() {
    value null;
    const auto before = allocation_probe::calls;
    require(null.read(0).type() == kind::null && !null.contains("missing") && !null.remove(0));
    require(null.type() == kind::null && allocation_probe::calls == before);
    throws<std::out_of_range>([&] { (void)null.read(-1); });
    throws<std::out_of_range>([&] { null.set(2147483648LL, value::integer(3)); });
    require(null.type() == kind::null);
    auto scalar = value::integer(4);
    throws<std::runtime_error>([&] { scalar.set(0, value{}); });
    throws<std::runtime_error>([&] { (void)scalar.read(0); });
    throws<std::runtime_error>([&] { (void)scalar.remove(0); });
    require(scalar.get_integer() == 4);
    auto empty = value::container();
    require(empty.type() == kind::container && empty.container_size() == 0 && empty.is_packed_container());
    require(null.append(value::integer(10)) == 0);
    null.set("12", value::integer(12)); null.set(12, value::integer(13));
    require(null.read("12").get_integer() == 12 && null.read(12).get_integer() == 13);
    null.set("present-null", value{});
    require(null.contains("present-null") && null.read("present-null").type() == kind::null);
    auto original = null;
    auto copies_before = allocation_probe::calls;
    auto copy = original;
    require(allocation_probe::calls == copies_before);
    auto miss_before = allocation_probe::calls;
    require(!copy.remove("absent-and-never-interned") && copy.read("absent-and-never-interned").type() == kind::null);
    require(allocation_probe::calls == miss_before);
    copy.set(0, value::integer(20));
    auto unique_before = allocation_probe::calls;
    copy.set(0, value::integer(21));
    require(allocation_probe::calls == unique_before);
    copy.set(0, value::integer(20));
    require(original.read(0).get_integer() == 10 && copy.read(0).get_integer() == 20);
    require(copy.remove(12) && original.contains(12));
    copy.clean(); require(original.contains(12) && copy.type() == kind::null);
    auto child = value::container(); child.set(0, value::integer(1));
    auto root = value::container(); root.set("child", child);
    auto root_copy = root;
    auto extracted = root_copy.read("child");
    extracted.set(0, value::integer(2));
    require(root.read("child").read(0).get_integer() == 1);
    require(root_copy.read("child").read(0).get_integer() == 1);
    root_copy.set("child", extracted);
    require(root_copy.read("child").read(0).get_integer() == 2);
    require(root.read("child").read(0).get_integer() == 1 && child.read(0).get_integer() == 1);
    // Inserting an own value captures the previous value tree, not a COW cycle.
    auto own = value::container(); own.set(0, value::integer(7)); own.set("old", own);
    own.set(0, value::integer(8));
    require(own.read("old").read(0).get_integer() == 7 && !own.read("old").contains("old"));
    auto maximum = value::container(); maximum.set(2147483647, value::integer(1));
    auto maximum_copy = maximum;
    throws<std::overflow_error>([&] { (void)maximum_copy.append(value{}); });
    require(maximum_copy.container_size() == 1 && maximum.read(2147483647).get_integer() == 1);
    require(maximum_copy.remove(2147483647) && maximum_copy.append(value{}) == 0);
    require(maximum.contains(2147483647));
}
void failures() {
    bool bootstrap_failed = false, bootstrap_succeeded = false;
    bool detach_failed = false, detach_succeeded = false;
    auto source = value::container();
    source.append(value::integer(10)); source.append(value::integer(11));
    for (long n = 0; n < 24; ++n) {
        value fresh;
        allocation_probe::fail_after = n;
        bool failed = false;
        try { fresh.set(5, value::integer(30)); } catch (const std::bad_alloc&) { failed = true; }
        allocation_probe::fail_after = -1;
        if (failed) { bootstrap_failed = true; require(fresh.type() == kind::null); }
        else { bootstrap_succeeded = true; require(fresh.read(5).get_integer() == 30); }
        fresh.set(5, value::integer(31)); require(fresh.read(5).get_integer() == 31);
        auto target = source;
        allocation_probe::fail_after = n;
        failed = false;
        try { target.set(5, value::integer(30)); } catch (const std::bad_alloc&) { failed = true; }
        allocation_probe::fail_after = -1;
        require(source.container_size() == 2 && source.read(0).get_integer() == 10 && !source.contains(5));
        if (failed) {
            detach_failed = true;
            require(target.container_size() == 2 && target.is_packed_container() && !target.contains(5));
        } else { detach_succeeded = true; require(target.read(5).get_integer() == 30); }
        target.set(5, value::integer(31)); require(target.read(5).get_integer() == 31);
    }
    require(bootstrap_failed && bootstrap_succeeded && detach_failed && detach_succeeded);
    bool remove_failed = false;
    for (long n = 0; n < 12; ++n) {
        auto target = source;
        allocation_probe::fail_after = n;
        bool failed = false;
        try { (void)target.remove(0); } catch (const std::bad_alloc&) { failed = true; }
        allocation_probe::fail_after = -1;
        if (failed) { remove_failed = true; require(target.read(0).get_integer() == 10); }
        require(source.read(0).get_integer() == 10);
    }
    require(remove_failed);
    auto kept = value::string("unchanged");
    for (long n = 0; n < 4; ++n) {
        auto target = kept;
        allocation_probe::fail_after = n;
        bool failed = false;
        try { target = value::string("long-new-string-needing-its-own-storage"); }
        catch (const std::bad_alloc&) { failed = true; }
        allocation_probe::fail_after = -1;
        if (failed) require(target.get_string() == "unchanged");
        require(kept.get_string() == "unchanged");
    }
}
int main() {
    scalars(); containers(); failures();
    std::printf("mixed ownership passed; value_bytes=%zu\n", sizeof(value));
}
