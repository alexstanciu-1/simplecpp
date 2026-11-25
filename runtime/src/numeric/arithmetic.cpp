#include <scpp/arithmetic.hpp>
#include "scalar_semantics.hpp"
#include <bit>
#include <cmath>

namespace scpp {
::int64_t int64_divide(::int64_t left, ::int64_t right)
{
    return detail::integer_divide(left, right);
}

::int64_t int64_remainder(::int64_t left, ::int64_t right)
{
    return detail::integer_remainder(left, right);
}
::uint8_t uint8_divide(::uint8_t left, ::uint8_t right)
{
    return detail::integer_divide(left, right);
}

::uint8_t uint8_remainder(::uint8_t left, ::uint8_t right)
{
    return detail::integer_remainder(left, right);
}

::int64_t int64_shift_left(::int64_t left, ::int64_t count)
{
    return detail::integer_shift_left(left, count);
}

::int64_t int64_shift_right(::int64_t left, ::int64_t count)
{
    return detail::integer_shift_right(left, count);
}

::uint8_t uint8_shift_left(::uint8_t left, ::int64_t count)
{
    return detail::integer_shift_left(left, count);
}

::uint8_t uint8_shift_right(::uint8_t left, ::int64_t count)
{
    return detail::integer_shift_right(left, count);
}

double float64_divide(double left, double right)
{
    return detail::floating_divide(left, right);
}
namespace {
::uint64_t magnitude(::int64_t value)
{
    const auto bits = static_cast<::uint64_t>(value);
    return value < 0 ? ::uint64_t{0} - bits : bits;
}

// Scale the exact ratio to a binary64 significand before rounding it once.
double rounded_ratio(::uint64_t numerator, ::uint64_t denominator)
{
    constexpr int fraction_bits = 52;
    using Wide = __uint128_t;
    int exponent = static_cast<int>(std::bit_width(numerator)) - static_cast<int>(std::bit_width(denominator));
    const bool below_power = exponent >= 0
        ? Wide{numerator} < (Wide{denominator} << exponent)
        : (Wide{numerator} << -exponent) < denominator;
    if (below_power) --exponent;

    const int shift = fraction_bits - exponent;
    Wide dividend = numerator;
    Wide divisor = denominator;
    if (shift >= 0) dividend <<= shift;
    else divisor <<= -shift;

    auto significand = static_cast<::uint64_t>(dividend / divisor);
    const auto twice_remainder = (dividend % divisor) * 2;
    if (twice_remainder > divisor || (twice_remainder == divisor && (significand & 1U))) ++significand;
    return std::ldexp(static_cast<double>(significand), exponent - fraction_bits);
}
}

double int64_true_divide(::int64_t left, ::int64_t right)
{
    if (right == 0) throw std::domain_error("division_by_zero");
    const auto numerator = magnitude(left);
    const auto denominator = magnitude(right);
    constexpr auto exact_limit = ::uint64_t{1} << std::numeric_limits<double>::digits;
    if (numerator <= exact_limit && denominator <= exact_limit) {
        return static_cast<double>(left) / static_cast<double>(right);
    }
    const double quotient = numerator == 0 ? 0.0 : rounded_ratio(numerator, denominator);
    return (left < 0) != (right < 0) ? -quotient : quotient;
}

::int64_t int64_floor_divide(::int64_t left, ::int64_t right)
{
    if (right == 0) throw std::domain_error("division_by_zero");
    if (left == std::numeric_limits<::int64_t>::min() && right == -1) {
        throw std::overflow_error("division_overflow");
    }
    const auto quotient = left / right;
    const auto remainder = left % right;
    return remainder != 0 && (remainder < 0) != (right < 0) ? quotient - 1 : quotient;
}

::int64_t int64_floor_remainder(::int64_t left, ::int64_t right)
{
    if (right == 0) throw std::domain_error("modulo_by_zero");
    if (right == -1) return 0;
    const auto remainder = left % right;
    return remainder != 0 && (remainder < 0) != (right < 0) ? remainder + right : remainder;
}

::int64_t float64_compare(double left, double right)
{
    if (std::isnan(left) || std::isnan(right)) throw std::domain_error("unordered_comparison");
    return static_cast<::int64_t>((left > right) - (left < right));
}

namespace {
// Unsigned magnitudes cover abs(INT64_MIN); check before multiplying.
::uint64_t power_product(::uint64_t left, ::uint64_t right, ::uint64_t limit)
{
    if (right != 0 && left > limit / right) throw std::runtime_error("power_overflow");
    return left * right;
}
}

::int64_t int64_power(::int64_t base, ::int64_t exponent)
{
    if (exponent < 0) throw std::runtime_error("power_negative_exponent");
    auto remaining = static_cast<::uint64_t>(exponent);
    const bool negative = base < 0 && (remaining & 1U) != 0;
    const auto maximum = static_cast<::uint64_t>(std::numeric_limits<::int64_t>::max());
    const auto limit = negative ? maximum + 1U : maximum;
    auto factor = base < 0 ? ::uint64_t{0} - static_cast<::uint64_t>(base) : static_cast<::uint64_t>(base);
    ::uint64_t result = 1;
    while (remaining != 0) {
        if ((remaining & 1U) != 0) result = power_product(result, factor, limit);
        remaining >>= 1U;
        if (remaining != 0) factor = power_product(factor, factor, limit);
    }
    if (negative && result == maximum + 1U) return std::numeric_limits<::int64_t>::min();
    const auto magnitude = static_cast<::int64_t>(result);
    return negative ? -magnitude : magnitude;
}

}
