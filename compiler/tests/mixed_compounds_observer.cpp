// Test-only native observers of source reference writes, exceptions and IEEE results.
#include <cmath>
#include <limits>
#include <stdexcept>
#include <string_view>
#include <initializer_list>
int main() {
    double value = 8.0;
    @add@(value, 2); @subtract@(value, 4); @multiply@(value, 3); @divide@(value, 2);
    if (value != 9.0) return 90;
    for (double numerator : {0.0, -0.0, 10.0, std::numeric_limits<double>::infinity(), std::numeric_limits<double>::quiet_NaN()}) {
        for (auto zero : {0LL}) {
            value = numerator;
            try { @divide@(value, zero); return 91; }
            catch (const std::domain_error& error) {
                if (std::string_view(error.what()) != "division_by_zero") return 92;
                if (std::isnan(numerator) ? !std::isnan(value) : (value != numerator || std::signbit(value) != std::signbit(numerator))) return 93;
            }
        }
    }
    for (auto operation : {@mutate_then_fail@, @rhs_failure@}) {
        value = 10.0;
        try { operation(value); return 94; }
        catch (const std::domain_error& error) {
            if (value != 100.0 || std::string_view(error.what()) != "division_by_zero") return 95;
        }
    }
    value = std::numeric_limits<double>::max(); @multiply@(value, 2);
    if (!std::isinf(value)) return 96;
    value = std::numeric_limits<double>::quiet_NaN(); @add@(value, 1);
    if (!std::isnan(value)) return 97;
    value = -std::numeric_limits<double>::denorm_min(); @divide@(value, 2);
    if (value != 0.0 || !std::signbit(value)) return 98;
    for (auto integer : {9007199254740993LL, -9007199254740993LL, 9223372036854775807LL, (-9223372036854775807LL - 1)}) {
        value = 0.0; @add@(value, integer);
        if (value != static_cast<double>(integer)) return 99;
    }
    return 8;
}
