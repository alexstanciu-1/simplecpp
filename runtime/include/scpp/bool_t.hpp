#pragma once

#include "scpp/detail.hpp"

namespace scpp {

// Semantic boolean wrapper used throughout the runtime.
//
// Enforces:
// - boolean values remain inside the scpp type system
// - logical/comparison operations return scpp::bool_t, not native bool
// - native implicit truthiness is forbidden to stop accidental fallback to C++ semantics
class bool_t final {
private:
	bool value_;

public:
	// Stable core API.
	// Default-initializes to false so the wrapper has deterministic zero-state semantics.
	constexpr bool_t() noexcept
		: value_(false) {
	}

	// Explicit on purpose: native bool must be wrapped intentionally.
	explicit constexpr bool_t(bool value) noexcept
		: value_(value) {
	}

	// Exposes the underlying C++ value for controlled bridges back to native code.
	[[nodiscard]] constexpr bool native_value() const noexcept {
		return value_;
	}

	// Generated logical operators.
	// These preserve scpp semantics by returning bool_t rather than bool.
	[[nodiscard]] friend constexpr bool_t operator!(const bool_t &value) noexcept {
		return bool_t(!value.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator&&(const bool_t &left, const bool_t &right) noexcept {
		return bool_t(left.value_ && right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator||(const bool_t &left, const bool_t &right) noexcept {
		return bool_t(left.value_ || right.value_);
	}

	// Equality is also wrapped so every boolean result stays inside the runtime type system.
	[[nodiscard]] friend constexpr bool_t operator==(const bool_t &left, const bool_t &right) noexcept {
		return bool_t(left.value_ == right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator!=(const bool_t &left, const bool_t &right) noexcept {
		return bool_t(left.value_ != right.value_);
	}

	// Explicit native-bool bridge used only at generator-selected control-flow boundaries.
	// The conversion remains explicit so the semantic wrapper does not silently degrade into native C++ bool.
	explicit constexpr operator bool() const noexcept {
		return value_;
	}
};

} // namespace scpp
