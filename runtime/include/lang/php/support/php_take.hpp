#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/error_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/result.hpp"
#include "scpp/result_or_bool.hpp"
#include "scpp/result_or_false.hpp"

namespace scpp::php {

// Unified guarded-value extraction helper for nullable<T>.
// Success assigns the wrapped value and returns true; empty state leaves the output unchanged and returns false.
template <typename T>
[[nodiscard]] inline bool_t take(T &out_value, const nullable<T> &source) {
	if (!source.has_value().native_value()) {
		return bool_t(false);
	}

	out_value = source.require_value("php::take(nullable)");
	return bool_t(true);
}

// Unified guarded-value extraction helper for result_or_false<T>.
// Success assigns the wrapped value and returns true; false state leaves the output unchanged and returns false.
template <typename T>
[[nodiscard]] inline bool_t take(T &out_value, const result_or_false<T> &source) {
	if (!source.has_value().native_value()) {
		return bool_t(false);
	}

	out_value = source.require_value("php::take(result_or_false)");
	return bool_t(true);
}

// Unified guarded-value extraction helper for result<T>.
// Success assigns the wrapped value and returns true; error state assigns the error output and returns false.
template <typename T>
[[nodiscard]] inline bool_t take(T &out_value, error_t &out_error, const result<T> &source) {
	if (source.has_value().native_value()) {
		out_value = source.require_value("php::take(result)");
		return bool_t(true);
	}

	out_error = source.require_error("php::take(result)");
	return bool_t(false);
}

// Unified guarded-value extraction helper for result_or_bool<T>.
// Value state assigns the wrapped value and returns true.
// True state returns true and assigns the bool output.
// False state returns false and assigns the bool output.
template <typename T>
[[nodiscard]] inline bool_t take(T &out_value, bool_t &out_bool, const result_or_bool<T> &source) {
	if (source.has_value().native_value()) {
		out_value = source.require_value("php::take(result_or_bool)");
		return bool_t(true);
	}

	if (source.is_true().native_value()) {
		out_bool = bool_t(true);
		return bool_t(true);
	}

	out_bool = bool_t(false);
	return bool_t(false);
}

} // namespace scpp::php
