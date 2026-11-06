// Test-only native inputs expose nonfinite values without adding source APIs.
#include <limits>
#include <initializer_list>
using integer_type = __INT64_TYPE__;
int main() {
    for (double value : {std::numeric_limits<double>::quiet_NaN(), std::numeric_limits<double>::infinity(),
        -std::numeric_limits<double>::infinity(), -0.0, 0.0, 1.5, -1.5, 0x1p53, -0x1p53, 0x1p63, -0x1p63}) {
        for (integer_type integer : {integer_type{0}, integer_type{9007199254740993LL}, integer_type{-9007199254740993LL},
            std::numeric_limits<__INT64_TYPE__>::min(), std::numeric_limits<__INT64_TYPE__>::max()}) {
            const double converted = static_cast<double>(integer);
            if (@equal_if@(integer, value) != (converted == value) || @equal_fi@(value, integer) != (value == converted)) return 90;
            if (@not_equal_if@(integer, value) != (converted != value) || @not_equal_fi@(value, integer) != (value != converted)) return 91;
        }
    }
    return 8;
}
