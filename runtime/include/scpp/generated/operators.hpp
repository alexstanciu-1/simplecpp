#pragma once

#include <concepts>

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/result_or_bool.hpp"
#include "scpp/error_t.hpp"
#include "scpp/result.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/weak_p.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/cast.hpp"
#include "scpp/generated/operator_detail.hpp"

namespace scpp {

// Generated from runtime/specs/config.json on 2026-04-04T05:33:02+00:00.
// Enabled config families: bool_logical, float_arithmetic, float_logical, float_mutation, int_arithmetic, int_bitwise_and_mutation, int_logical, mixed_numeric, mixed_numeric_logical, null_comparisons, nullable_ops, pointer_null_comparisons, string_ops, table_identity_comparisons.
// Do not edit manually.

template <typename T>
concept is_bool = std::same_as<detail::remove_cvref_t<T>, bool_t>;

template <typename T>
concept is_native_int = std::same_as<detail::remove_cvref_t<T>, int_t>;

template <typename T>
concept is_native_float = std::same_as<detail::remove_cvref_t<T>, float_t>;

template <typename T>
concept is_native_number = is_native_int<T> || is_native_float<T>;

template <typename T>
concept is_string_like = std::same_as<detail::remove_cvref_t<T>, string_t>;

template <typename T>
concept is_mixed = std::same_as<detail::remove_cvref_t<T>, mixed_t>;

template <typename T>
concept is_mixed_compatible =
	is_bool<T> ||
	is_native_int<T> ||
	is_native_float<T> ||
	is_string_like<T> ||
	is_mixed<T>;

template <typename T>
concept is_nullable = detail::is_specialization_of_v<detail::remove_cvref_t<T>, nullable>;

template <typename T>
concept is_result_or_false = detail::is_specialization_of_v<detail::remove_cvref_t<T>, result_or_false>;

template <typename T>
concept is_result_or_bool = detail::is_specialization_of_v<detail::remove_cvref_t<T>, result_or_bool>;

template <typename T>
concept is_result = detail::is_specialization_of_v<detail::remove_cvref_t<T>, result>;

template <typename T>
concept is_guarded_value = is_nullable<T> || is_result_or_false<T> || is_result_or_bool<T> || is_result<T>;

template <typename T>
struct unwrap_nullable_type {
	using type = detail::remove_cvref_t<T>;
};

template <typename T>
struct unwrap_nullable_type<nullable<T>> {
	using type = T;
};

template <typename T>
struct unwrap_nullable_type<result_or_false<T>> {
	using type = T;
};

template <typename T>
struct unwrap_nullable_type<result_or_bool<T>> {
	using type = T;
};

template <typename T>
struct unwrap_nullable_type<result<T>> {
	using type = T;
};

template <typename T>
using unwrap_nullable_type_t = typename unwrap_nullable_type<detail::remove_cvref_t<T>>::type;

template <typename T>
[[nodiscard]] inline decltype(auto) require_nullable_lifted_value(T &&value, const char *context) {
	if constexpr (is_guarded_value<T>) {
		return std::forward<T>(value).require_value(context);
	} else {
		return std::forward<T>(value);
	}
}

template <typename T>
concept nullable_unary_plus_operand = is_guarded_value<T> && requires(const unwrap_nullable_type_t<T> &value) { +value; };

template <typename T>
concept nullable_unary_minus_operand = is_guarded_value<T> && requires(const unwrap_nullable_type_t<T> &value) { -value; };

template <typename T>
concept nullable_logical_not_operand = is_guarded_value<T> && requires(const unwrap_nullable_type_t<T> &value) { !value; };

template <typename T>
concept nullable_bitwise_not_operand = is_guarded_value<T> && requires(const unwrap_nullable_type_t<T> &value) { ~value; };

template <typename T>
concept nullable_preincrement_operand = is_guarded_value<T> && requires(unwrap_nullable_type_t<T> &value) { ++value; };

template <typename T>
concept nullable_predecrement_operand = is_guarded_value<T> && requires(unwrap_nullable_type_t<T> &value) { --value; };

template <typename L, typename R>
concept nullable_binary_plus_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs + rhs; };

template <typename L, typename R>
concept nullable_binary_minus_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs - rhs; };

template <typename L, typename R>
concept nullable_binary_mul_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs * rhs; };

template <typename L, typename R>
concept nullable_binary_div_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs / rhs; };

template <typename L, typename R>
concept nullable_binary_mod_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs % rhs; };

template <typename L, typename R>
concept nullable_binary_bitand_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs & rhs; };

template <typename L, typename R>
concept nullable_binary_bitor_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs | rhs; };

template <typename L, typename R>
concept nullable_binary_bitxor_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs ^ rhs; };

template <typename L, typename R>
concept nullable_binary_shl_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs << rhs; };

template <typename L, typename R>
concept nullable_binary_shr_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs >> rhs; };

template <typename L, typename R>
concept nullable_binary_eq_operand = (is_guarded_value<L> || is_guarded_value<R>) && !(std::same_as<unwrap_nullable_type_t<L>, null_t> || std::same_as<unwrap_nullable_type_t<R>, null_t> || std::same_as<unwrap_nullable_type_t<L>, nullopt_t> || std::same_as<unwrap_nullable_type_t<R>, nullopt_t>);

template <typename L, typename R>
concept nullable_binary_lt_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs < rhs; };

template <typename L, typename R>
concept nullable_binary_le_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs <= rhs; };

template <typename L, typename R>
concept nullable_binary_gt_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs > rhs; };

template <typename L, typename R>
concept nullable_binary_ge_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs >= rhs; };

template <typename L, typename R>
concept nullable_binary_land_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs && rhs; };

template <typename L, typename R>
concept nullable_binary_lor_operand = (is_guarded_value<L> || is_guarded_value<R>) && requires(const unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs || rhs; };

template <typename L, typename R>
concept nullable_compound_add_operand = is_guarded_value<L> && requires(unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs += rhs; };

template <typename L, typename R>
concept nullable_compound_sub_operand = is_guarded_value<L> && requires(unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs -= rhs; };

template <typename L, typename R>
concept nullable_compound_mul_operand = is_guarded_value<L> && requires(unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs *= rhs; };

template <typename L, typename R>
concept nullable_compound_div_operand = is_guarded_value<L> && requires(unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs /= rhs; };

template <typename L, typename R>
concept nullable_compound_mod_operand = is_guarded_value<L> && requires(unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs %= rhs; };

template <typename L, typename R>
concept nullable_compound_bitand_operand = is_guarded_value<L> && requires(unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs &= rhs; };

template <typename L, typename R>
concept nullable_compound_bitor_operand = is_guarded_value<L> && requires(unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs |= rhs; };

template <typename L, typename R>
concept nullable_compound_bitxor_operand = is_guarded_value<L> && requires(unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs ^= rhs; };

template <typename L, typename R>
concept nullable_compound_shl_operand = is_guarded_value<L> && requires(unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs <<= rhs; };

template <typename L, typename R>
concept nullable_compound_shr_operand = is_guarded_value<L> && requires(unwrap_nullable_type_t<L> &lhs, const unwrap_nullable_type_t<R> &rhs) { lhs >>= rhs; };

template <typename T>
	requires (is_native_number<T> || is_mixed<T>)
[[nodiscard]] inline auto operator+(const T &value) {
	using base_t = detail::remove_cvref_t<T>;
	if constexpr (is_mixed<base_t>) {
		return +mixed_t(value);
	} else if constexpr (is_native_int<base_t>) {
		return detail::generated_operator_detail::unary_plus(value);
	} else if constexpr (is_native_float<base_t>) {
		return detail::generated_operator_detail::unary_plus(value);
	} else {
		static_assert(detail::always_false_v<base_t>, "unsupported unary operator+");
	}
}

template <typename T>
	requires (is_native_number<T> || is_mixed<T>)
[[nodiscard]] inline auto operator-(const T &value) {
	using base_t = detail::remove_cvref_t<T>;
	if constexpr (is_mixed<base_t>) {
		return -mixed_t(value);
	} else if constexpr (is_native_int<base_t>) {
		return detail::generated_operator_detail::unary_minus(value);
	} else if constexpr (is_native_float<base_t>) {
		return detail::generated_operator_detail::unary_minus(value);
	} else {
		static_assert(detail::always_false_v<base_t>, "unsupported unary operator-");
	}
}

template <typename T>
	requires (is_bool<T> || is_native_number<T> || is_mixed<T>)
[[nodiscard]] inline auto operator!(const T &value) {
	using base_t = detail::remove_cvref_t<T>;
	if constexpr (is_mixed<base_t>) {
		return !mixed_t(value);
	} else {
		return bool_t(!detail::generated_operator_detail::truthy(value));
	}
}

template <typename T>
	requires (is_native_int<T> || is_mixed<T>)
[[nodiscard]] inline auto operator~(const T &value) {
	using base_t = detail::remove_cvref_t<T>;
	if constexpr (is_mixed<base_t>) {
		return ~mixed_t(value);
	} else {
		return detail::generated_operator_detail::bitwise_not(value);
	}
}

inline int_t &operator++(int_t &value) noexcept {
	return detail::generated_operator_detail::prefix_inc(value);
}

inline int_t operator++(int_t &value, int) noexcept {
	return detail::generated_operator_detail::postfix_inc(value);
}

inline int_t &operator--(int_t &value) noexcept {
	return detail::generated_operator_detail::prefix_dec(value);
}

inline int_t operator--(int_t &value, int) noexcept {
	return detail::generated_operator_detail::postfix_dec(value);
}

inline float_t &operator++(float_t &value) noexcept {
	return detail::generated_operator_detail::prefix_inc(value);
}

inline float_t operator++(float_t &value, int) noexcept {
	return detail::generated_operator_detail::postfix_inc(value);
}

inline float_t &operator--(float_t &value) noexcept {
	return detail::generated_operator_detail::prefix_dec(value);
}

inline float_t operator--(float_t &value, int) noexcept {
	return detail::generated_operator_detail::postfix_dec(value);
}

[[nodiscard]] inline string_t operator+(const string_t &lhs, const string_t &rhs) {
	return detail::generated_operator_detail::concat(lhs, rhs);
}

template <typename T1, typename T2>
	requires ((is_native_number<T1> || is_mixed<T1>) && (is_native_number<T2> || is_mixed<T2>))
[[nodiscard]] inline auto operator+(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) + mixed_t(rhs);
	} else {
		return detail::generated_operator_detail::add(lhs, rhs);
	}
}

template <typename T1, typename T2>
	requires ((is_native_number<T1> || is_mixed<T1>) && (is_native_number<T2> || is_mixed<T2>))
[[nodiscard]] inline auto operator-(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) - mixed_t(rhs);
	} else {
		return detail::generated_operator_detail::sub(lhs, rhs);
	}
}

template <typename T1, typename T2>
	requires ((is_native_number<T1> || is_mixed<T1>) && (is_native_number<T2> || is_mixed<T2>))
[[nodiscard]] inline auto operator*(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) * mixed_t(rhs);
	} else {
		return detail::generated_operator_detail::mul(lhs, rhs);
	}
}

template <typename T1, typename T2>
	requires ((is_native_number<T1> || is_mixed<T1>) && (is_native_number<T2> || is_mixed<T2>))
[[nodiscard]] inline auto operator/(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) / mixed_t(rhs);
	} else {
		return detail::generated_operator_detail::div(lhs, rhs);
	}
}

template <typename T1, typename T2>
	requires ((is_native_int<T1> || is_mixed<T1>) && (is_native_int<T2> || is_mixed<T2>))
[[nodiscard]] inline auto operator%(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) % mixed_t(rhs);
	} else {
		return detail::generated_operator_detail::mod(lhs, rhs);
	}
}

template <typename T1, typename T2>
	requires ((is_native_int<T1> || is_mixed<T1>) && (is_native_int<T2> || is_mixed<T2>))
[[nodiscard]] inline auto operator&(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) & mixed_t(rhs);
	} else {
		return detail::generated_operator_detail::bit_and(lhs, rhs);
	}
}

template <typename T1, typename T2>
	requires ((is_native_int<T1> || is_mixed<T1>) && (is_native_int<T2> || is_mixed<T2>))
[[nodiscard]] inline auto operator|(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) | mixed_t(rhs);
	} else {
		return detail::generated_operator_detail::bit_or(lhs, rhs);
	}
}

template <typename T1, typename T2>
	requires ((is_native_int<T1> || is_mixed<T1>) && (is_native_int<T2> || is_mixed<T2>))
[[nodiscard]] inline auto operator^(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) ^ mixed_t(rhs);
	} else {
		return detail::generated_operator_detail::bit_xor(lhs, rhs);
	}
}

template <typename T1, typename T2>
	requires ((is_native_int<T1> || is_mixed<T1>) && (is_native_int<T2> || is_mixed<T2>))
[[nodiscard]] inline auto operator<<(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) << mixed_t(rhs);
	} else {
		return detail::generated_operator_detail::shl(lhs, rhs);
	}
}

template <typename T1, typename T2>
	requires ((is_native_int<T1> || is_mixed<T1>) && (is_native_int<T2> || is_mixed<T2>))
[[nodiscard]] inline auto operator>>(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) >> mixed_t(rhs);
	} else {
		return detail::generated_operator_detail::shr(lhs, rhs);
	}
}

template <typename T1, typename T2>
	requires ((is_bool<T1> || is_native_number<T1> || is_string_like<T1> || is_mixed<T1>) &&
			  (is_bool<T2> || is_native_number<T2> || is_string_like<T2> || is_mixed<T2>))
[[nodiscard]] inline auto operator==(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) == mixed_t(rhs);
	} else if constexpr (is_bool<lhs_t> && is_bool<rhs_t>) {
		return detail::generated_operator_detail::eq(lhs, rhs);
	} else if constexpr (is_string_like<lhs_t> && is_string_like<rhs_t>) {
		return detail::generated_operator_detail::eq(lhs, rhs);
	} else if constexpr (is_native_number<lhs_t> && is_native_number<rhs_t>) {
		return detail::generated_operator_detail::eq(lhs, rhs);
	} else {
		static_assert(detail::always_false_v<lhs_t, rhs_t>, "unsupported operator== combination");
	}
}

template <typename T1, typename T2>
	requires ((is_bool<T1> || is_native_number<T1> || is_string_like<T1> || is_mixed<T1>) &&
			  (is_bool<T2> || is_native_number<T2> || is_string_like<T2> || is_mixed<T2>))
[[nodiscard]] inline auto operator!=(const T1 &lhs, const T2 &rhs) {
	return bool_t(!static_cast<bool>((lhs == rhs).native_value()));
}

template <typename T1, typename T2>
	requires (((is_native_number<T1> || is_string_like<T1>) || is_mixed<T1>) &&
			  ((is_native_number<T2> || is_string_like<T2>) || is_mixed<T2>))
[[nodiscard]] inline auto operator<(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) < mixed_t(rhs);
	} else if constexpr (is_string_like<lhs_t> && is_string_like<rhs_t>) {
		return detail::generated_operator_detail::lt(lhs, rhs);
	} else if constexpr (is_native_number<lhs_t> && is_native_number<rhs_t>) {
		return detail::generated_operator_detail::lt(lhs, rhs);
	} else {
		static_assert(detail::always_false_v<lhs_t, rhs_t>, "unsupported operator< combination");
	}
}

template <typename T1, typename T2>
	requires (((is_native_number<T1> || is_string_like<T1>) || is_mixed<T1>) &&
			  ((is_native_number<T2> || is_string_like<T2>) || is_mixed<T2>))
[[nodiscard]] inline auto operator<=(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) <= mixed_t(rhs);
	} else if constexpr (is_string_like<lhs_t> && is_string_like<rhs_t>) {
		return detail::generated_operator_detail::le(lhs, rhs);
	} else if constexpr (is_native_number<lhs_t> && is_native_number<rhs_t>) {
		return detail::generated_operator_detail::le(lhs, rhs);
	} else {
		static_assert(detail::always_false_v<lhs_t, rhs_t>, "unsupported operator<= combination");
	}
}

template <typename T1, typename T2>
	requires (((is_native_number<T1> || is_string_like<T1>) || is_mixed<T1>) &&
			  ((is_native_number<T2> || is_string_like<T2>) || is_mixed<T2>))
[[nodiscard]] inline auto operator>(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) > mixed_t(rhs);
	} else if constexpr (is_string_like<lhs_t> && is_string_like<rhs_t>) {
		return detail::generated_operator_detail::gt(lhs, rhs);
	} else if constexpr (is_native_number<lhs_t> && is_native_number<rhs_t>) {
		return detail::generated_operator_detail::gt(lhs, rhs);
	} else {
		static_assert(detail::always_false_v<lhs_t, rhs_t>, "unsupported operator> combination");
	}
}

template <typename T1, typename T2>
	requires (((is_native_number<T1> || is_string_like<T1>) || is_mixed<T1>) &&
			  ((is_native_number<T2> || is_string_like<T2>) || is_mixed<T2>))
[[nodiscard]] inline auto operator>=(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) >= mixed_t(rhs);
	} else if constexpr (is_string_like<lhs_t> && is_string_like<rhs_t>) {
		return detail::generated_operator_detail::ge(lhs, rhs);
	} else if constexpr (is_native_number<lhs_t> && is_native_number<rhs_t>) {
		return detail::generated_operator_detail::ge(lhs, rhs);
	} else {
		static_assert(detail::always_false_v<lhs_t, rhs_t>, "unsupported operator>= combination");
	}
}

template <typename T1, typename T2>
	requires ((is_bool<T1> || is_native_number<T1> || is_mixed<T1>) && (is_bool<T2> || is_native_number<T2> || is_mixed<T2>))
[[nodiscard]] inline auto operator&&(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) && mixed_t(rhs);
	} else if constexpr (is_bool<lhs_t> && is_bool<rhs_t>) {
		return detail::generated_operator_detail::logical_and(lhs, rhs);
	} else if constexpr (is_native_number<lhs_t> && is_native_number<rhs_t>) {
		return detail::generated_operator_detail::logical_and(lhs, rhs);
	} else {
		static_assert(detail::always_false_v<lhs_t, rhs_t>, "unsupported operator&& combination");
	}
}

template <typename T1, typename T2>
	requires ((is_bool<T1> || is_native_number<T1> || is_mixed<T1>) && (is_bool<T2> || is_native_number<T2> || is_mixed<T2>))
[[nodiscard]] inline auto operator||(const T1 &lhs, const T2 &rhs) {
	using lhs_t = detail::remove_cvref_t<T1>;
	using rhs_t = detail::remove_cvref_t<T2>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs) || mixed_t(rhs);
	} else if constexpr (is_bool<lhs_t> && is_bool<rhs_t>) {
		return detail::generated_operator_detail::logical_or(lhs, rhs);
	} else if constexpr (is_native_number<lhs_t> && is_native_number<rhs_t>) {
		return detail::generated_operator_detail::logical_or(lhs, rhs);
	} else {
		static_assert(detail::always_false_v<lhs_t, rhs_t>, "unsupported operator|| combination");
	}
}


template <typename T>
	requires nullable_unary_plus_operand<T>
[[nodiscard]] inline auto operator+(const T &value) {
	return +require_nullable_lifted_value(value, "nullable unary operator+ requires a present value");
}

template <typename T>
	requires nullable_unary_minus_operand<T>
[[nodiscard]] inline auto operator-(const T &value) {
	return -require_nullable_lifted_value(value, "nullable unary operator- requires a present value");
}

template <typename T>
	requires nullable_logical_not_operand<T>
[[nodiscard]] inline auto operator!(const T &value) {
	return !require_nullable_lifted_value(value, "nullable operator! requires a present value");
}

template <typename T>
	requires nullable_bitwise_not_operand<T>
[[nodiscard]] inline auto operator~(const T &value) {
	return ~require_nullable_lifted_value(value, "nullable operator~ requires a present value");
}

template <typename T>
	requires nullable_preincrement_operand<T>
inline auto &operator++(T &value) {
	++value.require_value("nullable prefix operator++ requires a present value");
	return value;
}

template <typename T>
	requires nullable_preincrement_operand<T>
inline auto operator++(T &value, int) {
	auto before = value.require_value("nullable postfix operator++ requires a present value");
	value.require_value("nullable postfix operator++ requires a present value")++;
	return before;
}

template <typename T>
	requires nullable_predecrement_operand<T>
inline auto &operator--(T &value) {
	--value.require_value("nullable prefix operator-- requires a present value");
	return value;
}

template <typename T>
	requires nullable_predecrement_operand<T>
inline auto operator--(T &value, int) {
	auto before = value.require_value("nullable postfix operator-- requires a present value");
	value.require_value("nullable postfix operator-- requires a present value")--;
	return before;
}

template <typename L, typename R>
	requires nullable_binary_plus_operand<L, R>
[[nodiscard]] inline auto operator+(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator+ requires a present left operand") + require_nullable_lifted_value(rhs, "nullable operator+ requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_minus_operand<L, R>
[[nodiscard]] inline auto operator-(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator- requires a present left operand") - require_nullable_lifted_value(rhs, "nullable operator- requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_mul_operand<L, R>
[[nodiscard]] inline auto operator*(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator* requires a present left operand") * require_nullable_lifted_value(rhs, "nullable operator* requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_div_operand<L, R>
[[nodiscard]] inline auto operator/(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator/ requires a present left operand") / require_nullable_lifted_value(rhs, "nullable operator/ requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_mod_operand<L, R>
[[nodiscard]] inline auto operator%(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator% requires a present left operand") % require_nullable_lifted_value(rhs, "nullable operator% requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_bitand_operand<L, R>
[[nodiscard]] inline auto operator&(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator& requires a present left operand") & require_nullable_lifted_value(rhs, "nullable operator& requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_bitor_operand<L, R>
[[nodiscard]] inline auto operator|(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator| requires a present left operand") | require_nullable_lifted_value(rhs, "nullable operator| requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_bitxor_operand<L, R>
[[nodiscard]] inline auto operator^(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator^ requires a present left operand") ^ require_nullable_lifted_value(rhs, "nullable operator^ requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_shl_operand<L, R>
[[nodiscard]] inline auto operator<<(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator<< requires a present left operand") << require_nullable_lifted_value(rhs, "nullable operator<< requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_shr_operand<L, R>
[[nodiscard]] inline auto operator>>(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator>> requires a present left operand") >> require_nullable_lifted_value(rhs, "nullable operator>> requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_eq_operand<L, R>
[[nodiscard]] inline bool_t operator==(const L &lhs, const R &rhs) {
	const bool lhs_is_present = [&]() {
		if constexpr (is_guarded_value<L>) {
			return lhs.has_value().native_value();
		} else {
			return true;
		}
	}();
	const bool rhs_is_present = [&]() {
		if constexpr (is_guarded_value<R>) {
			return rhs.has_value().native_value();
		} else {
			return true;
		}
	}();
	if (!lhs_is_present || !rhs_is_present) {
		return bool_t(lhs_is_present == rhs_is_present);
	}
	return require_nullable_lifted_value(lhs, "nullable operator== requires a present left operand") == require_nullable_lifted_value(rhs, "nullable operator== requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_eq_operand<L, R>
[[nodiscard]] inline bool_t operator!=(const L &lhs, const R &rhs) {
	return bool_t(!static_cast<bool>((lhs == rhs).native_value()));
}

template <typename L, typename R>
	requires nullable_binary_lt_operand<L, R>
[[nodiscard]] inline auto operator<(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator< requires a present left operand") < require_nullable_lifted_value(rhs, "nullable operator< requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_le_operand<L, R>
[[nodiscard]] inline auto operator<=(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator<= requires a present left operand") <= require_nullable_lifted_value(rhs, "nullable operator<= requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_gt_operand<L, R>
[[nodiscard]] inline auto operator>(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator> requires a present left operand") > require_nullable_lifted_value(rhs, "nullable operator> requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_ge_operand<L, R>
[[nodiscard]] inline auto operator>=(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator>= requires a present left operand") >= require_nullable_lifted_value(rhs, "nullable operator>= requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_land_operand<L, R>
[[nodiscard]] inline auto operator&&(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator&& requires a present left operand") && require_nullable_lifted_value(rhs, "nullable operator&& requires a present right operand");
}

template <typename L, typename R>
	requires nullable_binary_lor_operand<L, R>
[[nodiscard]] inline auto operator||(const L &lhs, const R &rhs) {
	return require_nullable_lifted_value(lhs, "nullable operator|| requires a present left operand") || require_nullable_lifted_value(rhs, "nullable operator|| requires a present right operand");
}

template <typename L, typename R>
	requires nullable_compound_add_operand<L, R>
inline L &operator+=(L &lhs, const R &rhs) {
	lhs.require_value("nullable operator+= requires a present left operand") += require_nullable_lifted_value(rhs, "nullable operator+= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires nullable_compound_sub_operand<L, R>
inline L &operator-=(L &lhs, const R &rhs) {
	lhs.require_value("nullable operator-= requires a present left operand") -= require_nullable_lifted_value(rhs, "nullable operator-= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires nullable_compound_mul_operand<L, R>
inline L &operator*=(L &lhs, const R &rhs) {
	lhs.require_value("nullable operator*= requires a present left operand") *= require_nullable_lifted_value(rhs, "nullable operator*= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires nullable_compound_div_operand<L, R>
inline L &operator/=(L &lhs, const R &rhs) {
	lhs.require_value("nullable operator/= requires a present left operand") /= require_nullable_lifted_value(rhs, "nullable operator/= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires nullable_compound_mod_operand<L, R>
inline L &operator%=(L &lhs, const R &rhs) {
	lhs.require_value("nullable operator%= requires a present left operand") %= require_nullable_lifted_value(rhs, "nullable operator%= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires nullable_compound_bitand_operand<L, R>
inline L &operator&=(L &lhs, const R &rhs) {
	lhs.require_value("nullable operator&= requires a present left operand") &= require_nullable_lifted_value(rhs, "nullable operator&= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires nullable_compound_bitor_operand<L, R>
inline L &operator|=(L &lhs, const R &rhs) {
	lhs.require_value("nullable operator|= requires a present left operand") |= require_nullable_lifted_value(rhs, "nullable operator|= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires nullable_compound_bitxor_operand<L, R>
inline L &operator^=(L &lhs, const R &rhs) {
	lhs.require_value("nullable operator^= requires a present left operand") ^= require_nullable_lifted_value(rhs, "nullable operator^= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires nullable_compound_shl_operand<L, R>
inline L &operator<<=(L &lhs, const R &rhs) {
	lhs.require_value("nullable operator<<= requires a present left operand") <<= require_nullable_lifted_value(rhs, "nullable operator<<= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires nullable_compound_shr_operand<L, R>
inline L &operator>>=(L &lhs, const R &rhs) {
	lhs.require_value("nullable operator>>= requires a present left operand") >>= require_nullable_lifted_value(rhs, "nullable operator>>= requires a present right operand");
	return lhs;
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const nullable<T> &value, null_t) noexcept {
	return detail::generated_operator_detail::nullable_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(null_t, const nullable<T> &value) noexcept {
	return detail::generated_operator_detail::nullable_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const nullable<T> &value, null_t) noexcept {
	return detail::generated_operator_detail::nullable_ne_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(null_t, const nullable<T> &value) noexcept {
	return detail::generated_operator_detail::nullable_ne_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const nullable<T> &value, nullopt_t) noexcept {
	return detail::generated_operator_detail::nullable_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(nullopt_t, const nullable<T> &value) noexcept {
	return detail::generated_operator_detail::nullable_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const nullable<T> &value, nullopt_t) noexcept {
	return detail::generated_operator_detail::nullable_ne_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(nullopt_t, const nullable<T> &value) noexcept {
	return detail::generated_operator_detail::nullable_ne_null(value);
}


template <typename T>
[[nodiscard]] inline bool_t operator==(const result_or_false<T> &value, false_sentinel_t) noexcept {
	return value.is_false();
}

template <typename T>
[[nodiscard]] inline bool_t operator==(false_sentinel_t, const result_or_false<T> &value) noexcept {
	return value.is_false();
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const result_or_false<T> &value, false_sentinel_t) noexcept {
	return bool_t(!value.is_false().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(false_sentinel_t, const result_or_false<T> &value) noexcept {
	return bool_t(!value.is_false().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const result_or_false<T> &value, const bool_t &rhs) noexcept {
	return rhs.native_value() ? bool_t(false) : value.is_false();
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const bool_t &lhs, const result_or_false<T> &value) noexcept {
	return lhs.native_value() ? bool_t(false) : value.is_false();
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const result_or_false<T> &value, const bool_t &rhs) noexcept {
	return bool_t(!static_cast<bool>((value == rhs).native_value()));
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const bool_t &lhs, const result_or_false<T> &value) noexcept {
	return bool_t(!static_cast<bool>((lhs == value).native_value()));
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const result_or_bool<T> &value, false_sentinel_t) noexcept {
	return value.is_false();
}

template <typename T>
[[nodiscard]] inline bool_t operator==(false_sentinel_t, const result_or_bool<T> &value) noexcept {
	return value.is_false();
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const result_or_bool<T> &value, false_sentinel_t) noexcept {
	return bool_t(!value.is_false().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(false_sentinel_t, const result_or_bool<T> &value) noexcept {
	return bool_t(!value.is_false().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const result_or_bool<T> &value, const bool_t &rhs) noexcept {
	return rhs.native_value() ? value.is_true() : value.is_false();
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const bool_t &lhs, const result_or_bool<T> &value) noexcept {
	return lhs.native_value() ? value.is_true() : value.is_false();
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const result_or_bool<T> &value, const bool_t &rhs) noexcept {
	return bool_t(!static_cast<bool>((value == rhs).native_value()));
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const bool_t &lhs, const result_or_bool<T> &value) noexcept {
	return bool_t(!static_cast<bool>((lhs == value).native_value()));
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const result<T> &value, error_sentinel_t) noexcept {
	return bool_t(!value.has_value().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator==(error_sentinel_t, const result<T> &value) noexcept {
	return bool_t(!value.has_value().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const result<T> &value, error_sentinel_t) noexcept {
	return bool_t(value.has_value().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(error_sentinel_t, const result<T> &value) noexcept {
	return bool_t(value.has_value().native_value());
}


template <typename T>
[[nodiscard]] inline bool_t operator==(const shared_p<T> &value, null_t) noexcept {
	return detail::generated_operator_detail::ptr_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(null_t, const shared_p<T> &value) noexcept {
	return detail::generated_operator_detail::ptr_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const shared_p<T> &value, null_t) noexcept {
	return detail::generated_operator_detail::ptr_ne_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(null_t, const shared_p<T> &value) noexcept {
	return detail::generated_operator_detail::ptr_ne_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const shared_p<T> &value, nullptr_t) noexcept {
	return detail::generated_operator_detail::ptr_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(nullptr_t, const shared_p<T> &value) noexcept {
	return detail::generated_operator_detail::ptr_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const shared_p<T> &value, nullptr_t) noexcept {
	return detail::generated_operator_detail::ptr_ne_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(nullptr_t, const shared_p<T> &value) noexcept {
	return detail::generated_operator_detail::ptr_ne_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const shared_p<T> &lhs, const shared_p<T> &rhs) noexcept {
	return detail::generated_operator_detail::ptr_eq_same(lhs, rhs);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const shared_p<T> &lhs, const shared_p<T> &rhs) noexcept {
	return detail::generated_operator_detail::ptr_ne_same(lhs, rhs);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const unique_p<T> &value, null_t) noexcept {
	return detail::generated_operator_detail::ptr_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(null_t, const unique_p<T> &value) noexcept {
	return detail::generated_operator_detail::ptr_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const unique_p<T> &value, null_t) noexcept {
	return detail::generated_operator_detail::ptr_ne_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(null_t, const unique_p<T> &value) noexcept {
	return detail::generated_operator_detail::ptr_ne_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const unique_p<T> &value, nullptr_t) noexcept {
	return detail::generated_operator_detail::ptr_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(nullptr_t, const unique_p<T> &value) noexcept {
	return detail::generated_operator_detail::ptr_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const unique_p<T> &value, nullptr_t) noexcept {
	return detail::generated_operator_detail::ptr_ne_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(nullptr_t, const unique_p<T> &value) noexcept {
	return detail::generated_operator_detail::ptr_ne_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const weak_p<T> &value, null_t) noexcept {
	return detail::generated_operator_detail::ptr_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(null_t, const weak_p<T> &value) noexcept {
	return detail::generated_operator_detail::ptr_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const weak_p<T> &value, null_t) noexcept {
	return detail::generated_operator_detail::ptr_ne_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(null_t, const weak_p<T> &value) noexcept {
	return detail::generated_operator_detail::ptr_ne_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const weak_p<T> &value, nullptr_t) noexcept {
	return detail::generated_operator_detail::ptr_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(nullptr_t, const weak_p<T> &value) noexcept {
	return detail::generated_operator_detail::ptr_eq_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const weak_p<T> &value, nullptr_t) noexcept {
	return detail::generated_operator_detail::ptr_ne_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(nullptr_t, const weak_p<T> &value) noexcept {
	return detail::generated_operator_detail::ptr_ne_null(value);
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const weak_p<hash_t<T>> &lhs, const weak_p<hash_t<T>> &rhs) noexcept {
	return detail::generated_operator_detail::weak_eq_same(lhs, rhs);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const weak_p<hash_t<T>> &lhs, const weak_p<hash_t<T>> &rhs) noexcept {
	return detail::generated_operator_detail::weak_ne_same(lhs, rhs);
}

inline int_t &operator+=(int_t &lhs, const int_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::add(lhs, rhs);
	return lhs;
}

inline int_t &operator-=(int_t &lhs, const int_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::sub(lhs, rhs);
	return lhs;
}

inline int_t &operator*=(int_t &lhs, const int_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::mul(lhs, rhs);
	return lhs;
}

inline int_t &operator/=(int_t &lhs, const int_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::div(lhs, rhs);
	return lhs;
}

inline int_t &operator%=(int_t &lhs, const int_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::mod(lhs, rhs);
	return lhs;
}

inline int_t &operator&=(int_t &lhs, const int_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::bit_and(lhs, rhs);
	return lhs;
}

inline int_t &operator|=(int_t &lhs, const int_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::bit_or(lhs, rhs);
	return lhs;
}

inline int_t &operator^=(int_t &lhs, const int_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::bit_xor(lhs, rhs);
	return lhs;
}

inline int_t &operator<<=(int_t &lhs, const int_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::shl(lhs, rhs);
	return lhs;
}

inline int_t &operator>>=(int_t &lhs, const int_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::shr(lhs, rhs);
	return lhs;
}

inline int_t &operator+=(int_t &lhs, const mixed_t &rhs) {
	lhs = cast<int_t>(mixed_t(lhs) + rhs);
	return lhs;
}

inline int_t &operator-=(int_t &lhs, const mixed_t &rhs) {
	lhs = cast<int_t>(mixed_t(lhs) - rhs);
	return lhs;
}

inline int_t &operator*=(int_t &lhs, const mixed_t &rhs) {
	lhs = cast<int_t>(mixed_t(lhs) * rhs);
	return lhs;
}

inline int_t &operator/=(int_t &lhs, const mixed_t &rhs) {
	lhs = cast<int_t>(mixed_t(lhs) / rhs);
	return lhs;
}

inline int_t &operator%=(int_t &lhs, const mixed_t &rhs) {
	lhs = cast<int_t>(mixed_t(lhs) % rhs);
	return lhs;
}

inline int_t &operator&=(int_t &lhs, const mixed_t &rhs) {
	lhs = cast<int_t>(mixed_t(lhs) & rhs);
	return lhs;
}

inline int_t &operator|=(int_t &lhs, const mixed_t &rhs) {
	lhs = cast<int_t>(mixed_t(lhs) | rhs);
	return lhs;
}

inline int_t &operator^=(int_t &lhs, const mixed_t &rhs) {
	lhs = cast<int_t>(mixed_t(lhs) ^ rhs);
	return lhs;
}

inline int_t &operator<<=(int_t &lhs, const mixed_t &rhs) {
	lhs = cast<int_t>(mixed_t(lhs) << rhs);
	return lhs;
}

inline int_t &operator>>=(int_t &lhs, const mixed_t &rhs) {
	lhs = cast<int_t>(mixed_t(lhs) >> rhs);
	return lhs;
}

inline float_t &operator+=(float_t &lhs, const int_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::add(lhs, rhs);
	return lhs;
}

inline float_t &operator+=(float_t &lhs, const float_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::add(lhs, rhs);
	return lhs;
}

inline float_t &operator-=(float_t &lhs, const int_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::sub(lhs, rhs);
	return lhs;
}

inline float_t &operator-=(float_t &lhs, const float_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::sub(lhs, rhs);
	return lhs;
}

inline float_t &operator*=(float_t &lhs, const int_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::mul(lhs, rhs);
	return lhs;
}

inline float_t &operator*=(float_t &lhs, const float_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::mul(lhs, rhs);
	return lhs;
}

inline float_t &operator/=(float_t &lhs, const int_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::div(lhs, rhs);
	return lhs;
}

inline float_t &operator/=(float_t &lhs, const float_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::div(lhs, rhs);
	return lhs;
}

inline float_t &operator+=(float_t &lhs, const mixed_t &rhs) {
	lhs = cast<float_t>(mixed_t(lhs) + rhs);
	return lhs;
}

inline float_t &operator-=(float_t &lhs, const mixed_t &rhs) {
	lhs = cast<float_t>(mixed_t(lhs) - rhs);
	return lhs;
}

inline float_t &operator*=(float_t &lhs, const mixed_t &rhs) {
	lhs = cast<float_t>(mixed_t(lhs) * rhs);
	return lhs;
}

inline float_t &operator/=(float_t &lhs, const mixed_t &rhs) {
	lhs = cast<float_t>(mixed_t(lhs) / rhs);
	return lhs;
}

inline string_t &operator+=(string_t &lhs, const string_t &rhs) {
	lhs = detail::generated_operator_detail::concat(lhs, rhs);
	return lhs;
}

} // namespace scpp
