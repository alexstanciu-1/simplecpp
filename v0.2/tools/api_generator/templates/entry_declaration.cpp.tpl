#pragma once
#include <limits.h>
#include <stdint.h>

namespace scpp {
namespace detail {
// Public constexpr definition is required to check the application compiler's
// actual int width, including targets where int has padding bits.
constexpr int native_int_digits() noexcept
{
    unsigned int value = INT_MAX;
    int digits = 0;
    while (value != 0) {
        ++digits;
        value >>= 1;
    }
    return digits;
}
}
// The argument is evaluated once; an out-of-range value throws.
int entry_exit_checked(::int64_t value);
}

// Validate the source preparation target against the consuming C++ compiler.
#define SCPP_REQUIRE_ENTRY_TARGET(digits) \
    static_assert(INT_MIN < 0 && ::scpp::detail::native_int_digits() == (digits), \
                  "Entry target does not match prepared native int")
