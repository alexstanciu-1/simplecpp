#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"

namespace scpp {

// Semantic floating-point wrapper implementing the configured numeric widening rules.
// Spec link: this type centralizes behavior so generated code follows runtime/specs/spec.md instead of raw STL semantics.
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

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr int_t operator-(const int_t &value) noexcept {
		return int_t(-value.value_);
	}

	// Implements the configured bitwise complement operator for integer wrappers.
	// How: the overload preserves C++ integer semantics while keeping the result in scpp::int_t.
	[[nodiscard]] friend constexpr int_t operator~(const int_t &value) noexcept {
		return int_t(~value.value_);
	}

	// Binary arithmetic is defined only for configured pairs and returns int_t here.
	[[nodiscard]] friend constexpr int_t operator+(const int_t &left, const int_t &right) noexcept {
		return int_t(left.value_ + right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr int_t operator-(const int_t &left, const int_t &right) noexcept {
		return int_t(left.value_ - right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr int_t operator*(const int_t &left, const int_t &right) noexcept {
		return int_t(left.value_ * right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr int_t operator/(const int_t &left, const int_t &right) noexcept {
		return int_t(left.value_ / right.value_);
	}

	// Implements the configured integer modulo operator.
	// How: the overload follows native C++ integer remainder behavior and keeps the result in scpp::int_t.
	[[nodiscard]] friend constexpr int_t operator%(const int_t &left, const int_t &right) noexcept {
		return int_t(left.value_ % right.value_);
	}

	// Implements the configured integer bitwise-and operator.
	// How: the overload follows native C++ integer bitwise semantics and keeps the result in scpp::int_t.
	[[nodiscard]] friend constexpr int_t operator&(const int_t &left, const int_t &right) noexcept {
		return int_t(left.value_ & right.value_);
	}

	// Implements the configured integer bitwise-or operator.
	// How: the overload follows native C++ integer bitwise semantics and keeps the result in scpp::int_t.
	[[nodiscard]] friend constexpr int_t operator|(const int_t &left, const int_t &right) noexcept {
		return int_t(left.value_ | right.value_);
	}

	// Implements the configured integer bitwise-xor operator.
	// How: the overload follows native C++ integer bitwise semantics and keeps the result in scpp::int_t.
	[[nodiscard]] friend constexpr int_t operator^(const int_t &left, const int_t &right) noexcept {
		return int_t(left.value_ ^ right.value_);
	}

	// Implements the configured integer left-shift operator.
	// How: the overload follows native C++ shift semantics and keeps the result in scpp::int_t.
	[[nodiscard]] friend constexpr int_t operator<<(const int_t &left, const int_t &right) noexcept {
		return int_t(left.value_ << right.value_);
	}

	// Implements the configured integer right-shift operator.
	// How: the overload follows native C++ shift semantics and keeps the result in scpp::int_t.
	[[nodiscard]] friend constexpr int_t operator>>(const int_t &left, const int_t &right) noexcept {
		return int_t(left.value_ >> right.value_);
	}

	// Generated comparisons.
	// All comparison results stay in the runtime domain as bool_t.
	[[nodiscard]] friend constexpr bool_t operator==(const int_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ == right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator!=(const int_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ != right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator<(const int_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ < right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator<=(const int_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ <= right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator>(const int_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ > right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend constexpr bool_t operator>=(const int_t &left, const int_t &right) noexcept {
		return bool_t(left.value_ >= right.value_);
	}

	// Implements the configured prefix increment operator.
	// How: the overload mutates the wrapped integer in place and returns the updated wrapper by reference.
	constexpr int_t &operator++() noexcept {
		++value_;
		return *this;
	}

	// Implements the configured postfix increment operator.
	// How: the overload preserves C++ postfix semantics by returning the pre-increment snapshot.
	constexpr int_t operator++(int) noexcept {
		const int_t snapshot(*this);
		++value_;
		return snapshot;
	}

	// Implements the configured prefix decrement operator.
	// How: the overload mutates the wrapped integer in place and returns the updated wrapper by reference.
	constexpr int_t &operator--() noexcept {
		--value_;
		return *this;
	}

	// Implements the configured postfix decrement operator.
	// How: the overload preserves C++ postfix semantics by returning the pre-decrement snapshot.
	constexpr int_t operator--(int) noexcept {
		const int_t snapshot(*this);
		--value_;
		return snapshot;
	}

	// Implements compound addition assignment for the integer wrapper.
	// How: the overload mutates the wrapped integer in place and returns the left-hand side by reference.
	constexpr int_t &operator+=(const int_t &right) noexcept {
		value_ += right.value_;
		return *this;
	}

	// Implements compound subtraction assignment for the integer wrapper.
	// How: the overload mutates the wrapped integer in place and returns the left-hand side by reference.
	constexpr int_t &operator-=(const int_t &right) noexcept {
		value_ -= right.value_;
		return *this;
	}

	// Implements compound multiplication assignment for the integer wrapper.
	// How: the overload mutates the wrapped integer in place and returns the left-hand side by reference.
	constexpr int_t &operator*=(const int_t &right) noexcept {
		value_ *= right.value_;
		return *this;
	}

	// Implements compound division assignment for the integer wrapper.
	// How: the overload follows native C++ integer division semantics and returns the left-hand side by reference.
	constexpr int_t &operator/=(const int_t &right) noexcept {
		value_ /= right.value_;
		return *this;
	}

	// Implements compound modulo assignment for the integer wrapper.
	// How: the overload follows native C++ integer remainder semantics and returns the left-hand side by reference.
	constexpr int_t &operator%=(const int_t &right) noexcept {
		value_ %= right.value_;
		return *this;
	}

	// Implements compound bitwise-and assignment for the integer wrapper.
	// How: the overload follows native C++ integer bitwise semantics and returns the left-hand side by reference.
	constexpr int_t &operator&=(const int_t &right) noexcept {
		value_ &= right.value_;
		return *this;
	}

	// Implements compound bitwise-or assignment for the integer wrapper.
	// How: the overload follows native C++ integer bitwise semantics and returns the left-hand side by reference.
	constexpr int_t &operator|=(const int_t &right) noexcept {
		value_ |= right.value_;
		return *this;
	}

	// Implements compound bitwise-xor assignment for the integer wrapper.
	// How: the overload follows native C++ integer bitwise semantics and returns the left-hand side by reference.
	constexpr int_t &operator^=(const int_t &right) noexcept {
		value_ ^= right.value_;
		return *this;
	}

	// Implements compound left-shift assignment for the integer wrapper.
	// How: the overload follows native C++ shift semantics and returns the left-hand side by reference.
	constexpr int_t &operator<<=(const int_t &right) noexcept {
		value_ <<= right.value_;
		return *this;
	}

	// Implements compound right-shift assignment for the integer wrapper.
	// How: the overload follows native C++ shift semantics and returns the left-hand side by reference.
	constexpr int_t &operator>>=(const int_t &right) noexcept {
		value_ >>= right.value_;
		return *this;
	}
};

} // namespace scpp
