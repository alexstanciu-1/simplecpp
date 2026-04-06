#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/string_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/hash_t.hpp"

#include <type_traits>
#include <sstream>
#include <iomanip>

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

// int_t -> bool_t
// Zero becomes false; any non-zero value becomes true.
template <>
inline bool_t cast<bool_t, int_t>(const int_t &value) {
	return bool_t(value.native_value() != 0);
}

// float_t -> bool_t
// Zero becomes false; any non-zero value becomes true.
template <>
inline bool_t cast<bool_t, float_t>(const float_t &value) {
	return bool_t(value.native_value() != 0.0);
}

// float_t -> int_t
// This is an explicit narrowing conversion and truncates via static_cast.
template <>
inline int_t cast<int_t, float_t>(const float_t &value) {
	return int_t(static_cast<std::int64_t>(value.native_value()));
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

// nullable<T> -> T
// Explicit unwrap used by generator-emitted return/cast sites after a non-null control-flow check.
template <typename To>
inline To cast(const nullable<To> &value) {
	return value.value();
}

// nullable<T> -> string_t
// Mirrors PHP string conversion for nullable scalars: empty nullable => "", present value => stringified wrapped value.
template <typename To, typename From>
requires(std::is_same_v<To, string_t>)
inline To cast(const nullable<From> &value) {
	if (!value.has_value().native_value()) {
		return string_t("");
	}

	return cast<string_t>(value.value());
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
	std::ostringstream stream;
	stream << std::setprecision(14) << std::defaultfloat << value.native_value();
	return string_t(stream.str());
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
		case mixed_t::kind_t::int_v:
			return value.int_value();
		case mixed_t::kind_t::float_v:
			return cast<int_t>(value.float_value());
		default:
			throw std::runtime_error("scpp::cast<int_t>(mixed_t): runtime kind is not convertible to int_t");
	}
}

// mixed_t -> float_t
// Applies the configured explicit conversion rules after runtime kind dispatch.
template <>
inline float_t cast<float_t, mixed_t>(const mixed_t &value) {
	switch (value.kind()) {
		case mixed_t::kind_t::float_v:
			return value.float_value();
		case mixed_t::kind_t::int_v:
			return float_t(value.int_value());
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
				auto extracted = std::move(value.table_value_);
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
