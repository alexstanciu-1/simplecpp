#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/string_t.hpp"

namespace scpp {

// Named cast helper required by the config.
//
// Enforces:
// - only explicitly configured From/To pairs are legal
// - unsupported cast pairs fail at compile time
// - cast behavior is centralized rather than scattered across constructors

template <typename To, typename From>
To cast(const From &value) {
	static_assert(detail::always_false_v<To>, "scpp::cast is not defined for this From/To pair");
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

// int_t -> string_t
// Numeric to string conversion is explicit and centralized here.
template <>
inline string_t cast<string_t, int_t>(const int_t &value) {
	return string_t(std::to_string(value.native_value()));
}

// float_t -> string_t
// Numeric to string conversion is explicit and centralized here.
template <>
inline string_t cast<string_t, float_t>(const float_t &value) {
	return string_t(std::to_string(value.native_value()));
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

} // namespace scpp
