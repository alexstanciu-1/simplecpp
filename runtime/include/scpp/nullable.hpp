#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"

namespace scpp {

// Optional-value wrapper around std::optional.
//
// Enforces:
// - nullable state is represented explicitly in the scpp type system
// - empty-state comparisons return bool_t
// - comparison of contained values delegates to the wrapped scpp type operators
template <typename T>
class nullable final {
private:
	std::optional<T> value_;

public:
	// Default state is empty.
	nullable() = default;

	// These sentinel constructors normalize both null and nullopt to an empty optional.
	nullable(null_t) noexcept : value_(std::nullopt) {}
	nullable(nullopt_t) noexcept : value_(std::nullopt) {}

	// Value constructors store a present value.
	nullable(const T &value) : value_(value) {}
	nullable(T &&value) : value_(std::move(value)) {}

	// Returns wrapped presence information as bool_t.
	[[nodiscard]] bool_t has_value() const noexcept { return bool_t(value_.has_value()); }

	// Clears the stored value and returns to the empty state.
	void reset() noexcept { value_.reset(); }
	void reset(nullopt_t) noexcept { value_.reset(); }

	// Accesses the contained value.
	// Like std::optional::value(), this throws if no value is present.
	T &value() { return value_.value(); }
	const T &value() const { return value_.value(); }

	// Returns the contained value or a fallback converted to T.
	template <typename U> T value_or(U &&fallback) const { return value_.value_or(static_cast<T>(std::forward<U>(fallback))); }

	// Controlled access to the native optional storage.
	[[nodiscard]] const std::optional<T> &native_value() const noexcept { return value_; }
	[[nodiscard]] std::optional<T> &native_value() noexcept { return value_; }

	// Empty-state comparisons against the generic null sentinel.
	[[nodiscard]] friend bool_t operator==(const nullable<T> &left, null_t) noexcept { return bool_t(!left.value_.has_value()); }
	[[nodiscard]] friend bool_t operator==(null_t, const nullable<T> &right) noexcept { return bool_t(!right.value_.has_value()); }
	[[nodiscard]] friend bool_t operator!=(const nullable<T> &left, null_t) noexcept { return bool_t(left.value_.has_value()); }
	[[nodiscard]] friend bool_t operator!=(null_t, const nullable<T> &right) noexcept { return bool_t(right.value_.has_value()); }

	// Empty-state comparisons against the explicit optional-empty sentinel.
	[[nodiscard]] friend bool_t operator==(const nullable<T> &left, nullopt_t) noexcept { return bool_t(!left.value_.has_value()); }
	[[nodiscard]] friend bool_t operator==(nullopt_t, const nullable<T> &right) noexcept { return bool_t(!right.value_.has_value()); }
	[[nodiscard]] friend bool_t operator!=(const nullable<T> &left, nullopt_t) noexcept { return bool_t(left.value_.has_value()); }
	[[nodiscard]] friend bool_t operator!=(nullopt_t, const nullable<T> &right) noexcept { return bool_t(right.value_.has_value()); }

	// Value equality is implemented manually instead of delegating to std::optional equality
	// because wrapped scpp comparisons return bool_t, not native bool.
	[[nodiscard]] friend bool_t operator==(const nullable<T> &left, const nullable<T> &right) {
		if (!left.value_.has_value() && !right.value_.has_value()) return bool_t(true);
		if (left.value_.has_value() != right.value_.has_value()) return bool_t(false);
		return left.value_.value() == right.value_.value();
	}

	[[nodiscard]] friend bool_t operator!=(const nullable<T> &left, const nullable<T> &right) {
		return bool_t(!(left == right).native_value());
	}
};

} // namespace scpp
