#pragma once

#include "scpp/detail.hpp"

namespace scpp {

// Semantic boolean wrapper used throughout the runtime.
//
// Enforces:
// - boolean values remain inside the scpp type system
// - native implicit truthiness is forbidden to stop accidental fallback to C++ semantics
class bool_t final {
private:
	bool value_;

public:
	constexpr bool_t() noexcept
		: value_(false) {
	}

	explicit constexpr bool_t(bool value) noexcept
		: value_(value) {
	}

	[[nodiscard]] constexpr bool native_value() const noexcept {
		return value_;
	}

	explicit constexpr operator bool() const noexcept {
		return value_;
	}
};

} // namespace scpp
