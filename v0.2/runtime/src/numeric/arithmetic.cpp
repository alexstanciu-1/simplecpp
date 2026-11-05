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
double float64_divide(double left, double right)
{
    return detail::floating_divide(left, right);
}
}
