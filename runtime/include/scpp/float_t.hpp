#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/int_t.hpp"

namespace scpp {

// Semantic 64-bit floating-point wrapper.
//
// Enforces:
// - floating-point math is performed inside the scpp type domain
// - configured mixed numeric operations with int_t return float_t
// - comparisons always return bool_t
class float_t final {
private:
	double value_;

public:
	// Stable core API.
	// Default state is 0.0.
	constexpr float_t() noexcept
		: value_(0.0) {
	}

	// Explicit native constructor to keep boundary crossings intentional.
	explicit constexpr float_t(double value) noexcept
		: value_(value) {
	}

	// Configured implicit int_t -> float_t constructor.
	// This exists because the runtime config allows widening from int_t to float_t.
	constexpr float_t(const int_t &value) noexcept
		: value_(static_cast<double>(value.native_value())) {
	}

	// Controlled escape hatch to the native representation.
	[[nodiscard]] constexpr double native_value() const noexcept {
		return value_;
	}

	// Generated arithmetic.
	[[nodiscard]] friend constexpr float_t operator+(const float_t &value) noexcept {
		return float_t(+value.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr float_t operator-(const float_t &value) noexcept {
		return float_t(-value.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr float_t operator+(const float_t &left, const float_t &right) noexcept {
		return float_t(left.value_ + right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr float_t operator-(const float_t &left, const float_t &right) noexcept {
		return float_t(left.value_ - right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr float_t operator*(const float_t &left, const float_t &right) noexcept {
		return float_t(left.value_ * right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr float_t operator/(const float_t &left, const float_t &right) noexcept {
		return float_t(left.value_ / right.value_);
	}

	// Generated comparisons for float_t <-> float_t.
	[[nodiscard]] friend constexpr bool_t operator==(const float_t &left, const float_t &right) noexcept {
		return bool_t(left.value_ == right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator!=(const float_t &left, const float_t &right) noexcept {
		return bool_t(left.value_ != right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator<(const float_t &left, const float_t &right) noexcept {
		return bool_t(left.value_ < right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator<=(const float_t &left, const float_t &right) noexcept {
		return bool_t(left.value_ <= right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator>(const float_t &left, const float_t &right) noexcept {
		return bool_t(left.value_ > right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator>=(const float_t &left, const float_t &right) noexcept {
		return bool_t(left.value_ >= right.value_);
	}

	// Mixed numeric overloads from config.
	// These model configured int_t/float_t interoperability and always produce float_t.
	[[nodiscard]] friend constexpr float_t operator+(const int_t &left, const float_t &right) noexcept {
		return float_t(static_cast<double>(left.native_value()) + right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr float_t operator+(const float_t &left, const int_t &right) noexcept {
		return float_t(left.value_ + static_cast<double>(right.native_value()));
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr float_t operator-(const int_t &left, const float_t &right) noexcept {
		return float_t(static_cast<double>(left.native_value()) - right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr float_t operator-(const float_t &left, const int_t &right) noexcept {
		return float_t(left.value_ - static_cast<double>(right.native_value()));
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr float_t operator*(const int_t &left, const float_t &right) noexcept {
		return float_t(static_cast<double>(left.native_value()) * right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr float_t operator*(const float_t &left, const int_t &right) noexcept {
		return float_t(left.value_ * static_cast<double>(right.native_value()));
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr float_t operator/(const int_t &left, const float_t &right) noexcept {
		return float_t(static_cast<double>(left.native_value()) / right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr float_t operator/(const float_t &left, const int_t &right) noexcept {
		return float_t(left.value_ / static_cast<double>(right.native_value()));
	}

	// Mixed numeric comparisons are also configured explicitly.
	[[nodiscard]] friend constexpr bool_t operator==(const int_t &left, const float_t &right) noexcept {
		return bool_t(static_cast<double>(left.native_value()) == right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator==(const float_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ == static_cast<double>(right.native_value()));
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator!=(const int_t &left, const float_t &right) noexcept {
		return bool_t(static_cast<double>(left.native_value()) != right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator!=(const float_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ != static_cast<double>(right.native_value()));
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator<(const int_t &left, const float_t &right) noexcept {
		return bool_t(static_cast<double>(left.native_value()) < right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator<(const float_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ < static_cast<double>(right.native_value()));
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator<=(const int_t &left, const float_t &right) noexcept {
		return bool_t(static_cast<double>(left.native_value()) <= right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator<=(const float_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ <= static_cast<double>(right.native_value()));
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator>(const int_t &left, const float_t &right) noexcept {
		return bool_t(static_cast<double>(left.native_value()) > right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator>(const float_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ > static_cast<double>(right.native_value()));
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator>=(const int_t &left, const float_t &right) noexcept {
		return bool_t(static_cast<double>(left.native_value()) >= right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator>=(const float_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ >= static_cast<double>(right.native_value()));
	}
};

} // namespace scpp
