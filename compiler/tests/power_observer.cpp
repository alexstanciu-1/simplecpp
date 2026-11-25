// Independent wider arithmetic oracle; each intermediate is checked before another multiply.
#include <cstdint>
#include <limits>
#include <stdexcept>
#include <string_view>
#include <initializer_list>
int main() {
    const auto low = std::numeric_limits<std::int64_t>::min();
    const auto high = std::numeric_limits<std::int64_t>::max();
    for (auto base : {low, high, std::int64_t{-3}, std::int64_t{-2}, std::int64_t{-1}, std::int64_t{0}, std::int64_t{1}, std::int64_t{2}, std::int64_t{3}}) {
        for (std::int64_t exponent = -2; exponent <= 65; ++exponent) {
            __int128 expected = 1;
            bool overflow = false;
            if (exponent >= 0) for (std::int64_t i = 0; i < exponent; ++i) {
                expected *= base;
                if (expected < low || expected > high) { overflow = true; break; }
            }
            const auto error = exponent < 0 ? "power_negative_exponent" : overflow ? "power_overflow" : "";
            try {
                const auto actual = @power_value@(base, exponent);
                if (error[0] || actual != expected) return 90;
            } catch (const std::runtime_error& failure) {
                if (!error[0] || std::string_view(failure.what()) != error) return 91;
            }
            auto target = base;
            try {
                @power_write@(target, exponent);
                if (error[0] || target != expected) return 92;
            } catch (const std::runtime_error& failure) {
                if (!error[0] || target != base || std::string_view(failure.what()) != error) return 93;
            }
        }
    }
    for (std::int64_t base : {-1, 0, 1}) if (@power_value@(base, high) != base) return 94;
    std::int64_t target = 2;
    try { @power_effect@(target); return 95; }
    catch (const std::runtime_error& failure) {
        if (target != 9 || std::string_view(failure.what()) != "power_overflow") return 96;
    }
    return 8;
}
