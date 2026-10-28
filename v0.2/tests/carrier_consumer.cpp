#include <errno.h>
#include <scpp/short_names.hpp>
#include <scpp/native_bridge.hpp>
#include <stdexcept>
#include <type_traits>
#include <utility>
static void require(bool ok) { if (!ok) throw std::runtime_error("Carrier proof failed"); }
template<class Error, class F> static void fails(F f) {
    try { f(); } catch (const Error &) { return; }
    throw std::runtime_error("Expected carrier failure");
}
template<class T> concept condition = requires(T x) { x ? 1 : 0; };
template<class L, class R> concept adds = requires(L l, R r) { l + r; };
template<class T> concept increments = requires(T x) { ++x; };
static_assert(std::is_convertible_v<nullable_int64, int64>);
static_assert(!std::is_convertible_v<int64, nullable_int64>);
static_assert(!std::is_constructible_v<nullable_int64, int>);
static_assert(!std::is_convertible_v<nullable_int64, int32>);
static_assert(!condition<nullable_bool_t> && !condition<result_or_bool_bool_t>);
static_assert(!increments<nullable_int64>);
static_assert(!adds<nullable_int64, float64>);
static_assert(std::is_same_v<decltype(nullable_int64{} + result_int64{}), int64>);
static_assert(std::is_same_v<decltype(nullable_int64{} + 1), int64>);
static_assert(std::is_same_v<decltype(nullable_string{}.value()), string>);
static_assert(std::is_nothrow_move_constructible_v<nullable_string>);
int main() {
    nullable_int64 empty{null};
    require(!static_cast<bool>(empty.has_value()));
    fails<std::runtime_error>([&] { int64 value = empty; (void)value; });
    nullable_int64 a{int64{20}};
    result_int64 b{int64{3}};
    int64 sum = a + b;
    require(sum.native_value() == 23 && (a + 1).native_value() == 21);
    require(result_int64{}.value().native_value() == 0);
    a.reset();
    fails<std::runtime_error>([&] { (void)(a + b); });
    result_or_false_int64 false_state;
    require(static_cast<bool>(false_state.is_false()));
    fails<std::runtime_error>([&] { (void)false_state.value(); });
    result_or_bool_int64 true_state = result_or_bool_int64::from_true();
    require(static_cast<bool>(true_state.is_true()) && !static_cast<bool>(true_state.has_value()));
    fails<std::runtime_error>([&] { (void)true_state.value(); });
    result_or_bool_bool_t boolean_payload{bool_t{false}};
    require(static_cast<bool>(boolean_payload.has_value()) && !static_cast<bool>(boolean_payload.is_false()));
    require(!static_cast<bool>(boolean_payload.value()));
    boolean_payload.reset();
    require(static_cast<bool>(boolean_payload.is_false()));
    fails<std::runtime_error>([&] { (void)boolean_payload.value(); });
    result_or_false_bool_t false_payload{bool_t{false}};
    require(static_cast<bool>(false_payload.has_value()) && !static_cast<bool>(false_payload.is_false()));
    nullable_bool_t present_false{bool_t{false}};
    require(static_cast<bool>(present_false.has_value()) && !static_cast<bool>(present_false.value()));
    require(static_cast<bool>(!present_false)); // Payload !, not a presence query.

    scpp::error_t error{string{""}, int64{7}, string{"source.phs"}};
    require(static_cast<bool>(error.has_error()) && !static_cast<bool>(scpp::error_t::no_error.has_error()));
    require(static_cast<bool>(error) && !static_cast<bool>(scpp::error_t::no_error));
    require(static_cast<bool>(error.get_file() == string{"source.phs"}));
    result_int64 failed{error};
    require(static_cast<bool>(failed.has_error()) && failed.error().get_line().native_value() == 7);
    fails<std::runtime_error>([&] { (void)(failed + b); });
    fails<std::runtime_error>([&] { (void)b.error(); });
    fails<std::invalid_argument>([&] { b = result_int64{scpp::error_t::no_error}; });
    require(b.value().native_value() == 3);
    auto moved_failure = std::move(failed);
    require(static_cast<bool>(moved_failure.has_error()) && static_cast<bool>(failed.has_value()));
    require(failed.value().native_value() == 0);

    nullable_string original{string{"Ada"}};
    auto copy = original;
    string detached = copy;
    detached.append(string{"!"});
    require(static_cast<bool>(original.value() == string{"Ada"}));
    auto moved = std::move(original);
    require(!static_cast<bool>(original.has_value()) && static_cast<bool>(moved.has_value()));
    auto *self = &moved;
    moved = *self; moved = std::move(*self);
    require(static_cast<bool>(moved.value() == string{"Ada"}));
    vector_string names; names.append("Ada");
    result_vector_string wrapped{names};
    auto extracted = wrapped.value(); extracted.set(0, "Grace");
    require(static_cast<bool>(wrapped.value().at(0) == string{"Ada"}));
    names.clear();
    require(wrapped.value().size().native_value() == 1);
}
