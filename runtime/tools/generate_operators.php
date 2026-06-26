<?php

declare(strict_types=1);

$runtime_root = dirname(__DIR__);
$config_path = $runtime_root . '/specs/config.json';
$output_path = $runtime_root . '/include/scpp/generated/operators.hpp';

if (!is_file($config_path)) {
	fwrite(STDERR, "Missing config: {$config_path}\n");
	exit(1);
}

$config = json_decode(file_get_contents($config_path), true, 512, JSON_THROW_ON_ERROR);
if (!is_array($config)) {
	fwrite(STDERR, "Config is not an object.\n");
	exit(1);
}

$enabled_families = [];
$symbols = [];
$unary_symbols = [];
$binary_symbols = [];
foreach (($config['overload_families'] ?? []) as $family) {
	if (!is_array($family) || empty($family['enabled'])) {
		continue;
	}
	$family_name = (string)($family['name'] ?? 'unknown');
	$enabled_families[$family_name] = true;
	foreach (($family['operators'] ?? []) as $operator) {
		if (!is_array($operator)) {
			continue;
		}
		$symbol = (string)($operator['symbol'] ?? '');
		$arity = (int)($operator['arity'] ?? 2);
		if ($symbol === '') {
			continue;
		}
		$symbols[$symbol] = true;
		if ($arity === 1) {
			$unary_symbols[$symbol] = true;
		} elseif ($arity === 2) {
			$binary_symbols[$symbol] = true;
		}
	}
}

$compound_symbols = [];
foreach (($config['compound_assignment_policy']['supported_symbols'] ?? []) as $symbol) {
	$compound_symbols[(string)$symbol] = true;
}

ksort($enabled_families);
ksort($symbols);
ksort($unary_symbols);
ksort($binary_symbols);
ksort($compound_symbols);

$header = [];
$emit = static function (string $code) use (&$header): void {
	$header[] = rtrim($code, "\n");
};

$stamp = gmdate('c');
$emit(<<<CPP
#pragma once

#include <concepts>
#include <type_traits>

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
#include "scpp/result.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/result_or_bool.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/weak_p.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/cast.hpp"
#include "scpp/generated/operator_detail.hpp"

namespace scpp {

// Generated from runtime/specs/config.json on {$stamp}.
// Enabled config families: @{families}@.
// Do not edit manually.

template <typename T>
concept is_bool = std::same_as<detail::remove_cvref_t<T>, bool_t>;

template <typename T>
concept is_native_int = detail::is_int_t_v<T>;

template <typename T>
concept is_default_int = std::same_as<detail::remove_cvref_t<T>, int_t<>>;

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
	is_default_int<T> ||
	is_native_float<T> ||
	is_string_like<T> ||
	is_mixed<T>;

template <typename T>
struct lifted_compound_wrapper_traits;

template <typename T>
struct lifted_compound_wrapper_traits<nullable<T>> {
	using inner_t = T;

	static T &require(nullable<T> &value, const char *context) {
		return value.require_value(context);
	}

	static const T &require(const nullable<T> &value, const char *context) {
		return value.require_value(context);
	}
};

template <typename T>
struct lifted_compound_wrapper_traits<result<T>> {
	using inner_t = T;

	static T &require(result<T> &value, const char *context) {
		return value.require_value(context);
	}

	static const T &require(const result<T> &value, const char *context) {
		return value.require_value(context);
	}
};

template <typename T>
struct lifted_compound_wrapper_traits<result_or_false<T>> {
	using inner_t = T;

	static T &require(result_or_false<T> &value, const char *context) {
		return value.require_value(context);
	}

	static const T &require(const result_or_false<T> &value, const char *context) {
		return value.require_value(context);
	}
};

template <typename T>
struct lifted_compound_wrapper_traits<result_or_bool<T>> {
	using inner_t = T;

	static T &require(result_or_bool<T> &value, const char *context) {
		return value.require_value(context);
	}

	static const T &require(const result_or_bool<T> &value, const char *context) {
		return value.require_value(context);
	}
};

template <typename T>
concept is_lifted_compound_wrapper = requires {
	typename lifted_compound_wrapper_traits<detail::remove_cvref_t<T>>::inner_t;
};

template <typename T>
using lifted_compound_inner_t = typename lifted_compound_wrapper_traits<detail::remove_cvref_t<T>>::inner_t;

template <typename T>
decltype(auto) require_lifted_compound_value(T &value, const char *context) {
	if constexpr (is_lifted_compound_wrapper<T>) {
		return lifted_compound_wrapper_traits<detail::remove_cvref_t<T>>::require(value, context);
	} else {
		return (value);
	}
}

template <typename T>
decltype(auto) require_lifted_compound_value(const T &value, const char *context) {
	if constexpr (is_lifted_compound_wrapper<T>) {
		return lifted_compound_wrapper_traits<detail::remove_cvref_t<T>>::require(value, context);
	} else {
		return (value);
	}
}

template <typename T>
concept is_lifted_bitwise_operand =
	(is_native_int<T> || is_mixed<T>)
	|| (is_lifted_compound_wrapper<T> && (is_native_int<lifted_compound_inner_t<T>> || is_mixed<lifted_compound_inner_t<T>>));

template <typename T>
concept is_lifted_logical_operand =
	(is_bool<T> || is_native_number<T> || is_mixed<T>)
	|| (is_lifted_compound_wrapper<T> && (is_bool<lifted_compound_inner_t<T>> || is_native_number<lifted_compound_inner_t<T>> || is_mixed<lifted_compound_inner_t<T>>));

template <typename T>
concept is_lifted_ordering_operand =
	(is_native_number<T> || is_string_like<T> || is_mixed<T>)
	|| (is_lifted_compound_wrapper<T> && (is_native_number<lifted_compound_inner_t<T>> || is_string_like<lifted_compound_inner_t<T>> || is_mixed<lifted_compound_inner_t<T>>));

template <typename T>
concept is_lifted_equality_operand =
	(is_bool<T> || is_native_number<T> || is_string_like<T> || is_mixed<T>)
	|| (is_lifted_compound_wrapper<T> && (is_bool<lifted_compound_inner_t<T>> || is_native_number<lifted_compound_inner_t<T>> || is_string_like<lifted_compound_inner_t<T>> || is_mixed<lifted_compound_inner_t<T>>));

template <typename T>
concept is_lifted_arithmetic_operand =
	(is_native_number<T> || is_mixed<T>)
	|| (is_lifted_compound_wrapper<T> && (is_native_number<lifted_compound_inner_t<T>> || is_mixed<lifted_compound_inner_t<T>>));

template <typename T>
concept is_lifted_modulo_operand =
	(is_native_int<T> || is_mixed<T>)
	|| (is_lifted_compound_wrapper<T> && (is_native_int<lifted_compound_inner_t<T>> || is_mixed<lifted_compound_inner_t<T>>));

CPP);
$header[0] = str_replace('@{families}@', implode(', ', array_keys($enabled_families)), $header[0]);

$emit(<<<'CPP'
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
	requires is_lifted_bitwise_operand<T>
[[nodiscard]] inline auto operator~(const T &value) {
	const auto &inner_value = require_lifted_compound_value(value, "lifted unary operator~ requires a present operand");
	using inner_t = detail::remove_cvref_t<decltype(inner_value)>;
	if constexpr (is_mixed<inner_t>) {
		return ~mixed_t(inner_value);
	} else {
		return detail::generated_operator_detail::bitwise_not(inner_value);
	}
}

template <typename Rep>
inline int_t<Rep> &operator++(int_t<Rep> &value) noexcept {
	return detail::generated_operator_detail::prefix_inc(value);
}

template <typename Rep>
inline int_t<Rep> operator++(int_t<Rep> &value, int) noexcept {
	return detail::generated_operator_detail::postfix_inc(value);
}

template <typename Rep>
inline int_t<Rep> &operator--(int_t<Rep> &value) noexcept {
	return detail::generated_operator_detail::prefix_dec(value);
}

template <typename Rep>
inline int_t<Rep> operator--(int_t<Rep> &value, int) noexcept {
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

CPP);

$emit(<<<'CPP'
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
	requires (is_lifted_arithmetic_operand<T1> && is_lifted_arithmetic_operand<T2>)
[[nodiscard]] inline auto operator+(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted arithmetic operator+ requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted arithmetic operator+ requires a present right operand");
	return lhs_value + rhs_value;
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
	requires (is_lifted_arithmetic_operand<T1> && is_lifted_arithmetic_operand<T2>)
[[nodiscard]] inline auto operator-(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted arithmetic operator- requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted arithmetic operator- requires a present right operand");
	return lhs_value - rhs_value;
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
	requires (is_lifted_arithmetic_operand<T1> && is_lifted_arithmetic_operand<T2>)
[[nodiscard]] inline auto operator*(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted arithmetic operator* requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted arithmetic operator* requires a present right operand");
	return lhs_value * rhs_value;
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
	requires (is_lifted_arithmetic_operand<T1> && is_lifted_arithmetic_operand<T2>)
[[nodiscard]] inline auto operator/(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted arithmetic operator/ requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted arithmetic operator/ requires a present right operand");
	return lhs_value / rhs_value;
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
	requires (is_lifted_modulo_operand<T1> && is_lifted_modulo_operand<T2>)
[[nodiscard]] inline auto operator%(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted arithmetic operator% requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted arithmetic operator% requires a present right operand");
	return lhs_value % rhs_value;
}

template <typename T1, typename T2>
	requires (is_lifted_bitwise_operand<T1> && is_lifted_bitwise_operand<T2>)
[[nodiscard]] inline auto operator&(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted bitwise operator& requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted bitwise operator& requires a present right operand");
	using lhs_t = detail::remove_cvref_t<decltype(lhs_value)>;
	using rhs_t = detail::remove_cvref_t<decltype(rhs_value)>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs_value) & mixed_t(rhs_value);
	} else {
		return detail::generated_operator_detail::bit_and(lhs_value, rhs_value);
	}
}

template <typename T1, typename T2>
	requires (is_lifted_bitwise_operand<T1> && is_lifted_bitwise_operand<T2>)
[[nodiscard]] inline auto operator|(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted bitwise operator| requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted bitwise operator| requires a present right operand");
	using lhs_t = detail::remove_cvref_t<decltype(lhs_value)>;
	using rhs_t = detail::remove_cvref_t<decltype(rhs_value)>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs_value) | mixed_t(rhs_value);
	} else {
		return detail::generated_operator_detail::bit_or(lhs_value, rhs_value);
	}
}

template <typename T1, typename T2>
	requires (is_lifted_bitwise_operand<T1> && is_lifted_bitwise_operand<T2>)
[[nodiscard]] inline auto operator^(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted bitwise operator^ requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted bitwise operator^ requires a present right operand");
	using lhs_t = detail::remove_cvref_t<decltype(lhs_value)>;
	using rhs_t = detail::remove_cvref_t<decltype(rhs_value)>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs_value) ^ mixed_t(rhs_value);
	} else {
		return detail::generated_operator_detail::bit_xor(lhs_value, rhs_value);
	}
}

template <typename T1, typename T2>
	requires (is_lifted_bitwise_operand<T1> && is_lifted_bitwise_operand<T2>)
[[nodiscard]] inline auto operator<<(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted shift operator<< requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted shift operator<< requires a present right operand");
	using lhs_t = detail::remove_cvref_t<decltype(lhs_value)>;
	using rhs_t = detail::remove_cvref_t<decltype(rhs_value)>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs_value) << mixed_t(rhs_value);
	} else {
		return detail::generated_operator_detail::shl(lhs_value, rhs_value);
	}
}

template <typename T1, typename T2>
	requires (is_lifted_bitwise_operand<T1> && is_lifted_bitwise_operand<T2>)
[[nodiscard]] inline auto operator>>(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted shift operator>> requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted shift operator>> requires a present right operand");
	using lhs_t = detail::remove_cvref_t<decltype(lhs_value)>;
	using rhs_t = detail::remove_cvref_t<decltype(rhs_value)>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs_value) >> mixed_t(rhs_value);
	} else {
		return detail::generated_operator_detail::shr(lhs_value, rhs_value);
	}
}

CPP);

$emit(<<<'CPP'
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

template <typename T, typename U>
[[nodiscard]] inline bool_t operator==(const nullable<T> &lhs, const nullable<U> &rhs) {
	if (!lhs.has_value().native_value()) {
		return bool_t(!rhs.has_value().native_value());
	}
	if (!rhs.has_value().native_value()) {
		return bool_t(false);
	}
	return lhs.value() == rhs.value();
}

template <typename T, typename Right>
	requires is_lifted_equality_operand<Right>
[[nodiscard]] inline bool_t operator==(const nullable<T> &lhs, const Right &rhs) {
	if (!lhs.has_value().native_value()) {
		return bool_t(false);
	}
	return lhs.value() == rhs;
}

template <typename Left, typename T>
	requires is_lifted_equality_operand<Left>
[[nodiscard]] inline bool_t operator==(const Left &lhs, const nullable<T> &rhs) {
	if (!rhs.has_value().native_value()) {
		return bool_t(false);
	}
	return lhs == rhs.value();
}

template <typename T, typename U>
[[nodiscard]] inline bool_t operator!=(const nullable<T> &lhs, const nullable<U> &rhs) {
	return bool_t(!static_cast<bool>((lhs == rhs).native_value()));
}

template <typename T, typename Right>
	requires is_lifted_equality_operand<Right>
[[nodiscard]] inline bool_t operator!=(const nullable<T> &lhs, const Right &rhs) {
	return bool_t(!static_cast<bool>((lhs == rhs).native_value()));
}

template <typename Left, typename T>
	requires is_lifted_equality_operand<Left>
[[nodiscard]] inline bool_t operator!=(const Left &lhs, const nullable<T> &rhs) {
	return bool_t(!static_cast<bool>((lhs == rhs).native_value()));
}

template <typename T, typename U>
[[nodiscard]] inline bool_t operator==(const result_or_false<T> &lhs, const result_or_false<U> &rhs) {
	if (!lhs.has_value().native_value()) {
		return bool_t(!rhs.has_value().native_value());
	}
	if (!rhs.has_value().native_value()) {
		return bool_t(false);
	}
	return lhs.value() == rhs.value();
}

template <typename T, typename Right>
	requires is_lifted_equality_operand<Right>
[[nodiscard]] inline bool_t operator==(const result_or_false<T> &lhs, const Right &rhs) {
	if (!lhs.has_value().native_value()) {
		return bool_t(bool_t(false) == rhs);
	}
	if constexpr (is_bool<Right> && !is_bool<T>) {
		return bool_t(false);
	} else {
		return lhs.value() == rhs;
	}
}

template <typename Left, typename T>
	requires is_lifted_equality_operand<Left>
[[nodiscard]] inline bool_t operator==(const Left &lhs, const result_or_false<T> &rhs) {
	if (!rhs.has_value().native_value()) {
		return bool_t(lhs == bool_t(false));
	}
	if constexpr (is_bool<Left> && !is_bool<T>) {
		return bool_t(false);
	} else {
		return lhs == rhs.value();
	}
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const result_or_false<T> &lhs, false_sentinel_t) noexcept {
	return bool_t(!lhs.has_value().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator==(false_sentinel_t, const result_or_false<T> &rhs) noexcept {
	return bool_t(!rhs.has_value().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const result_or_false<T> &lhs, false_sentinel_t) noexcept {
	return bool_t(lhs.has_value().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(false_sentinel_t, const result_or_false<T> &rhs) noexcept {
	return bool_t(rhs.has_value().native_value());
}

template <typename T, typename U>
[[nodiscard]] inline bool_t operator!=(const result_or_false<T> &lhs, const result_or_false<U> &rhs) {
	return bool_t(!static_cast<bool>((lhs == rhs).native_value()));
}

template <typename T, typename Right>
	requires is_lifted_equality_operand<Right>
[[nodiscard]] inline bool_t operator!=(const result_or_false<T> &lhs, const Right &rhs) {
	return bool_t(!static_cast<bool>((lhs == rhs).native_value()));
}

template <typename Left, typename T>
	requires is_lifted_equality_operand<Left>
[[nodiscard]] inline bool_t operator!=(const Left &lhs, const result_or_false<T> &rhs) {
	return bool_t(!static_cast<bool>((lhs == rhs).native_value()));
}

template <typename T, typename U>
[[nodiscard]] inline bool_t operator==(const result_or_bool<T> &lhs, const result_or_bool<U> &rhs) {
	if (!lhs.has_value().native_value()) {
		if (!rhs.has_value().native_value()) {
			return bool_t(lhs.is_true().native_value() == rhs.is_true().native_value());
		}
		return bool_t(false);
	}
	if (!rhs.has_value().native_value()) {
		return bool_t(false);
	}
	return lhs.value() == rhs.value();
}

template <typename T, typename Right>
	requires is_lifted_equality_operand<Right>
[[nodiscard]] inline bool_t operator==(const result_or_bool<T> &lhs, const Right &rhs) {
	if (!lhs.has_value().native_value()) {
		return bool_t(bool_t(lhs.is_true().native_value()) == rhs);
	}
	if constexpr (is_bool<Right> && !is_bool<T>) {
		return bool_t(false);
	} else {
		return lhs.value() == rhs;
	}
}

template <typename Left, typename T>
	requires is_lifted_equality_operand<Left>
[[nodiscard]] inline bool_t operator==(const Left &lhs, const result_or_bool<T> &rhs) {
	if (!rhs.has_value().native_value()) {
		return bool_t(lhs == bool_t(rhs.is_true().native_value()));
	}
	if constexpr (is_bool<Left> && !is_bool<T>) {
		return bool_t(false);
	} else {
		return lhs == rhs.value();
	}
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const result_or_bool<T> &lhs, false_sentinel_t) noexcept {
	return bool_t(lhs.is_false().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator==(false_sentinel_t, const result_or_bool<T> &rhs) noexcept {
	return bool_t(rhs.is_false().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const result_or_bool<T> &lhs, false_sentinel_t) noexcept {
	return bool_t(!lhs.is_false().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(false_sentinel_t, const result_or_bool<T> &rhs) noexcept {
	return bool_t(!rhs.is_false().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator==(const result_or_bool<T> &lhs, true_sentinel_t) noexcept {
	return bool_t(lhs.is_true().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator==(true_sentinel_t, const result_or_bool<T> &rhs) noexcept {
	return bool_t(rhs.is_true().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const result_or_bool<T> &lhs, true_sentinel_t) noexcept {
	return bool_t(!lhs.is_true().native_value());
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(true_sentinel_t, const result_or_bool<T> &rhs) noexcept {
	return bool_t(!rhs.is_true().native_value());
}

template <typename T, typename U>
[[nodiscard]] inline bool_t operator!=(const result_or_bool<T> &lhs, const result_or_bool<U> &rhs) {
	return bool_t(!static_cast<bool>((lhs == rhs).native_value()));
}

template <typename T, typename Right>
	requires is_lifted_equality_operand<Right>
[[nodiscard]] inline bool_t operator!=(const result_or_bool<T> &lhs, const Right &rhs) {
	return bool_t(!static_cast<bool>((lhs == rhs).native_value()));
}

template <typename Left, typename T>
	requires is_lifted_equality_operand<Left>
[[nodiscard]] inline bool_t operator!=(const Left &lhs, const result_or_bool<T> &rhs) {
	return bool_t(!static_cast<bool>((lhs == rhs).native_value()));
}

template <typename T, typename U>
[[nodiscard]] inline bool_t operator==(const result<T> &lhs, const result<U> &rhs) {
	if (lhs.has_error().native_value()) {
		return bool_t(rhs.has_error().native_value());
	}
	if (rhs.has_error().native_value()) {
		return bool_t(false);
	}
	return lhs.value() == rhs.value();
}

template <typename T, typename Right>
	requires is_lifted_equality_operand<Right>
[[nodiscard]] inline bool_t operator==(const result<T> &lhs, const Right &rhs) {
	if (lhs.has_error().native_value()) {
		return bool_t(false);
	}
	return lhs.value() == rhs;
}

template <typename Left, typename T>
	requires is_lifted_equality_operand<Left>
[[nodiscard]] inline bool_t operator==(const Left &lhs, const result<T> &rhs) {
	if (rhs.has_error().native_value()) {
		return bool_t(false);
	}
	return lhs == rhs.value();
}

template <typename T, typename U>
[[nodiscard]] inline bool_t operator!=(const result<T> &lhs, const result<U> &rhs) {
	return bool_t(!static_cast<bool>((lhs == rhs).native_value()));
}

template <typename T, typename Right>
	requires is_lifted_equality_operand<Right>
[[nodiscard]] inline bool_t operator!=(const result<T> &lhs, const Right &rhs) {
	return bool_t(!static_cast<bool>((lhs == rhs).native_value()));
}

template <typename Left, typename T>
	requires is_lifted_equality_operand<Left>
[[nodiscard]] inline bool_t operator!=(const Left &lhs, const result<T> &rhs) {
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
	requires (is_lifted_ordering_operand<T1> && is_lifted_ordering_operand<T2>)
[[nodiscard]] inline auto operator<(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted ordering operator< requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted ordering operator< requires a present right operand");
	return lhs_value < rhs_value;
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
	requires (is_lifted_ordering_operand<T1> && is_lifted_ordering_operand<T2>)
[[nodiscard]] inline auto operator<=(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted ordering operator<= requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted ordering operator<= requires a present right operand");
	return lhs_value <= rhs_value;
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
	requires (is_lifted_ordering_operand<T1> && is_lifted_ordering_operand<T2>)
[[nodiscard]] inline auto operator>(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted ordering operator> requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted ordering operator> requires a present right operand");
	return lhs_value > rhs_value;
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
	requires (is_lifted_ordering_operand<T1> && is_lifted_ordering_operand<T2>)
[[nodiscard]] inline auto operator>=(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted ordering operator>= requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted ordering operator>= requires a present right operand");
	return lhs_value >= rhs_value;
}

template <typename T1, typename T2>
	requires (is_lifted_logical_operand<T1> && is_lifted_logical_operand<T2>)
[[nodiscard]] inline auto operator&&(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted logical operator&& requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted logical operator&& requires a present right operand");
	using lhs_t = detail::remove_cvref_t<decltype(lhs_value)>;
	using rhs_t = detail::remove_cvref_t<decltype(rhs_value)>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs_value) && mixed_t(rhs_value);
	} else if constexpr (is_bool<lhs_t> && is_bool<rhs_t>) {
		return detail::generated_operator_detail::logical_and(lhs_value, rhs_value);
	} else if constexpr (is_native_number<lhs_t> && is_native_number<rhs_t>) {
		return detail::generated_operator_detail::logical_and(lhs_value, rhs_value);
	} else {
		static_assert(detail::always_false_v<lhs_t, rhs_t>, "unsupported operator&& combination");
	}
}

template <typename T1, typename T2>
	requires (is_lifted_logical_operand<T1> && is_lifted_logical_operand<T2>)
[[nodiscard]] inline auto operator||(const T1 &lhs, const T2 &rhs) {
	const auto &lhs_value = require_lifted_compound_value(lhs, "lifted logical operator|| requires a present left operand");
	const auto &rhs_value = require_lifted_compound_value(rhs, "lifted logical operator|| requires a present right operand");
	using lhs_t = detail::remove_cvref_t<decltype(lhs_value)>;
	using rhs_t = detail::remove_cvref_t<decltype(rhs_value)>;
	if constexpr (is_mixed<lhs_t> || is_mixed<rhs_t>) {
		return mixed_t(lhs_value) || mixed_t(rhs_value);
	} else if constexpr (is_bool<lhs_t> && is_bool<rhs_t>) {
		return detail::generated_operator_detail::logical_or(lhs_value, rhs_value);
	} else if constexpr (is_native_number<lhs_t> && is_native_number<rhs_t>) {
		return detail::generated_operator_detail::logical_or(lhs_value, rhs_value);
	} else {
		static_assert(detail::always_false_v<lhs_t, rhs_t>, "unsupported operator|| combination");
	}
}

CPP);

$emit(<<<'CPP'

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
[[nodiscard]] inline bool_t operator==(const nullable<T> &lhs, const nullable<T> &rhs) {
	return detail::generated_operator_detail::nullable_eq_same(lhs, rhs);
}

template <typename T>
[[nodiscard]] inline bool_t operator!=(const nullable<T> &lhs, const nullable<T> &rhs) {
	return detail::generated_operator_detail::nullable_ne_same(lhs, rhs);
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

CPP);

$emit(<<<'CPP'
template <typename L, typename R>
	requires (
		is_lifted_compound_wrapper<L>
		&& (is_native_number<lifted_compound_inner_t<L>> || is_mixed<lifted_compound_inner_t<L>>)
		&& requires (lifted_compound_inner_t<L> &lhs_inner, const R &rhs_value) {
			lhs_inner += require_lifted_compound_value(rhs_value, "");
		}
	)
inline L &operator+=(L &lhs, const R &rhs) {
	require_lifted_compound_value(lhs, "lifted compound operator+= requires a present left operand")
		+= require_lifted_compound_value(rhs, "lifted compound operator+= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires (
		is_lifted_compound_wrapper<L>
		&& (is_native_number<lifted_compound_inner_t<L>> || is_mixed<lifted_compound_inner_t<L>>)
		&& requires (lifted_compound_inner_t<L> &lhs_inner, const R &rhs_value) {
			lhs_inner -= require_lifted_compound_value(rhs_value, "");
		}
	)
inline L &operator-=(L &lhs, const R &rhs) {
	require_lifted_compound_value(lhs, "lifted compound operator-= requires a present left operand")
		-= require_lifted_compound_value(rhs, "lifted compound operator-= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires (
		is_lifted_compound_wrapper<L>
		&& (is_native_number<lifted_compound_inner_t<L>> || is_mixed<lifted_compound_inner_t<L>>)
		&& requires (lifted_compound_inner_t<L> &lhs_inner, const R &rhs_value) {
			lhs_inner *= require_lifted_compound_value(rhs_value, "");
		}
	)
inline L &operator*=(L &lhs, const R &rhs) {
	require_lifted_compound_value(lhs, "lifted compound operator*= requires a present left operand")
		*= require_lifted_compound_value(rhs, "lifted compound operator*= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires (
		is_lifted_compound_wrapper<L>
		&& (is_native_number<lifted_compound_inner_t<L>> || is_mixed<lifted_compound_inner_t<L>>)
		&& requires (lifted_compound_inner_t<L> &lhs_inner, const R &rhs_value) {
			lhs_inner /= require_lifted_compound_value(rhs_value, "");
		}
	)
inline L &operator/=(L &lhs, const R &rhs) {
	require_lifted_compound_value(lhs, "lifted compound operator/= requires a present left operand")
		/= require_lifted_compound_value(rhs, "lifted compound operator/= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires (
		is_lifted_compound_wrapper<L>
		&& (is_native_int<lifted_compound_inner_t<L>> || is_mixed<lifted_compound_inner_t<L>>)
		&& requires (lifted_compound_inner_t<L> &lhs_inner, const R &rhs_value) {
			lhs_inner %= require_lifted_compound_value(rhs_value, "");
		}
	)
inline L &operator%=(L &lhs, const R &rhs) {
	require_lifted_compound_value(lhs, "lifted compound operator%= requires a present left operand")
		%= require_lifted_compound_value(rhs, "lifted compound operator%= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires (
		is_lifted_compound_wrapper<L>
		&& (is_native_int<lifted_compound_inner_t<L>> || is_mixed<lifted_compound_inner_t<L>>)
		&& requires (lifted_compound_inner_t<L> &lhs_inner, const R &rhs_value) {
			lhs_inner &= require_lifted_compound_value(rhs_value, "");
		}
	)
inline L &operator&=(L &lhs, const R &rhs) {
	require_lifted_compound_value(lhs, "lifted compound operator&= requires a present left operand")
		&= require_lifted_compound_value(rhs, "lifted compound operator&= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires (
		is_lifted_compound_wrapper<L>
		&& (is_native_int<lifted_compound_inner_t<L>> || is_mixed<lifted_compound_inner_t<L>>)
		&& requires (lifted_compound_inner_t<L> &lhs_inner, const R &rhs_value) {
			lhs_inner |= require_lifted_compound_value(rhs_value, "");
		}
	)
inline L &operator|=(L &lhs, const R &rhs) {
	require_lifted_compound_value(lhs, "lifted compound operator|= requires a present left operand")
		|= require_lifted_compound_value(rhs, "lifted compound operator|= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires (
		is_lifted_compound_wrapper<L>
		&& (is_native_int<lifted_compound_inner_t<L>> || is_mixed<lifted_compound_inner_t<L>>)
		&& requires (lifted_compound_inner_t<L> &lhs_inner, const R &rhs_value) {
			lhs_inner ^= require_lifted_compound_value(rhs_value, "");
		}
	)
inline L &operator^=(L &lhs, const R &rhs) {
	require_lifted_compound_value(lhs, "lifted compound operator^= requires a present left operand")
		^= require_lifted_compound_value(rhs, "lifted compound operator^= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires (
		is_lifted_compound_wrapper<L>
		&& (is_native_int<lifted_compound_inner_t<L>> || is_mixed<lifted_compound_inner_t<L>>)
		&& requires (lifted_compound_inner_t<L> &lhs_inner, const R &rhs_value) {
			lhs_inner <<= require_lifted_compound_value(rhs_value, "");
		}
	)
inline L &operator<<=(L &lhs, const R &rhs) {
	require_lifted_compound_value(lhs, "lifted compound operator<<= requires a present left operand")
		<<= require_lifted_compound_value(rhs, "lifted compound operator<<= requires a present right operand");
	return lhs;
}

template <typename L, typename R>
	requires (
		is_lifted_compound_wrapper<L>
		&& (is_native_int<lifted_compound_inner_t<L>> || is_mixed<lifted_compound_inner_t<L>>)
		&& requires (lifted_compound_inner_t<L> &lhs_inner, const R &rhs_value) {
			lhs_inner >>= require_lifted_compound_value(rhs_value, "");
		}
	)
inline L &operator>>=(L &lhs, const R &rhs) {
	require_lifted_compound_value(lhs, "lifted compound operator>>= requires a present left operand")
		>>= require_lifted_compound_value(rhs, "lifted compound operator>>= requires a present right operand");
	return lhs;
}

CPP);

$emit(<<<'CPP'
template <typename Rep>
inline int_t<Rep> &operator+=(int_t<Rep> &lhs, const int_t<Rep> &rhs) noexcept {
	lhs = detail::generated_operator_detail::add(lhs, rhs);
	return lhs;
}

template <typename Rep>
inline int_t<Rep> &operator-=(int_t<Rep> &lhs, const int_t<Rep> &rhs) noexcept {
	lhs = detail::generated_operator_detail::sub(lhs, rhs);
	return lhs;
}

template <typename Rep>
inline int_t<Rep> &operator*=(int_t<Rep> &lhs, const int_t<Rep> &rhs) noexcept {
	lhs = detail::generated_operator_detail::mul(lhs, rhs);
	return lhs;
}

template <typename Rep>
inline int_t<Rep> &operator/=(int_t<Rep> &lhs, const int_t<Rep> &rhs) {
	lhs = detail::generated_operator_detail::div(lhs, rhs);
	return lhs;
}

template <typename Rep>
inline int_t<Rep> &operator%=(int_t<Rep> &lhs, const int_t<Rep> &rhs) {
	lhs = detail::generated_operator_detail::mod(lhs, rhs);
	return lhs;
}

template <typename Rep>
inline int_t<Rep> &operator&=(int_t<Rep> &lhs, const int_t<Rep> &rhs) noexcept {
	lhs = detail::generated_operator_detail::bit_and(lhs, rhs);
	return lhs;
}

template <typename Rep>
inline int_t<Rep> &operator|=(int_t<Rep> &lhs, const int_t<Rep> &rhs) noexcept {
	lhs = detail::generated_operator_detail::bit_or(lhs, rhs);
	return lhs;
}

template <typename Rep>
inline int_t<Rep> &operator^=(int_t<Rep> &lhs, const int_t<Rep> &rhs) noexcept {
	lhs = detail::generated_operator_detail::bit_xor(lhs, rhs);
	return lhs;
}

template <typename Rep>
inline int_t<Rep> &operator<<=(int_t<Rep> &lhs, const int_t<Rep> &rhs) noexcept {
	lhs = detail::generated_operator_detail::shl(lhs, rhs);
	return lhs;
}

template <typename Rep>
inline int_t<Rep> &operator>>=(int_t<Rep> &lhs, const int_t<Rep> &rhs) noexcept {
	lhs = detail::generated_operator_detail::shr(lhs, rhs);
	return lhs;
}

inline int_t<> &operator+=(int_t<> &lhs, const mixed_t &rhs) {
	lhs = cast<int_t<>>(mixed_t(lhs) + rhs);
	return lhs;
}

inline int_t<> &operator-=(int_t<> &lhs, const mixed_t &rhs) {
	lhs = cast<int_t<>>(mixed_t(lhs) - rhs);
	return lhs;
}

inline int_t<> &operator*=(int_t<> &lhs, const mixed_t &rhs) {
	lhs = cast<int_t<>>(mixed_t(lhs) * rhs);
	return lhs;
}

inline int_t<> &operator/=(int_t<> &lhs, const mixed_t &rhs) {
	lhs = cast<int_t<>>(mixed_t(lhs) / rhs);
	return lhs;
}

inline int_t<> &operator%=(int_t<> &lhs, const mixed_t &rhs) {
	lhs = cast<int_t<>>(mixed_t(lhs) % rhs);
	return lhs;
}

inline int_t<> &operator&=(int_t<> &lhs, const mixed_t &rhs) {
	lhs = cast<int_t<>>(mixed_t(lhs) & rhs);
	return lhs;
}

inline int_t<> &operator|=(int_t<> &lhs, const mixed_t &rhs) {
	lhs = cast<int_t<>>(mixed_t(lhs) | rhs);
	return lhs;
}

inline int_t<> &operator^=(int_t<> &lhs, const mixed_t &rhs) {
	lhs = cast<int_t<>>(mixed_t(lhs) ^ rhs);
	return lhs;
}

inline int_t<> &operator<<=(int_t<> &lhs, const mixed_t &rhs) {
	lhs = cast<int_t<>>(mixed_t(lhs) << rhs);
	return lhs;
}

inline int_t<> &operator>>=(int_t<> &lhs, const mixed_t &rhs) {
	lhs = cast<int_t<>>(mixed_t(lhs) >> rhs);
	return lhs;
}

inline float_t &operator+=(float_t &lhs, const int_t<> &rhs) noexcept {
	lhs = detail::generated_operator_detail::add(lhs, rhs);
	return lhs;
}

inline float_t &operator+=(float_t &lhs, const float_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::add(lhs, rhs);
	return lhs;
}

inline float_t &operator-=(float_t &lhs, const int_t<> &rhs) noexcept {
	lhs = detail::generated_operator_detail::sub(lhs, rhs);
	return lhs;
}

inline float_t &operator-=(float_t &lhs, const float_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::sub(lhs, rhs);
	return lhs;
}

inline float_t &operator*=(float_t &lhs, const int_t<> &rhs) noexcept {
	lhs = detail::generated_operator_detail::mul(lhs, rhs);
	return lhs;
}

inline float_t &operator*=(float_t &lhs, const float_t &rhs) noexcept {
	lhs = detail::generated_operator_detail::mul(lhs, rhs);
	return lhs;
}

inline float_t &operator/=(float_t &lhs, const int_t<> &rhs) {
	lhs = detail::generated_operator_detail::div(lhs, rhs);
	return lhs;
}

inline float_t &operator/=(float_t &lhs, const float_t &rhs) {
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
CPP);

file_put_contents($output_path, implode("\n\n", $header) . "\n");
fwrite(STDOUT, "Generated {$output_path}\n");
