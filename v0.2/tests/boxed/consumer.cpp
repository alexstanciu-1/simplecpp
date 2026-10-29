#include "scpp/boxed.hpp"
#include "../hash/allocation_probe.hpp"
#include <cstdio>
#include <cstdlib>
#include <stdexcept>
#include <utility>
using namespace scpp;
void require(bool ok) { if (!ok) std::abort(); }
template<class E, class F> void throws(F f) {
    bool caught = false; try { f(); } catch(const E&) { caught = true; } require(caught);
}
int main() {
    auto before = allocation_probe::calls;
    mixed m;
    auto integer = mixed::from_integer(int64{-7LL});
    auto boolean = dynamic::from_boolean(bool_t{false});
    auto floating = dynamic::from_floating(float64{1.5});
    auto copy = integer;
    require(m.kind() == boxed_kind::null && copy.get_integer().native_value() == -7);
    require(!boolean.get_boolean().native_value() && floating.get_floating().native_value() == 1.5);
    require(allocation_probe::calls == before);
    m.set(int64{0LL}, integer);
    auto alias = m; alias.set(int64{0LL}, mixed::from_integer(int64{2LL}));
    require(m.read(int64{0LL}).get_integer().native_value() == -7);
    auto d = dynamic::container(); d.set(int64{0LL}, dynamic::from_integer(int64{1LL}));
    auto da = d; da.set(int64{0LL}, dynamic::from_integer(int64{3LL}));
    require(d.read(int64{0LL}).get_integer().native_value() == 3);
    auto child = dynamic::container(); d.set(string{"child"}, child);
    d.read(string{"child"}).set(int64{0LL}, dynamic::from_boolean(bool_t{true}));
    require(child.read(int64{0LL}).get_boolean().native_value());
    auto text = mixed::from_string(string{"a\0b", int64{3LL}});
    auto converted = dynamic::from_mixed_scalar(text);
    require(converted.get_string().size().native_value() == 3 && converted.get_string().data()[1] == '\0');
    require(mixed::from_dynamic_scalar(converted).kind() == boxed_kind::string);
    throws<std::runtime_error>([&] { converted = dynamic::from_mixed_scalar(m); });
    require(converted.kind() == boxed_kind::string);
    auto empty = dynamic{};
    throws<std::runtime_error>([&] { empty.set(int64{0LL}, dynamic{}); });
    throws<std::out_of_range>([&] { (void)empty.read(int64{-1LL}); });
    require(empty.read(string{"missing"}).kind() == boxed_kind::null);
    d.set(string{"12"}, dynamic::from_integer(int64{12LL}));
    require(!d.has(int64{12LL}).native_value());
    d.set(int64{2147483647LL}, dynamic{});
    throws<std::overflow_error>([&] { (void)da.append(dynamic{}); });
    require(da.remove(int64{2147483647LL}).native_value());
    auto moved = std::move(da); require(da.kind() == boxed_kind::null);
    moved.clean(); require(d.kind() == boxed_kind::container);
    auto* self = &d; d = *self; d = std::move(*self);
    require(d.read(int64{0LL}).get_integer().native_value() == 3);
    bool failed_once = false;
    for (long n = 0; n < 16; ++n) {
        auto target = m;
        allocation_probe::fail_after = n;
        bool failed = false;
        try { target.set(int64{9LL}, integer); } catch (const std::bad_alloc&) { failed = true; }
        allocation_probe::fail_after = -1;
        if (failed) { failed_once = true; require(!target.has(int64{9LL}).native_value()); }
        require(!m.has(int64{9LL}).native_value());
    }
    require(failed_once);
    std::printf("boxed consumer passed; mixed=%zu dynamic=%zu\n", sizeof(mixed), sizeof(dynamic));
}
