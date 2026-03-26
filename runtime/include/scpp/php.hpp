#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/weak_p.hpp"

#include <chrono>
#include <cstdint>
#include <iomanip>
#include <fstream>
#include <iostream>
#include <limits>
#include <sstream>
#include <string>
#include <type_traits>
#include <utility>
#if defined(__unix__) || defined(__APPLE__)
#include <sys/resource.h>
#endif

namespace scpp::php {

// PHP compatibility constants consumed by generated code.
inline const int_t PHP_INT_MAX{static_cast<std::int64_t>(std::numeric_limits<std::int64_t>::max())};

// Implements PHP microtime() string mode.
// How: system_clock is sampled once, then formatted as "0.xxxxxxxx seconds" to mirror PHP's default contract.
inline string_t microtime() {
	const auto now = std::chrono::system_clock::now();
	const auto since_epoch = now.time_since_epoch();
	const auto micros_total = std::chrono::duration_cast<std::chrono::microseconds>(since_epoch).count();
	const auto seconds = micros_total / static_cast<std::int64_t>(1000000);
	const auto micros_part = micros_total % static_cast<std::int64_t>(1000000);

	std::ostringstream stream;
	stream << std::fixed << std::setprecision(8)
		<< (static_cast<double>(micros_part) / 1000000.0)
		<< ' ' << seconds;
	return string_t(stream.str());
}

// Implements the numeric branch of PHP microtime(true).
// How: the helper is split out explicitly because the current runtime does not model a string|float union return type for one overload.
inline float_t microtime(bool_t as_float) {
	if (!as_float.native_value()) {
		throw std::logic_error("microtime(false) is not supported; use microtime() for string form");
	}

	const auto now = std::chrono::system_clock::now();
	const auto since_epoch = now.time_since_epoch();
	const auto micros_total = std::chrono::duration_cast<std::chrono::microseconds>(since_epoch).count();
	return float_t(static_cast<double>(micros_total) / 1000000.0);
}

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

template <typename Fn>
requires requires (Fn &&fn) {
	std::forward<Fn>(fn)();
}
// Evaluates one deferred echo operand and prints it.
// How: the thunk form preserves PHP left-to-right operand evaluation when the generator wants one logical echo call.
inline void echo_eval_one(Fn &&fn) {
	echo_one(std::forward<Fn>(fn)());
}

template <typename... Fns>
// Evaluates deferred echo operands left-to-right and prints them.
// How: a comma-fold over thunk invocations preserves sequencing while still allowing the generator to emit one runtime call.
inline void echo_eval(Fns &&...fns) {
	(echo_eval_one(std::forward<Fns>(fns)), ...);
}

// Implements PHP strict identity for two null sentinels.
// How: strict identity treats identical null sentinels as equal without consulting wrapper operator overloads.
inline bool_t identical(null_t, null_t) {
	return bool_t(true);
}

// Implements PHP strict identity between null and nullable<T> when the nullable is empty.
// How: this is the one cross-type exception to the exact-type identity rule currently adopted by the runtime.
template <typename T>
inline bool_t identical(null_t, const nullable<T> &right) {
	return bool_t(!right.has_value().native_value());
}

// Implements PHP strict identity between nullable<T> and null when the nullable is empty.
// How: this is the symmetric form of the null-vs-nullable exception.
template <typename T>
inline bool_t identical(const nullable<T> &left, null_t) {
	return bool_t(!left.has_value().native_value());
}

// Implements PHP strict identity for two nullable values of the same exact type.
// How: empty state matches empty state; present values recurse into the same identity helper for the contained exact type.
template <typename T>
inline bool_t identical(const nullable<T> &left, const nullable<T> &right) {
	if (!left.has_value().native_value() && !right.has_value().native_value()) {
		return bool_t(true);
	}
	if (left.has_value().native_value() != right.has_value().native_value()) {
		return bool_t(false);
	}
	return identical(left.value(), right.value());
}

// Implements PHP strict identity between null and shared ownership wrappers.
// How: an empty shared handle represents PHP null at the runtime comparison layer.
template <typename T>
inline bool_t identical(null_t, const shared_p<T> &right) {
	return bool_t(!right.has_value().native_value());
}

// Implements PHP strict identity between shared ownership wrappers and null.
// How: an empty shared handle represents PHP null at the runtime comparison layer.
template <typename T>
inline bool_t identical(const shared_p<T> &left, null_t) {
	return bool_t(!left.has_value().native_value());
}

// Implements PHP strict identity between null and unique ownership wrappers.
// How: an empty unique handle represents PHP null at the runtime comparison layer.
template <typename T>
inline bool_t identical(null_t, const unique_p<T> &right) {
	return bool_t(!right.has_value().native_value());
}

// Implements PHP strict identity between unique ownership wrappers and null.
// How: an empty unique handle represents PHP null at the runtime comparison layer.
template <typename T>
inline bool_t identical(const unique_p<T> &left, null_t) {
	return bool_t(!left.has_value().native_value());
}

// Implements PHP strict identity for shared ownership wrappers using object identity.
// How: aliases are identical only when they point at the exact same managed object.
template <typename T>
inline bool_t identical(const shared_p<T> &left, const shared_p<T> &right) {
	return bool_t(left.get() == right.get());
}

// Implements PHP strict identity for unique ownership wrappers using object identity.
// How: the comparison observes the managed object address rather than any pointed-to value.
template <typename T>
inline bool_t identical(const unique_p<T> &left, const unique_p<T> &right) {
	return bool_t(left.get() == right.get());
}

// Implements PHP strict identity for same-type runtime values not needing special object/null handling.
// How: the helper keeps strict comparison in the PHP helper layer and delegates exact-type value equality to the runtime operator surface.
template <typename T>
inline bool_t identical(const T &left, const T &right) {
	return left == right;
}

// Implements PHP strict identity for differing runtime value categories.
// How: the helper returns false because strict identity currently requires exact type equality except for null vs nullable<T>.
template <typename Left, typename Right>
requires (!std::is_same_v<std::remove_cvref_t<Left>, std::remove_cvref_t<Right>>)
inline bool_t identical(const Left &, const Right &) {
	return bool_t(false);
}

// Implements PHP strict non-identity as the inverse of the strict identity helper.
// How: one source of truth avoids drift between special-case identical overloads and their negated form.
template <typename Left, typename Right>
inline bool_t not_identical(const Left &left, const Right &right) {
	return !identical(left, right);
}

// Implements PHP-style concatenation assignment for wrapped strings.
// How: the helper mutates the left-hand side in place through string_t::append and returns the updated wrapper by reference.
inline string_t &concat_assign(string_t &left, const string_t &right) {
	left.append(right);
	return left;
}

// Implements PHP count() for the currently supported vector wrapper subset.
// How: returns the runtime vector size widened into the standard int_t wrapper used by generated code.
template <typename T>
inline int_t count(const vector_t<T> &value) {
	return int_t(static_cast<std::int64_t>(value.size()));
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
	&& !requires (const std::remove_cvref_t<T> &value) {
		value.has_value();
	}
	&& !requires (const std::remove_cvref_t<T> &value) {
		value.expired();
	}
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

namespace detail {

// Deleted fallback used to keep unset semantics explicit at the runtime boundary.
// How: unsupported/custom types fail at compile time instead of silently inventing semantics.
template <typename T>
inline void apply_unset(T &) = delete;

// Implements one-value unset semantics used by the variadic unset helper.
// How: nullable wrappers drop back to the empty state immediately.
template <typename T>
inline void apply_unset(nullable<T> &value) {
	value.reset();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: shared ownership wrappers release the current managed object immediately.
template <typename T>
inline void apply_unset(shared_p<T> &value) {
	value.reset();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: unique ownership wrappers release the current managed object immediately.
template <typename T>
inline void apply_unset(unique_p<T> &value) {
	value.reset();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: weak wrappers forget the current observation target immediately.
template <typename T>
inline void apply_unset(weak_p<T> &value) {
	value.reset();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: the wrapped string owns its storage and clears it through the dedicated runtime hook.
inline void apply_unset(string_t &value) {
	value._unset_();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: the wrapped vector owns its storage and clears it through the dedicated runtime hook.
template <typename T>
inline void apply_unset(vector_t<T> &value) {
	value._unset_();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: integer wrappers reset to the runtime zero state.
inline void apply_unset(int_t &value) {
	value = int_t();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: floating-point wrappers reset to the runtime zero state.
inline void apply_unset(float_t &value) {
	value = float_t();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: boolean wrappers reset to false.
inline void apply_unset(bool_t &value) {
	value = bool_t();
}


// Reads one numeric memory field from /proc/self/status when available.
// How: Linux exposes resident and peak resident process memory in kilobytes through VmRSS and VmHWM.
[[nodiscard]] inline std::int64_t read_proc_status_kb(const char *field_name) {
	std::ifstream input("/proc/self/status");
	if (!input.is_open()) {
		return static_cast<std::int64_t>(-1);
	}

	std::string line;
	while (std::getline(input, line)) {
		if (line.rfind(field_name, 0) != 0) {
			continue;
		}

		std::istringstream stream(line.substr(std::char_traits<char>::length(field_name)));
		std::int64_t value_kb = 0;
		std::string unit;
		if (stream >> value_kb >> unit) {
			return value_kb;
		}
		return static_cast<std::int64_t>(-1);
	}

	return static_cast<std::int64_t>(-1);
}

// Returns the current resident process memory in bytes when the platform exposes it.
// How: Linux uses VmRSS; unsupported platforms fall back to zero because the runtime does not track allocator-internal usage yet.
[[nodiscard]] inline std::int64_t process_memory_usage_bytes() {
#if defined(__linux__)
	const std::int64_t value_kb = read_proc_status_kb("VmRSS:");
	if (value_kb >= 0) {
		return value_kb * static_cast<std::int64_t>(1024);
	}
#endif
	return static_cast<std::int64_t>(0);
}

// Returns the peak resident process memory in bytes when the platform exposes it.
// How: Linux prefers VmHWM; Unix-like fallbacks use getrusage where ru_maxrss is defined in kilobytes on Linux and bytes on macOS/BSD.
[[nodiscard]] inline std::int64_t process_peak_memory_usage_bytes() {
#if defined(__linux__)
	const std::int64_t value_kb = read_proc_status_kb("VmHWM:");
	if (value_kb >= 0) {
		return value_kb * static_cast<std::int64_t>(1024);
	}
#endif
#if defined(__unix__) || defined(__APPLE__)
	struct rusage usage {};
	if (getrusage(RUSAGE_SELF, &usage) == 0) {
		#if defined(__APPLE__)
		return static_cast<std::int64_t>(usage.ru_maxrss);
		#else
		return static_cast<std::int64_t>(usage.ru_maxrss) * static_cast<std::int64_t>(1024);
		#endif
	}
#endif
	return static_cast<std::int64_t>(0);
}

} // namespace detail

// Implements the lowered unset helper for the currently supported mutable wrappers.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
template <typename... Args>
inline void unset(Args &...args) {
	(detail::apply_unset(args), ...);
}

// Implements PHP memory_get_usage() in a process-level, benchmark-oriented form.
// How: the runtime currently reports resident process memory in bytes rather than Zend allocator internals.
[[nodiscard]] inline int_t memory_get_usage() {
	return int_t(detail::process_memory_usage_bytes());
}

// Implements PHP memory_get_usage(true|false) with the current prototype semantics.
// How: the bool parameter is accepted for PHP surface compatibility, but both branches currently return the same process-level byte count.
[[nodiscard]] inline int_t memory_get_usage(bool_t) {
	return int_t(detail::process_memory_usage_bytes());
}

// Implements PHP memory_get_peak_usage() in a process-level, benchmark-oriented form.
// How: the runtime currently reports peak resident process memory in bytes rather than Zend allocator internals.
[[nodiscard]] inline int_t memory_get_peak_usage() {
	return int_t(detail::process_peak_memory_usage_bytes());
}

// Implements PHP memory_get_peak_usage(true|false) with the current prototype semantics.
// How: the bool parameter is accepted for PHP surface compatibility, but both branches currently return the same process-level byte count.
[[nodiscard]] inline int_t memory_get_peak_usage(bool_t) {
	return int_t(detail::process_peak_memory_usage_bytes());
}

// Temporary lifetime-audit helper.
// How: exposes the visible strong-owner count for shared/weak wrappers so tests can prove whether a hidden strong alias still exists.
template <typename T>
[[nodiscard]] inline long debug_use_count(const shared_p<T> &value) {
	return value.debug_use_count();
}

template <typename T>
[[nodiscard]] inline long debug_use_count(const weak_p<T> &value) {
	return value.debug_use_count();
}

// Implements PHP-style weak reference creation for shared-owned objects.
// How: weak observers are modeled directly with weak_p so generated code does not need a second wrapper family.
template <typename T>
inline weak_p<T> weakref(const shared_p<T> &value) {
	return weak_p<T>(value);
}

// Implements PHP-style weak reference readback.
// How: locking a weak observer yields a shared handle, and empty state is represented by a null shared_p sentinel.
template <typename T>
inline shared_p<T> weakref_get(const weak_p<T> &value) {
	return value.lock();
}

} // namespace scpp::php
