#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"

namespace scpp {

// Semantic string wrapper.
//
// Enforces:
// - source-level strings stay inside scpp::string_t
// - comparisons return bool_t
// - append/concatenation use the wrapped string representation
class string_t final {
private:
	std::string value_;

public:
	// Stable core API.
	string_t() = default;

	// Explicit constructors prevent accidental native string conversions.
	explicit string_t(std::string value)
		: value_(std::move(value)) {
	}

	// Implements `string_t` as part of the runtime surface consumed by generated Simple C++ code.
	// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
	explicit string_t(std::string_view value)
		: value_(value) {
	}

	// Null C-string pointers are normalized to the empty string.
	explicit string_t(const char *value)
		: value_(value != nullptr ? value : "") {
	}

	// Controlled access to the native string.
	[[nodiscard]] const std::string &native_value() const noexcept {
		return value_;
	}

	// Size remains native std::size_t because it models container size, not language arithmetic.
	[[nodiscard]] std::size_t size() const noexcept {
		return value_.size();
	}

	// Emptiness is wrapped as bool_t for consistency with runtime boolean semantics.
	[[nodiscard]] bool_t empty() const noexcept {
		return bool_t(value_.empty());
	}

	// In-place append on the wrapped value.
	void append(const string_t &value) {
		value_ += value.value_;
	}

	// Concatenation produces a new wrapped string.
	[[nodiscard]] friend string_t operator+(const string_t &left, const string_t &right) {
		return string_t(left.value_ + right.value_);
	}

	// Equality and inequality stay in the runtime boolean domain.
	[[nodiscard]] friend bool_t operator==(const string_t &left, const string_t &right) noexcept {
		return bool_t(left.value_ == right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator!=(const string_t &left, const string_t &right) noexcept {
		return bool_t(left.value_ != right.value_);
	}
};

} // namespace scpp
