// Test-only inputs exercise nonfinite values and signed zero without new source APIs.
#include <cmath>
#include <limits>
#include <initializer_list>
#include <stdexcept>
#include <string>
using integer_type = __INT64_TYPE__;
bool same(double actual, double expected) {
    if (std::isnan(expected)) return std::isnan(actual);
    return actual == expected && std::signbit(actual) == std::signbit(expected);
}
int main() {
    for (double value : {std::numeric_limits<double>::quiet_NaN(), std::numeric_limits<double>::infinity(),
        -std::numeric_limits<double>::infinity(), std::numeric_limits<double>::max(),
        std::numeric_limits<double>::denorm_min(), -0.0, 0.0, -2.0, 2.0, 0x1p53, 0x1p63}) {
        for (integer_type integer : {integer_type{0}, integer_type{2}, integer_type{-2},
            integer_type{9007199254740993LL}, integer_type{-9007199254740993LL},
            std::numeric_limits<integer_type>::min(), std::numeric_limits<integer_type>::max()}) {
            const double converted = static_cast<double>(integer);
            if (!same(@add_if@(integer, value), converted + value) || !same(@add_fi@(value, integer), value + converted)) return 90;
            if (!same(@subtract_if@(integer, value), converted - value) || !same(@subtract_fi@(value, integer), value - converted)) return 91;
            if (!same(@multiply_if@(integer, value), converted * value) || !same(@multiply_fi@(value, integer), value * converted)) return 92;
            if (value == 0) {
                try { (void)@divide_if@(integer, value); return 93; }
                catch (const std::domain_error& error) { if (std::string(error.what()) != "division_by_zero") return 94; }
            } else if (!same(@divide_if@(integer, value), converted / value)) return 95;
            if (integer == 0) {
                try { (void)@divide_fi@(value, integer); return 96; }
                catch (const std::domain_error& error) { if (std::string(error.what()) != "division_by_zero") return 97; }
            } else if (!same(@divide_fi@(value, integer), value / converted)) return 98;
        }
    }
    return 8;
}
