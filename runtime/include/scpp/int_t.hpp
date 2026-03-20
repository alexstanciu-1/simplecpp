#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"

namespace scpp {

class float_t;

// Semantic signed 64-bit integer wrapper.
//
// Enforces:
// - integer arithmetic is performed through scpp::int_t rather than raw int64_t
// - comparisons return scpp::bool_t
// - construction from native integral storage is explicit
class int_t final {
private:
	std::int64_t value_;

public:
	// Stable core API.
	// Default state is numeric zero.
	constexpr int_t() noexcept
		: value_(0) {
	}

	// Explicit so native integer use remains intentional at API boundaries.
	explicit constexpr int_t(std::int64_t value) noexcept
		: value_(value) {
	}

	// Gives controlled access to the native representation.
	[[nodiscard]] constexpr std::int64_t native_value() const noexcept {
		return value_;
	}

	// Generated arithmetic.
	// Unary operators preserve the wrapped integer domain.
	[[nodiscard]] friend constexpr int_t operator+(const int_t &value) noexcept {
		return int_t(+value.value_);
	}

	[[nodiscard]] friend constexpr int_t operator-(const int_t &value) noexcept {
		return int_t(-value.value_);
	}

	// Binary arithmetic is defined only for configured pairs and returns int_t here.
	[[nodiscard]] friend constexpr int_t operator+(const int_t &left, const int_t &right) noexcept {
		return int_t(left.value_ + right.value_);
	}

	[[nodiscard]] friend constexpr int_t operator-(const int_t &left, const int_t &right) noexcept {
		return int_t(left.value_ - right.value_);
	}

	[[nodiscard]] friend constexpr int_t operator*(const int_t &left, const int_t &right) noexcept {
		return int_t(left.value_ * right.value_);
	}

	[[nodiscard]] friend constexpr int_t operator/(const int_t &left, const int_t &right) noexcept {
		return int_t(left.value_ / right.value_);
	}

	// Generated comparisons.
	// All comparison results stay in the runtime domain as bool_t.
	[[nodiscard]] friend constexpr bool_t operator==(const int_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ == right.value_);
	}

	[[nodiscard]] friend constexpr bool_t operator!=(const int_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ != right.value_);
	}

	[[nodiscard]] friend constexpr bool_t operator<(const int_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ < right.value_);
	}

	[[nodiscard]] friend constexpr bool_t operator<=(const int_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ <= right.value_);
	}

	[[nodiscard]] friend constexpr bool_t operator>(const int_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ > right.value_);
	}

	[[nodiscard]] friend constexpr bool_t operator>=(const int_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ >= right.value_);
	}
};

} // namespace scpp
