#include <scpp/arithmetic.hpp>
#include "scalar_semantics.hpp"

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

double float64_divide(double left, double right)
{
    return detail::floating_divide(left, right);
}
}
