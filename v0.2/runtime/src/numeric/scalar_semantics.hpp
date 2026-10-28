#pragma once
#include <limits>
#include <cmath>
#include <cstdint>
#include <stdexcept>
#include <type_traits>
#include <utility>

namespace scpp::detail {
// Same representability contract as the former checked_integer_cast, expressed
// with the standard signedness-aware range primitive. Private templates only.
template<class To, class From>
To checked_integer_cast(From value) {
    static_assert(std::is_integral_v<To> && std::is_integral_v<From>);
    if (!std::in_range<To>(value)) throw std::out_of_range("integer_cast_out_of_range");
    return static_cast<To>(value);
}

// Preserve native signed arithmetic's defined domain. Unsigned small operands
// must not promote to signed int for multiplication (e.g. uint16 max * max).
template<class Rep>
using integer_calculation = std::conditional_t<std::is_unsigned_v<Rep>,
    std::make_unsigned_t<decltype(+std::declval<Rep>())>, Rep>;

template<class Rep>
Rep integer_add(Rep left, Rep right) noexcept {
    static_assert(std::is_integral_v<Rep>);
    using calculation = integer_calculation<Rep>;
    return static_cast<Rep>(static_cast<calculation>(left) + static_cast<calculation>(right));
}

template<class Rep>
Rep integer_subtract(Rep left, Rep right) noexcept {
    static_assert(std::is_integral_v<Rep>);
    using calculation = integer_calculation<Rep>;
    return static_cast<Rep>(static_cast<calculation>(left) - static_cast<calculation>(right));
}

template<class Rep>
Rep integer_multiply(Rep left, Rep right) noexcept {
    static_assert(std::is_integral_v<Rep>);
    using calculation = integer_calculation<Rep>;
    return static_cast<Rep>(static_cast<calculation>(left) * static_cast<calculation>(right));
}

template<class Rep>
Rep integer_bit_and(Rep left, Rep right) noexcept {
    static_assert(std::is_integral_v<Rep>);
    using calculation = integer_calculation<Rep>;
    return static_cast<Rep>(static_cast<calculation>(left) & static_cast<calculation>(right));
}

template<class Rep>
Rep integer_bit_or(Rep left, Rep right) noexcept {
    static_assert(std::is_integral_v<Rep>);
    using calculation = integer_calculation<Rep>;
    return static_cast<Rep>(static_cast<calculation>(left) | static_cast<calculation>(right));
}

template<class Rep>
Rep integer_bit_xor(Rep left, Rep right) noexcept {
    static_assert(std::is_integral_v<Rep>);
    using calculation = integer_calculation<Rep>;
    return static_cast<Rep>(static_cast<calculation>(left) ^ static_cast<calculation>(right));
}

template<class Rep>
Rep integer_divide(Rep left, Rep right) {
    static_assert(std::is_integral_v<Rep>);
    if (right == 0) throw std::domain_error("division_by_zero");
    return static_cast<Rep>(left / right);
}

template<class Rep>
Rep integer_remainder(Rep left, Rep right) {
    static_assert(std::is_integral_v<Rep>);
    if (right == 0) throw std::domain_error("modulo_by_zero");
    return static_cast<Rep>(left % right);
}

template<class Left, class Right>
bool integer_equal(Left left, Right right) noexcept {
    return std::cmp_equal(left, right);
}

template<class Left, class Right>
bool integer_not_equal(Left left, Right right) noexcept {
    return std::cmp_not_equal(left, right);
}

template<class Left, class Right>
bool integer_less(Left left, Right right) noexcept {
    return std::cmp_less(left, right);
}

template<class Left, class Right>
bool integer_less_equal(Left left, Right right) noexcept {
    return std::cmp_less_equal(left, right);
}

template<class Left, class Right>
bool integer_greater(Left left, Right right) noexcept {
    return std::cmp_greater(left, right);
}

template<class Left, class Right>
bool integer_greater_equal(Left left, Right right) noexcept {
    return std::cmp_greater_equal(left, right);
}

template<class Rep> Rep integer_positive(Rep value) noexcept { return value; }
template<class Rep> Rep integer_negate(Rep value) noexcept { return static_cast<Rep>(-value); }
template<class Rep> Rep integer_bit_not(Rep value) noexcept { return static_cast<Rep>(~value); }
template<class Rep> Rep integer_increment(Rep value) noexcept { return integer_add(value, Rep{1}); }
template<class Rep> Rep integer_decrement(Rep value) noexcept { return integer_subtract(value, Rep{1}); }

// The declared left width bounds the count before native integer promotion.
template<class Left, class Right> void require_shift_count(Right count) {
    constexpr int width = std::numeric_limits<std::make_unsigned_t<Left>>::digits;
    if (std::cmp_less(count, 0) || std::cmp_greater_equal(count, width))
        throw std::domain_error("shift_count_out_of_range");
}
template<class Left, class Right> Left integer_shift_left(Left left, Right right) {
    require_shift_count<Left>(right);
    // Unsigned shifting expresses the C++23 bit pattern without small-integer
    // promotion changing the declared representation's modulo behavior.
    using Unsigned = std::make_unsigned_t<Left>;
    return static_cast<Left>(static_cast<std::uintmax_t>(static_cast<Unsigned>(left)) << right);
}
template<class Left, class Right> Left integer_shift_right(Left left, Right right) {
    require_shift_count<Left>(right);
    return static_cast<Left>(left >> right);
}
template<class To, class From> To numeric_cast(From value) noexcept { return static_cast<To>(value); }
template<class To, class From> To truth_cast(From value) noexcept { return static_cast<To>(value != 0); }

// Adapted from the reviewed legacy floating/integer cast: exclusive powers-of-two
// bounds avoid rounding integer max up before checking. Never cast NaN/infinity.
template<class To, class From> To floating_integer_cast(From value) {
    static_assert(std::is_integral_v<To> && std::is_floating_point_v<From>);
    const auto truncated = std::trunc(value);
    const auto upper = std::ldexp(From{1}, std::numeric_limits<To>::digits);
    const auto lower = std::is_signed_v<To> ? -upper : From{0};
    if (!std::isfinite(value) || truncated < lower || truncated >= upper)
        throw std::out_of_range("integer_cast_out_of_range");
    return static_cast<To>(truncated);
}

template<class Rep> Rep floating_add(Rep left, Rep right) noexcept {
    return left + right;
}
template<class Rep> Rep floating_subtract(Rep left, Rep right) noexcept {
    return left - right;
}
template<class Rep> Rep floating_multiply(Rep left, Rep right) noexcept {
    return left * right;
}
template<class Rep> Rep floating_divide(Rep left, Rep right) {
    if (right == 0) throw std::domain_error("division_by_zero");
    return left / right;
}
template<class Rep> bool floating_equal(Rep left, Rep right) noexcept { return left == right; }
template<class Rep> bool floating_not_equal(Rep left, Rep right) noexcept { return left != right; }
template<class Rep> bool floating_less(Rep left, Rep right) noexcept { return left < right; }
template<class Rep> bool floating_less_equal(Rep left, Rep right) noexcept { return left <= right; }
template<class Rep> bool floating_greater(Rep left, Rep right) noexcept { return left > right; }
template<class Rep> bool floating_greater_equal(Rep left, Rep right) noexcept { return left >= right; }
template<class Rep> Rep floating_positive(Rep value) noexcept { return value; }
template<class Rep> Rep floating_negate(Rep value) noexcept { return -value; }
template<class Rep> Rep floating_increment(Rep value) noexcept { return value + Rep{1}; }
template<class Rep> Rep floating_decrement(Rep value) noexcept { return value - Rep{1}; }
}
