static_assert(std::numeric_limits<double>::is_iec559 && std::numeric_limits<double>::radix == 2 && std::numeric_limits<double>::digits == 53 && std::numeric_limits<double>::max_exponent == 1024);
static_assert(sizeof(float64) == sizeof(double));
