#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/result_or_bool.hpp"
#include "scpp/result.hpp"
#include "scpp/string_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/hash_t.hpp"

#include <cctype>
#include <cerrno>
#include <charconv>
#include <cmath>
#include <cstdlib>
#include <iomanip>
#include <limits>
#include <sstream>
#include <string>
#include <type_traits>

namespace scpp {

// Named cast helper required by the config.
//
// Enforces:
// - only explicitly configured From/To pairs are legal
// - unsupported cast pairs fail at compile time
// - cast behavior is centralized rather than scattered across constructors

template <typename To, typename From>
To cast(const From &value) {
	if constexpr (std::is_same_v<To, From>) {
		return value;
	} else {
		static_assert(detail::always_false_v<To>, "scpp::cast is not defined for this From/To pair");
	}
}

template <typename To, typename From>
requires(std::is_integral_v<detail::remove_cvref_t<From>> && !std::is_same_v<detail::remove_cvref_t<From>, bool> && std::is_same_v<To, bool_t>)
inline To cast(const From &value) {
	return bool_t(static_cast<std::int64_t>(value) != 0);
}

template <typename To, typename From>
requires(std::is_integral_v<detail::remove_cvref_t<From>> && !std::is_same_v<detail::remove_cvref_t<From>, bool> && std::is_same_v<To, int_t>)
inline To cast(const From &value) {
	return int_t(static_cast<std::int64_t>(value));
}

template <typename To, typename From>
requires(std::is_integral_v<detail::remove_cvref_t<From>> && !std::is_same_v<detail::remove_cvref_t<From>, bool> && std::is_same_v<To, float_t>)
inline To cast(const From &value) {
	return float_t(static_cast<double>(value));
}

template <typename To, typename From>
requires(std::is_integral_v<detail::remove_cvref_t<From>> && !std::is_same_v<detail::remove_cvref_t<From>, bool> && std::is_same_v<To, string_t>)
inline To cast(const From &value) {
	return string_t(std::to_string(static_cast<std::int64_t>(value)));
}

template <typename To, typename From>
requires(std::is_floating_point_v<detail::remove_cvref_t<From>> && std::is_same_v<To, bool_t>)
inline To cast(const From &value) {
	return bool_t(static_cast<double>(value) != 0.0);
}

template <typename To, typename From>
requires(std::is_floating_point_v<detail::remove_cvref_t<From>> && std::is_same_v<To, int_t>)
inline To cast(const From &value) {
	return int_t(static_cast<std::int64_t>(value));
}

template <typename To, typename From>
requires(std::is_floating_point_v<detail::remove_cvref_t<From>> && std::is_same_v<To, float_t>)
inline To cast(const From &value) {
	return float_t(static_cast<double>(value));
}

template <typename To, typename From>
requires(std::is_floating_point_v<detail::remove_cvref_t<From>> && std::is_same_v<To, string_t>)
inline To cast(const From &value) {
	return cast<string_t>(static_cast<double>(value));
}

namespace detail {

[[noreturn]] inline void throw_invalid_cast_string(const char *target, const std::string &value) {
	throw std::runtime_error(
		std::string("scpp::cast<") + target + ">(string_t): invalid strict string literal: \"" + value + "\""
	);
}

inline bool parse_bool_string_strict(const std::string &value, bool &out) {
	if (value == "0" || value == "false" || value == "FALSE" || value == "False") {
		out = false;
		return true;
	}
	if (value == "1" || value == "true" || value == "TRUE" || value == "True") {
		out = true;
		return true;
	}
	return false;
}

inline bool parse_int64_string_strict(const std::string &value, std::int64_t &out) {
	if (value.empty()) {
		return false;
	}

	const char *begin = value.data();
	const char *end = begin + value.size();
	std::int64_t parsed = 0;
	const auto result = std::from_chars(begin, end, parsed, 10);
	if (result.ec != std::errc() || result.ptr != end) {
		return false;
	}

	out = parsed;
	return true;
}

inline bool parse_double_string_strict(const std::string &value, double &out) {
	if (value.empty()) {
		return false;
	}
	for (const unsigned char ch : value) {
		if (std::isspace(ch) != 0) {
			return false;
		}
	}

	char *parse_end = nullptr;
	errno = 0;
	const double parsed = std::strtod(value.c_str(), &parse_end);
	if (parse_end == nullptr || parse_end != value.c_str() + value.size()) {
		return false;
	}
	if (errno == ERANGE || !std::isfinite(parsed)) {
		return false;
	}

	std::string lower;
	lower.reserve(value.size());
	for (const unsigned char ch : value) {
		lower.push_back(static_cast<char>(std::tolower(ch)));
	}
	if (lower == "nan" || lower == "+nan" || lower == "-nan" || lower == "inf" || lower == "+inf" || lower == "-inf" || lower == "infinity" || lower == "+infinity" || lower == "-infinity") {
		return false;
	}

	out = parsed;
	return true;
}

} // namespace detail

// Native bool -> wrappers
// Native values are accepted at explicit cast boundaries so generator/runtime helpers can reuse the same surface.
template <>
inline bool_t cast<bool_t, bool>(const bool &value) {
	return bool_t(value);
}

template <>
inline int_t cast<int_t, bool>(const bool &value) {
	return int_t(value ? 1 : 0);
}

template <>
inline float_t cast<float_t, bool>(const bool &value) {
	return float_t(value ? 1.0 : 0.0);
}

template <>
inline string_t cast<string_t, bool>(const bool &value) {
	return string_t(value ? "1" : "");
}

// Native integer -> wrappers
template <>
inline bool_t cast<bool_t, std::int64_t>(const std::int64_t &value) {
	return bool_t(value != 0);
}

template <>
inline int_t cast<int_t, std::int64_t>(const std::int64_t &value) {
	return int_t(value);
}

template <>
inline float_t cast<float_t, std::int64_t>(const std::int64_t &value) {
	return float_t(static_cast<double>(value));
}

template <>
inline string_t cast<string_t, std::int64_t>(const std::int64_t &value) {
	return string_t(std::to_string(value));
}

// Native float -> wrappers
template <>
inline bool_t cast<bool_t, double>(const double &value) {
	return bool_t(value != 0.0);
}

template <>
inline int_t cast<int_t, double>(const double &value) {
	return int_t(static_cast<std::int64_t>(value));
}

template <>
inline float_t cast<float_t, double>(const double &value) {
	return float_t(value);
}

template <>
inline string_t cast<string_t, double>(const double &value) {
	std::ostringstream stream;
	stream << std::setprecision(14) << std::defaultfloat << value;
	return string_t(stream.str());
}

// bool_t -> scalar wrappers
// Boolean to numeric remains explicit and centralized.
template <>
inline int_t cast<int_t, bool_t>(const bool_t &value) {
	return int_t(value.native_value() ? 1 : 0);
}

template <>
inline float_t cast<float_t, bool_t>(const bool_t &value) {
	return float_t(value.native_value() ? 1.0 : 0.0);
}

// int_t -> bool_t
// Zero becomes false; any non-zero value becomes true.
template <>
inline bool_t cast<bool_t, int_t>(const int_t &value) {
	return bool_t(value.native_value() != 0);
}

// int_t -> float_t
// Widening remains explicit through the named-cast surface even though the wrapper also has a constructor path.
template <>
inline float_t cast<float_t, int_t>(const int_t &value) {
	return float_t(static_cast<double>(value.native_value()));
}

// float_t -> bool_t
// Zero becomes false; any non-zero value becomes true.
template <>
inline bool_t cast<bool_t, float_t>(const float_t &value) {
	return bool_t(value.native_value() != 0.0);
}

// float_t -> int_t
// This is an explicit narrowing conversion and truncates toward zero.
template <>
inline int_t cast<int_t, float_t>(const float_t &value) {
	return int_t(static_cast<std::int64_t>(value.native_value()));
}

// string_t -> bool_t
// String-to-bool is strict in this project: only a small approved literal set is accepted.
template <>
inline bool_t cast<bool_t, string_t>(const string_t &value) {
	bool parsed = false;
	if (!detail::parse_bool_string_strict(value.native_value(), parsed)) {
		detail::throw_invalid_cast_string("bool_t", value.native_value());
	}
	return bool_t(parsed);
}

// string_t -> int_t
// String-to-int is strict: the whole string must be a valid base-10 integer literal.
template <>
inline int_t cast<int_t, string_t>(const string_t &value) {
	std::int64_t parsed = 0;
	if (!detail::parse_int64_string_strict(value.native_value(), parsed)) {
		detail::throw_invalid_cast_string("int_t", value.native_value());
	}
	return int_t(parsed);
}

// string_t -> float_t
// String-to-float is strict: the whole string must parse as a finite decimal floating literal.
template <>
inline float_t cast<float_t, string_t>(const string_t &value) {
	double parsed = 0.0;
	if (!detail::parse_double_string_strict(value.native_value(), parsed)) {
		detail::throw_invalid_cast_string("float_t", value.native_value());
	}
	return float_t(parsed);
}

// int_t -> bool
// Zero becomes false; any non-zero value becomes true.
template <>
inline bool cast<bool, int_t>(const int_t &value) {
	return value.native_value() != 0;
}

// float_t -> bool
// Zero becomes false; any non-zero value becomes true.
template <>
inline bool cast<bool, float_t>(const float_t &value) {
	return value.native_value() != 0.0;
}

// bool_t -> bool
// Explicit bridge to native bool for C++ control-flow sites generated through cast<bool>(...).
template <>
inline bool cast<bool, bool_t>(const bool_t &value) {
	return static_cast<bool>(value);
}

// nullable<T> -> U
// Centralized nullable cast lifting: empty nullable fails for value-required targets and present nullable delegates to the wrapped-value cast path.
// string_t remains the configured PHP-style exception where an empty nullable stringifies to the empty string.
template <typename To, typename From>
inline To cast(const nullable<From> &value) {
	if constexpr (std::is_same_v<To, string_t>) {
		if (!value.has_value().native_value()) {
			return string_t("");
		}
		return cast<string_t>(value.require_value("cast<string_t>(nullable): present value required for wrapped conversion"));
	} else {
		return cast<To>(value.require_value("cast<To>(nullable) cannot convert an empty nullable to a required value"));
	}
}

// result_or_false<T> -> U
// Centralized false-able cast lifting mirrors nullable: the false-like empty state fails for required-value targets and present values delegate to wrapped casts.
template <typename To, typename From>
inline To cast(const result_or_false<From> &value) {
	if constexpr (std::is_same_v<To, string_t>) {
		if (!value.has_value().native_value()) {
			return string_t("");
		}
		return cast<string_t>(value.require_value("cast<string_t>(result_or_false): present value required for wrapped conversion"));
	} else {
		return cast<To>(value.require_value("cast<To>(result_or_false) cannot convert a false-like empty state to a required value"));
	}
}

// result_or_bool<T> -> U
// Centralized bool-able cast lifting mirrors nullable: only the wrapped value participates in required-value casts.
template <typename To, typename From>
inline To cast(const result_or_bool<From> &value) {
	if constexpr (std::is_same_v<To, string_t>) {
		if (!value.has_value().native_value()) {
			return string_t("");
		}
		return cast<string_t>(value.require_value("cast<string_t>(result_or_bool): present value required for wrapped conversion"));
	} else {
		return cast<To>(value.require_value("cast<To>(result_or_bool) cannot convert a bool-state wrapper to a required value"));
	}
}

// result<T> -> U
// Centralized error-able cast lifting mirrors nullable: the error state fails for required-value targets and present values delegate to wrapped casts.
template <typename To, typename From>
inline To cast(const result<From> &value) {
	if constexpr (std::is_same_v<To, string_t>) {
		return cast<string_t>(value.require_value("cast<string_t>(result): present value required for wrapped conversion"));
	} else {
		return cast<To>(value.require_value("cast<To>(result) cannot convert an error state to a required value"));
	}
}

// Identity cast for string_t
// Keeps generator-emitted cast<string_t>(string_t) expressions valid and explicit.
template <>
inline string_t cast<string_t, string_t>(const string_t &value) {
	return value;
}

// int_t -> string_t
// Numeric to string conversion is explicit and centralized here.
template <>
inline string_t cast<string_t, int_t>(const int_t &value) {
	return string_t(std::to_string(value.native_value()));
}

// float_t -> string_t
// Numeric to string conversion follows PHP-like display formatting for scalar string coercion.
// This intentionally avoids std::to_string(), which forces six trailing fractional digits.
template <>
inline string_t cast<string_t, float_t>(const float_t &value) {
	return cast<string_t>(value.native_value());
}

// bool_t -> string_t
// Mirrors PHP string conversion for booleans: true => "1", false => "".
template <>
inline string_t cast<string_t, bool_t>(const bool_t &value) {
	return string_t(value.native_value() ? "1" : "");
}

// null-like sentinels -> string_t
// Mirrors PHP string conversion for null-like values as the empty string.
template <>
inline string_t cast<string_t, null_t>(const null_t &) {
	return string_t("");
}

template <>
inline string_t cast<string_t, nullopt_t>(const nullopt_t &) {
	return string_t("");
}

template <>
inline string_t cast<string_t, nullptr_t>(const nullptr_t &) {
	return string_t("");
}

// mixed_t -> bool_t
// Applies the configured explicit conversion rules after runtime kind dispatch.
template <>
inline bool_t cast<bool_t, mixed_t>(const mixed_t &value) {
	switch (value.kind()) {
		case mixed_t::kind_t::bool_v:
			return value.bool_value();
		case mixed_t::kind_t::int_v:
			return cast<bool_t>(value.int_value());
		case mixed_t::kind_t::float_v:
			return cast<bool_t>(value.float_value());
		case mixed_t::kind_t::string_v:
			return cast<bool_t>(*value.string_if());
		default:
			throw std::runtime_error("scpp::cast<bool_t>(mixed_t): runtime kind is not convertible to bool_t");
	}
}

// mixed_t -> bool
// Native bool bridge for runtime-dispatched values.
template <>
inline bool cast<bool, mixed_t>(const mixed_t &value) {
	return cast<bool>(cast<bool_t>(value));
}

// mixed_t -> int_t
// Applies the configured explicit conversion rules after runtime kind dispatch.
template <>
inline int_t cast<int_t, mixed_t>(const mixed_t &value) {
	switch (value.kind()) {
		case mixed_t::kind_t::bool_v:
			return cast<int_t>(value.bool_value());
		case mixed_t::kind_t::int_v:
			return value.int_value();
		case mixed_t::kind_t::float_v:
			return cast<int_t>(value.float_value());
		case mixed_t::kind_t::string_v:
			return cast<int_t>(*value.string_if());
		default:
			throw std::runtime_error("scpp::cast<int_t>(mixed_t): runtime kind is not convertible to int_t");
	}
}

// mixed_t -> float_t
// Applies the configured explicit conversion rules after runtime kind dispatch.
template <>
inline float_t cast<float_t, mixed_t>(const mixed_t &value) {
	switch (value.kind()) {
		case mixed_t::kind_t::bool_v:
			return cast<float_t>(value.bool_value());
		case mixed_t::kind_t::int_v:
			return cast<float_t>(value.int_value());
		case mixed_t::kind_t::float_v:
			return value.float_value();
		case mixed_t::kind_t::string_v:
			return cast<float_t>(*value.string_if());
		default:
			throw std::runtime_error("scpp::cast<float_t>(mixed_t): runtime kind is not convertible to float_t");
	}
}

// mixed_t -> string_t
// Applies the configured explicit conversion rules after runtime kind dispatch.
template <>
inline string_t cast<string_t, mixed_t>(const mixed_t &value) {
	switch (value.kind()) {
		case mixed_t::kind_t::null_v:
			return cast<string_t>(null_t{});
		case mixed_t::kind_t::bool_v:
			return cast<string_t>(value.bool_value());
		case mixed_t::kind_t::int_v:
			return cast<string_t>(value.int_value());
		case mixed_t::kind_t::float_v:
			return cast<string_t>(value.float_value());
		case mixed_t::kind_t::string_v:
			return *value.string_if();
		default:
			throw std::runtime_error("scpp::cast<string_t>(mixed_t): runtime kind is not convertible to string_t");
	}
}

// mixed_t -> nullable<T>
// Centralized dynamic-to-nullable lifting mirrors nullable-to-value lifting in the opposite direction.
// Null stays null; any non-null runtime kind must satisfy the already-configured mixed_t -> T rule for the wrapped target.
template <typename To>
requires(detail::is_specialization_of_v<To, nullable>)
inline To cast(const mixed_t &value) {
	using wrapped_t = detail::nullable_value_type_t<To>;
	if (value.kind() == mixed_t::kind_t::null_v) {
		return To(null_t{});
	}
	return To(cast<wrapped_t>(value));
}

// mixed_t -> shared_p<hash_t<mixed_t>>
// Keeps object-like shared ownership explicit while still allowing mixed_t to auto-bridge in typed contexts.
template <>
inline shared_p<hash_t<mixed_t>> cast<shared_p<hash_t<mixed_t>>, mixed_t>(const mixed_t &value) {
	switch (value.kind()) {
		case mixed_t::kind_t::null_v:
			return shared_p<hash_t<mixed_t>>(null_t{});
		case mixed_t::kind_t::shared_table_v:
			return *value.shared_table_if();
		default:
			throw std::runtime_error("scpp::cast<shared_p<hash_t<mixed_t>>>(mixed_t): runtime kind is not convertible to shared table");
	}
}

// mixed_t -> weak_p<hash_t<mixed_t>>
// Mirrors the wrapper-level shared-to-weak downgrade and null handling.
template <>
inline weak_p<hash_t<mixed_t>> cast<weak_p<hash_t<mixed_t>>, mixed_t>(const mixed_t &value) {
	switch (value.kind()) {
		case mixed_t::kind_t::null_v:
			return weak_p<hash_t<mixed_t>>(null_t{});
		case mixed_t::kind_t::shared_table_v:
			return weak_p<hash_t<mixed_t>>(*value.shared_table_if());
		case mixed_t::kind_t::weak_table_v:
			return *value.weak_table_if();
		default:
			throw std::runtime_error("scpp::cast<weak_p<hash_t<mixed_t>>>(mixed_t): runtime kind is not convertible to weak table");
	}
}

// mixed_t rvalue cast bridge
// Keeps move-only extraction centralized while delegating copyable cases to the lvalue overloads.
template <typename To>
inline To cast(mixed_t &&value) {
	if constexpr (std::is_same_v<To, unique_p<hash_t<mixed_t>>>) {
		switch (value.kind()) {
			case mixed_t::kind_t::null_v:
				return unique_p<hash_t<mixed_t>>(null_t{});
			case mixed_t::kind_t::table_v: {
				auto extracted = value.take_table_value();
				value = null_t{};
				return extracted;
			}
			default:
				throw std::runtime_error("scpp::cast<unique_p<hash_t<mixed_t>>>(mixed_t&&): runtime kind is not convertible to unique table");
		}
	} else {
		return cast<To>(static_cast<const mixed_t &>(value));
	}
}

} // namespace scpp
