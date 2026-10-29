#include "scpp/carriers.hpp"
#include "../hash/allocation_probe.hpp"
#include <cstdlib>
#include <cstdio>
#include <stdexcept>
#include <type_traits>
#include <utility>
using namespace scpp;
void require(bool ok) { if (!ok) std::abort(); }
template<class E, class F> void throws(F f) {
    bool caught = false; try { f(); } catch (const E&) { caught = true; } require(caught);
}
static_assert(std::is_convertible_v<result_mixed, mixed>);
static_assert(std::is_convertible_v<result_dynamic, dynamic>);
static_assert(!std::is_constructible_v<result_mixed, dynamic>);
static_assert(!std::is_constructible_v<result_dynamic, mixed>);
static_assert(!std::is_constructible_v<result_mixed, result_dynamic>);
static_assert(!std::is_convertible_v<result_dynamic, bool>);
static_assert(std::is_nothrow_move_constructible_v<result_dynamic>);
static_assert(std::is_nothrow_move_assignable_v<result_mixed>);
void states() {
    auto before = allocation_probe::calls;
    result_mixed m; result_dynamic d;
    require(m.has_value().native_value() && !m.has_error().native_value());
    require(d.has_value().native_value() && !d.has_error().native_value());
    require(m.value().kind() == boxed_kind::null && d.value().kind() == boxed_kind::null);
    require(allocation_probe::calls == before);
    result_mixed mn{mixed{}};
    result_dynamic df{dynamic::from_boolean(bool_t{false})};
    require(mn.has_value().native_value() && mn.value().kind() == boxed_kind::null);
    require(df.has_value().native_value() && !df.value().get_boolean().native_value());
    scpp::error_t error{string{"parse error at byte 3"}, int64{0LL}, string{}};
    result_dynamic failed{error};
    require(failed.has_error().native_value() && !failed.has_value().native_value());
    throws<std::runtime_error>([&] { (void)failed.value(); });
    throws<std::runtime_error>([&] { dynamic value = failed; (void)value; });
    throws<std::runtime_error>([&] { (void)d.error(); });
    auto target = dynamic::from_integer(int64{42LL});
    // Guarded extraction: failure leaves the receiving binding unchanged.
    if (failed.has_value().native_value()) target = failed.value();
    require(target.get_integer().native_value() == 42);
    failed = result_dynamic{dynamic::from_boolean(bool_t{false})};
    if (failed.has_value().native_value()) target = failed.value();
    require(target.kind() == boxed_kind::boolean && !target.get_boolean().native_value());
    throws<std::invalid_argument>([&] { failed = result_dynamic{scpp::error_t::no_error}; });
    require(failed.has_value().native_value());
    auto moved = std::move(df);
    require(!moved.value().get_boolean().native_value() && df.value().kind() == boxed_kind::null);
    result_mixed failure{error}; auto moved_error = std::move(failure);
    require(moved_error.has_error().native_value() && failure.value().kind() == boxed_kind::null);
    auto* self = &moved_error; moved_error = *self; moved_error = std::move(*self);
    require(moved_error.error().get_message().size().native_value() == 21);
    result_string text{string{"ok"}};
    require(text.value().size().native_value() == 2);
}
void ownership() {
    auto m = mixed::container(); m.set(int64{0LL}, mixed::from_integer(int64{1LL}));
    result_mixed stored{m}; auto stored_copy = stored;
    auto extracted = stored_copy.value(); extracted.set(int64{0LL}, mixed::from_integer(int64{2LL}));
    require(stored.value().read(int64{0LL}).get_integer().native_value() == 1);
    require(stored_copy.value().read(int64{0LL}).get_integer().native_value() == 1);
    stored_copy = result_mixed{extracted};
    require(stored_copy.value().read(int64{0LL}).get_integer().native_value() == 2);
    auto d = dynamic::container(); d.set(int64{0LL}, dynamic::from_integer(int64{3LL}));
    result_dynamic shared{d}; auto shared_copy = shared;
    shared_copy.value().set(int64{0LL}, dynamic::from_integer(int64{4LL}));
    require(d.read(int64{0LL}).get_integer().native_value() == 4);
    require(shared.value().read(int64{0LL}).get_integer().native_value() == 4);
    auto binding = shared.value(); binding.clean();
    require(shared.value().kind() == boxed_kind::container);
    shared_copy = result_dynamic{dynamic::from_integer(int64{5LL})};
    require(shared.value().kind() == boxed_kind::container);
    auto null = dynamic{};
    shared = result_dynamic{null};
    require(shared.value().kind() == boxed_kind::null && d.read(int64{0LL}).get_integer().native_value() == 4);
}
void failures() {
    bool failed_once = false, succeeded = false;
    auto payload = mixed::container(); payload.set(int64{0LL}, mixed::from_integer(int64{8LL}));
    result_mixed source{payload};
    for (long n = 0; n < 8; ++n) {
        result_mixed target{mixed::from_integer(int64{7LL})};
        allocation_probe::fail_after = n;
        bool failed = false;
        try { target = source; } catch (const std::bad_alloc&) { failed = true; }
        allocation_probe::fail_after = -1;
        if (failed) { failed_once = true; require(target.value().get_integer().native_value() == 7); }
        else { succeeded = true; require(target.value().read(int64{0LL}).get_integer().native_value() == 8); }
        require(source.value().read(int64{0LL}).get_integer().native_value() == 8);
    }
    require(failed_once && succeeded);
    result_dynamic error{scpp::error_t{string{"long owned error text that requires an allocation"}}};
    failed_once = false; succeeded = false;
    for (long n = 0; n < 16; ++n) {
        result_dynamic target{dynamic::from_integer(int64{9LL})};
        allocation_probe::fail_after = n;
        bool failed = false;
        try { target = error; } catch (const std::bad_alloc&) { failed = true; }
        allocation_probe::fail_after = -1;
        if (failed) { failed_once = true; require(target.value().get_integer().native_value() == 9); }
        else { succeeded = true; require(target.has_error().native_value()); }
        require(error.has_error().native_value());
    }
    require(failed_once && succeeded);
}
int main() { states(); ownership(); failures(); std::puts("boxed results passed"); }
