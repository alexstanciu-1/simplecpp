// Test-only native inputs expose nonfinite values without adding source APIs.
#include <limits>
#include <initializer_list>
using integer_type = __INT64_TYPE__;
int main() {
    for (double value : {std::numeric_limits<double>::quiet_NaN(), std::numeric_limits<double>::infinity(),
        -std::numeric_limits<double>::infinity(), -0.0, 0.0, 0x1p53, 0x1p63}) {
        for (integer_type integer : {integer_type{0}, integer_type{9007199254740993LL},
            std::numeric_limits<__INT64_TYPE__>::min(), std::numeric_limits<__INT64_TYPE__>::max()}) {
            const double converted = static_cast<double>(integer);
            if (@less_if@(integer, value) != (converted < value) || @less_fi@(value, integer) != (value < converted)) return 90;
            if (@less_equal_if@(integer, value) != (converted <= value) || @less_equal_fi@(value, integer) != (value <= converted)) return 91;
            if (@greater_if@(integer, value) != (converted > value) || @greater_fi@(value, integer) != (value > converted)) return 92;
            if (@greater_equal_if@(integer, value) != (converted >= value) || @greater_equal_fi@(value, integer) != (value >= converted)) return 93;
        }
    }
    return 8;
}
