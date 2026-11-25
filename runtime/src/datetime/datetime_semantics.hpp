#pragma once
#include <chrono>
#include <cstdint>
#include <limits>
#include <stdexcept>
#include <type_traits>

namespace scpp::detail::datetime {
// Widen BEFORE multiplication. Both a 64-bit count and intmax_t ratio fit in
// signed 128 bits, including uint64 counts. Truncation matches duration_cast.
// This private target-specific implementation is supported by the Clang build.
template<class ToDuration, class Rep, class Period>
auto checked_duration_count(std::chrono::duration<Rep, Period> duration) -> typename ToDuration::rep {
    using To = typename ToDuration::rep;
    using Scale = std::ratio_divide<Period, typename ToDuration::period>;
    static_assert(std::is_integral_v<Rep> && std::is_integral_v<To>);
    static_assert(sizeof(Rep) <= 8 && sizeof(To) <= 8);
    const __int128 scaled = static_cast<__int128>(duration.count()) * Scale::num / Scale::den;
    if (scaled < static_cast<__int128>(std::numeric_limits<To>::min()) ||
        scaled > static_cast<__int128>(std::numeric_limits<To>::max()))
        throw std::runtime_error("datetime duration is outside target range");
    return static_cast<To>(scaled);
}
// Keep host sleep conversions bounded, even for a very large positive request.
// Scheduling precision/interruption handling remains the host implementation's.
inline std::int64_t sleep_chunk_millis(std::int64_t remaining) noexcept {
    constexpr std::int64_t day = 24 * 60 * 60 * 1000;
    return remaining <= 0 ? 0 : remaining < day ? remaining : day;
}
}
