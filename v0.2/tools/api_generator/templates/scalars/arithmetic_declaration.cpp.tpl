#pragma once
#include <stdint.h>

namespace scpp {
// Native int64 arithmetic with existing zero-divisor errors; signed overflow is not checked.
::int64_t int64_divide(::int64_t left, ::int64_t right);
::int64_t int64_remainder(::int64_t left, ::int64_t right);
// Unsigned narrow results preserve their representation; zero divisors are rejected.
::uint8_t uint8_divide(::uint8_t left, ::uint8_t right);
::uint8_t uint8_remainder(::uint8_t left, ::uint8_t right);
// Checked counts use the declared left width; the active integer count domains fit int64.
::int64_t int64_shift_left(::int64_t left, ::int64_t count);
::int64_t int64_shift_right(::int64_t left, ::int64_t count);
::uint8_t uint8_shift_left(::uint8_t left, ::int64_t count);
::uint8_t uint8_shift_right(::uint8_t left, ::int64_t count);
@optional_helpers@// Binary64 division rejects both signs of zero; other results follow native IEEE arithmetic.
double float64_divide(double left, double right);
// Three-way floating comparison rejects unordered NaN operands.
::int64_t float64_compare(double left, double right);
// Exact signed integer power; negative exponents and out-of-range results throw.
::int64_t int64_power(::int64_t left, ::int64_t right);
}
