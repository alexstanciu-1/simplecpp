#pragma once
#include <stdint.h>

namespace scpp {
// Native int64 arithmetic with existing zero-divisor errors; signed overflow is not checked.
::int64_t int64_divide(::int64_t left, ::int64_t right);
::int64_t int64_remainder(::int64_t left, ::int64_t right);
// Binary64 division rejects both signs of zero; other results follow native IEEE arithmetic.
double float64_divide(double left, double right);
}
