#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/weak_p.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/cast.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"

namespace scpp::detail::generated_operator_detail {

[[nodiscard]] inline constexpr int_t unary_plus(const int_t &value) noexcept {
	return int_t(+value.native_value());
}

[[nodiscard]] inline constexpr float_t unary_plus(const float_t &value) noexcept {
	return float_t(+value.native_value());
}

[[nodiscard]] inline constexpr int_t unary_minus(const int_t &value) noexcept {
	return int_t(-value.native_value());
}

[[nodiscard]] inline constexpr float_t unary_minus(const float_t &value) noexcept {
	return float_t(-value.native_value());
}

[[nodiscard]] inline constexpr int_t bitwise_not(const int_t &value) noexcept {
	return int_t(~value.native_value());
}

template <typename Value>
[[nodiscard]] inline bool truthy(const Value &value) {
	return static_cast<bool>(::scpp::php::condition_truthy(value));
}

[[nodiscard]] inline constexpr int_t add(const int_t &lhs, const int_t &rhs) noexcept {
	return int_t(lhs.native_value() + rhs.native_value());
}

[[nodiscard]] inline constexpr float_t add(const int_t &lhs, const float_t &rhs) noexcept {
	return float_t(static_cast<double>(lhs.native_value()) + rhs.native_value());
}

[[nodiscard]] inline constexpr float_t add(const float_t &lhs, const int_t &rhs) noexcept {
	return float_t(lhs.native_value() + static_cast<double>(rhs.native_value()));
}

[[nodiscard]] inline constexpr float_t add(const float_t &lhs, const float_t &rhs) noexcept {
	return float_t(lhs.native_value() + rhs.native_value());
}

[[nodiscard]] inline constexpr int_t sub(const int_t &lhs, const int_t &rhs) noexcept {
	return int_t(lhs.native_value() - rhs.native_value());
}

[[nodiscard]] inline constexpr float_t sub(const int_t &lhs, const float_t &rhs) noexcept {
	return float_t(static_cast<double>(lhs.native_value()) - rhs.native_value());
}

[[nodiscard]] inline constexpr float_t sub(const float_t &lhs, const int_t &rhs) noexcept {
	return float_t(lhs.native_value() - static_cast<double>(rhs.native_value()));
}

[[nodiscard]] inline constexpr float_t sub(const float_t &lhs, const float_t &rhs) noexcept {
	return float_t(lhs.native_value() - rhs.native_value());
}

[[nodiscard]] inline constexpr int_t mul(const int_t &lhs, const int_t &rhs) noexcept {
	return int_t(lhs.native_value() * rhs.native_value());
}

[[nodiscard]] inline constexpr float_t mul(const int_t &lhs, const float_t &rhs) noexcept {
	return float_t(static_cast<double>(lhs.native_value()) * rhs.native_value());
}

[[nodiscard]] inline constexpr float_t mul(const float_t &lhs, const int_t &rhs) noexcept {
	return float_t(lhs.native_value() * static_cast<double>(rhs.native_value()));
}

[[nodiscard]] inline constexpr float_t mul(const float_t &lhs, const float_t &rhs) noexcept {
	return float_t(lhs.native_value() * rhs.native_value());
}

[[nodiscard]] inline constexpr int_t div(const int_t &lhs, const int_t &rhs) noexcept {
	return int_t(lhs.native_value() / rhs.native_value());
}

[[nodiscard]] inline constexpr float_t div(const int_t &lhs, const float_t &rhs) noexcept {
	return float_t(static_cast<double>(lhs.native_value()) / rhs.native_value());
}

[[nodiscard]] inline constexpr float_t div(const float_t &lhs, const int_t &rhs) noexcept {
	return float_t(lhs.native_value() / static_cast<double>(rhs.native_value()));
}

[[nodiscard]] inline constexpr float_t div(const float_t &lhs, const float_t &rhs) noexcept {
	return float_t(lhs.native_value() / rhs.native_value());
}

[[nodiscard]] inline constexpr int_t mod(const int_t &lhs, const int_t &rhs) noexcept {
	return int_t(lhs.native_value() % rhs.native_value());
}

[[nodiscard]] inline constexpr int_t bit_and(const int_t &lhs, const int_t &rhs) noexcept {
	return int_t(lhs.native_value() & rhs.native_value());
}

[[nodiscard]] inline constexpr int_t bit_or(const int_t &lhs, const int_t &rhs) noexcept {
	return int_t(lhs.native_value() | rhs.native_value());
}

[[nodiscard]] inline constexpr int_t bit_xor(const int_t &lhs, const int_t &rhs) noexcept {
	return int_t(lhs.native_value() ^ rhs.native_value());
}

[[nodiscard]] inline constexpr int_t shl(const int_t &lhs, const int_t &rhs) noexcept {
	return int_t(lhs.native_value() << rhs.native_value());
}

[[nodiscard]] inline constexpr int_t shr(const int_t &lhs, const int_t &rhs) noexcept {
	return int_t(lhs.native_value() >> rhs.native_value());
}


[[nodiscard]] inline string_t concat(const string_t &lhs, const string_t &rhs) {
	return string_t(lhs.native_value() + rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t eq(const bool_t &lhs, const bool_t &rhs) noexcept {
	return bool_t(lhs.native_value() == rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t eq(const int_t &lhs, const int_t &rhs) noexcept {
	return bool_t(lhs.native_value() == rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t eq(const int_t &lhs, const float_t &rhs) noexcept {
	return bool_t(static_cast<double>(lhs.native_value()) == rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t eq(const float_t &lhs, const int_t &rhs) noexcept {
	return bool_t(lhs.native_value() == static_cast<double>(rhs.native_value()));
}

[[nodiscard]] inline constexpr bool_t eq(const float_t &lhs, const float_t &rhs) noexcept {
	return bool_t(lhs.native_value() == rhs.native_value());
}

[[nodiscard]] inline bool_t eq(const string_t &lhs, const string_t &rhs) noexcept {
	return bool_t(lhs.native_value() == rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t lt(const int_t &lhs, const int_t &rhs) noexcept {
	return bool_t(lhs.native_value() < rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t lt(const int_t &lhs, const float_t &rhs) noexcept {
	return bool_t(static_cast<double>(lhs.native_value()) < rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t lt(const float_t &lhs, const int_t &rhs) noexcept {
	return bool_t(lhs.native_value() < static_cast<double>(rhs.native_value()));
}

[[nodiscard]] inline constexpr bool_t lt(const float_t &lhs, const float_t &rhs) noexcept {
	return bool_t(lhs.native_value() < rhs.native_value());
}

[[nodiscard]] inline bool_t lt(const string_t &lhs, const string_t &rhs) noexcept {
	return bool_t(lhs.native_value() < rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t le(const int_t &lhs, const int_t &rhs) noexcept {
	return bool_t(lhs.native_value() <= rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t le(const int_t &lhs, const float_t &rhs) noexcept {
	return bool_t(static_cast<double>(lhs.native_value()) <= rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t le(const float_t &lhs, const int_t &rhs) noexcept {
	return bool_t(lhs.native_value() <= static_cast<double>(rhs.native_value()));
}

[[nodiscard]] inline constexpr bool_t le(const float_t &lhs, const float_t &rhs) noexcept {
	return bool_t(lhs.native_value() <= rhs.native_value());
}

[[nodiscard]] inline bool_t le(const string_t &lhs, const string_t &rhs) noexcept {
	return bool_t(lhs.native_value() <= rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t gt(const int_t &lhs, const int_t &rhs) noexcept {
	return bool_t(lhs.native_value() > rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t gt(const int_t &lhs, const float_t &rhs) noexcept {
	return bool_t(static_cast<double>(lhs.native_value()) > rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t gt(const float_t &lhs, const int_t &rhs) noexcept {
	return bool_t(lhs.native_value() > static_cast<double>(rhs.native_value()));
}

[[nodiscard]] inline constexpr bool_t gt(const float_t &lhs, const float_t &rhs) noexcept {
	return bool_t(lhs.native_value() > rhs.native_value());
}

[[nodiscard]] inline bool_t gt(const string_t &lhs, const string_t &rhs) noexcept {
	return bool_t(lhs.native_value() > rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t ge(const int_t &lhs, const int_t &rhs) noexcept {
	return bool_t(lhs.native_value() >= rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t ge(const int_t &lhs, const float_t &rhs) noexcept {
	return bool_t(static_cast<double>(lhs.native_value()) >= rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t ge(const float_t &lhs, const int_t &rhs) noexcept {
	return bool_t(lhs.native_value() >= static_cast<double>(rhs.native_value()));
}

[[nodiscard]] inline constexpr bool_t ge(const float_t &lhs, const float_t &rhs) noexcept {
	return bool_t(lhs.native_value() >= rhs.native_value());
}

[[nodiscard]] inline bool_t ge(const string_t &lhs, const string_t &rhs) noexcept {
	return bool_t(lhs.native_value() >= rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t logical_and(const bool_t &lhs, const bool_t &rhs) noexcept {
	return bool_t(lhs.native_value() && rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t logical_and(const int_t &lhs, const int_t &rhs) noexcept {
	return bool_t(truthy(lhs) && truthy(rhs));
}

[[nodiscard]] inline constexpr bool_t logical_and(const int_t &lhs, const float_t &rhs) noexcept {
	return bool_t(truthy(lhs) && truthy(rhs));
}

[[nodiscard]] inline constexpr bool_t logical_and(const float_t &lhs, const int_t &rhs) noexcept {
	return bool_t(truthy(lhs) && truthy(rhs));
}

[[nodiscard]] inline constexpr bool_t logical_and(const float_t &lhs, const float_t &rhs) noexcept {
	return bool_t(truthy(lhs) && truthy(rhs));
}

[[nodiscard]] inline constexpr bool_t logical_or(const bool_t &lhs, const bool_t &rhs) noexcept {
	return bool_t(lhs.native_value() || rhs.native_value());
}

[[nodiscard]] inline constexpr bool_t logical_or(const int_t &lhs, const int_t &rhs) noexcept {
	return bool_t(truthy(lhs) || truthy(rhs));
}

[[nodiscard]] inline constexpr bool_t logical_or(const int_t &lhs, const float_t &rhs) noexcept {
	return bool_t(truthy(lhs) || truthy(rhs));
}

[[nodiscard]] inline constexpr bool_t logical_or(const float_t &lhs, const int_t &rhs) noexcept {
	return bool_t(truthy(lhs) || truthy(rhs));
}

[[nodiscard]] inline constexpr bool_t logical_or(const float_t &lhs, const float_t &rhs) noexcept {
	return bool_t(truthy(lhs) || truthy(rhs));
}

inline int_t &prefix_inc(int_t &value) noexcept {
	value = add(value, int_t(1));
	return value;
}

[[nodiscard]] inline int_t postfix_inc(int_t &value) noexcept {
	const int_t snapshot(value);
	prefix_inc(value);
	return snapshot;
}

inline int_t &prefix_dec(int_t &value) noexcept {
	value = sub(value, int_t(1));
	return value;
}

[[nodiscard]] inline int_t postfix_dec(int_t &value) noexcept {
	const int_t snapshot(value);
	prefix_dec(value);
	return snapshot;
}

inline float_t &prefix_inc(float_t &value) noexcept {
	value = add(value, float_t(1.0));
	return value;
}

[[nodiscard]] inline float_t postfix_inc(float_t &value) noexcept {
	const float_t snapshot(value);
	prefix_inc(value);
	return snapshot;
}

inline float_t &prefix_dec(float_t &value) noexcept {
	value = sub(value, float_t(1.0));
	return value;
}

[[nodiscard]] inline float_t postfix_dec(float_t &value) noexcept {
	const float_t snapshot(value);
	prefix_dec(value);
	return snapshot;
}

template <typename T>
[[nodiscard]] inline constexpr bool_t ptr_eq_null(const shared_p<T> &value) noexcept {
	return bool_t(value.native_value() == nullptr);
}

template <typename T>
[[nodiscard]] inline constexpr bool_t ptr_eq_null(const unique_p<T> &value) noexcept {
	return bool_t(value.native_value() == nullptr);
}

template <typename T>
[[nodiscard]] inline constexpr bool_t ptr_eq_null(const weak_p<T> &value) noexcept {
	return bool_t(value.native_value().expired());
}

template <typename T>
[[nodiscard]] inline constexpr bool_t ptr_ne_null(const T &value) noexcept {
	return bool_t(!ptr_eq_null(value).native_value());
}

template <typename T>
[[nodiscard]] inline bool_t ptr_eq_same(const shared_p<T> &lhs, const shared_p<T> &rhs) noexcept {
	return bool_t(lhs.native_value() == rhs.native_value());
}

template <typename T>
[[nodiscard]] inline bool_t ptr_ne_same(const shared_p<T> &lhs, const shared_p<T> &rhs) noexcept {
	return bool_t(lhs.native_value() != rhs.native_value());
}

template <typename T>
[[nodiscard]] inline bool_t weak_eq_same(const weak_p<T> &lhs, const weak_p<T> &rhs) noexcept {
	const auto &lv = lhs.native_value();
	const auto &rv = rhs.native_value();
	return bool_t(!lv.owner_before(rv) && !rv.owner_before(lv));
}

template <typename T>
[[nodiscard]] inline bool_t weak_ne_same(const weak_p<T> &lhs, const weak_p<T> &rhs) noexcept {
	return bool_t(!weak_eq_same(lhs, rhs).native_value());
}

template <typename T>
[[nodiscard]] inline constexpr bool_t nullable_eq_null(const nullable<T> &value) noexcept {
	return bool_t(!value.native_value().has_value());
}

template <typename T>
[[nodiscard]] inline constexpr bool_t nullable_ne_null(const nullable<T> &value) noexcept {
	return bool_t(value.native_value().has_value());
}

template <typename T>
[[nodiscard]] inline bool_t nullable_eq_same(const nullable<T> &lhs, const nullable<T> &rhs) {
	if (!lhs.native_value().has_value() && !rhs.native_value().has_value()) {
		return bool_t(true);
	}
	if (lhs.native_value().has_value() != rhs.native_value().has_value()) {
		return bool_t(false);
	}
	return lhs.native_value().value() == rhs.native_value().value();
}

template <typename T>
[[nodiscard]] inline bool_t nullable_ne_same(const nullable<T> &lhs, const nullable<T> &rhs) {
	return bool_t(!nullable_eq_same(lhs, rhs).native_value());
}

} // namespace scpp::detail::generated_operator_detail
