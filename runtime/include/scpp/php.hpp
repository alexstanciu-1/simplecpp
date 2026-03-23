#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/weak_p.hpp"

#include <cstdint>
#include <iostream>
#include <limits>
#include <sstream>
#include <type_traits>
#include <utility>

namespace scpp::php {

// PHP compatibility constants consumed by generated code.
inline const int_t PHP_INT_MAX{static_cast<std::int64_t>(std::numeric_limits<std::int64_t>::max())};

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(const string_t &value) {
	return value;
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(const int_t &value) {
	return string_t(std::to_string(value.native_value()));
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(const float_t &value) {
	std::ostringstream stream;
	stream << value.native_value();
	return string_t(stream.str());
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(const bool_t &value) {
	return string_t(value.native_value() ? "1" : "");
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(null_t) {
	return string_t("");
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(nullopt_t) {
	return string_t("");
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(nullptr_t) {
	return string_t("");
}

template <typename T>
// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(const nullable<T> &value) {
	if (!value.has_value().native_value()) {
		return string_t("");
	}
	return to_string(value.value());
}

// Prints one runtime value according to the PHP echo contract implemented by the prototype.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void echo_one(const string_t &value) {
	std::cout << value.native_value();
}

template <typename T>
requires requires (const std::remove_cvref_t<T> &value) {
	{ to_string(value) } -> std::same_as<string_t>;
}
// Prints one runtime value according to the PHP echo contract implemented by the prototype.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void echo_one(T &&value) {
	std::cout << to_string(std::forward<T>(value)).native_value();
}

// Prints one or more values using the runtime echo helpers.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void echo() {
}

template <typename... Args>
// Prints one or more values using the runtime echo helpers.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void echo(Args &&...args) {
	(echo_one(std::forward<Args>(args)), ...);
}


// Implements the lowered isset contract across the currently supported runtime value categories.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset() {
	return bool_t(true);
}

// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(null_t) {
	return bool_t(false);
}

// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(nullopt_t) {
	return bool_t(false);
}

// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(nullptr_t) {
	return bool_t(false);
}

template <typename T>
// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(const nullable<T> &value) {
	return value.has_value();
}

template <typename T>
// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(const shared_p<T> &value) {
	return value.has_value();
}

template <typename T>
// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(const unique_p<T> &value) {
	return value.has_value();
}

template <typename T>
// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(const weak_p<T> &value) {
	return bool_t(!value.expired().native_value());
}

template <typename T>
requires (
	!std::is_same_v<std::remove_cvref_t<T>, null_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, nullopt_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, nullptr_t>
)
// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(T &&) {
	return bool_t(true);
}

template <typename... Args>
// Implements the lowered isset contract across the currently supported runtime value categories.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset(Args &&...args) {
	bool result = true;
	((result = result && isset_one(std::forward<Args>(args)).native_value()), ...);
	return bool_t(result);
}

// Implements the lowered unset helper for the currently supported mutable wrappers.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void unset() {
}

template <typename T>
// Implements one-value unset semantics used by the variadic unset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void unset_one(nullable<T> &value) {
	value.reset();
}

template <typename T>
// Implements one-value unset semantics used by the variadic unset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void unset_one(shared_p<T> &value) {
	value.native_value().reset();
}

template <typename T>
// Implements one-value unset semantics used by the variadic unset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void unset_one(unique_p<T> &value) {
	value.native_value().reset();
}

template <typename T>
// Implements one-value unset semantics used by the variadic unset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void unset_one(weak_p<T> &value) {
	value.native_value().reset();
}

template <typename T>
// Implements one-value unset semantics used by the variadic unset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void unset_one(T &) {
	// No-op for non-nullable value types in the current prototype.
}

template <typename... Args>
// Implements the lowered unset helper for the currently supported mutable wrappers.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void unset(Args &...args) {
	(unset_one(args), ...);
}

} // namespace scpp::php
