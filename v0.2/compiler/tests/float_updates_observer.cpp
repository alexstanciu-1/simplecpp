// Test-only native inspection of source updates; no extra source-language facilities.
#include <cmath>
#include <limits>
#include <initializer_list>
int main() {
    for (auto operation : {@pre_inc@, @post_inc@, @pre_dec@, @post_dec@}) {
        const bool increment = operation == @pre_inc@ || operation == @post_inc@;
        double value = 8.5;
        operation(value);
        if (value != (increment ? 9.5 : 7.5)) return 90;
        for (double initial : {0.0, -0.0}) {
            value = initial; operation(value);
            if (value != (increment ? 1.0 : -1.0)) return 91;
        }
        value = increment ? -1.0 : 1.0;
        operation(value);
        if (value != 0.0 || std::signbit(value)) return 92;
        for (double initial : {0x1p54, -0x1p54, std::numeric_limits<double>::max(), -std::numeric_limits<double>::max()}) {
            value = initial; operation(value);
            if (value != initial) return 93;
        }
        for (double initial : {std::numeric_limits<double>::infinity(), -std::numeric_limits<double>::infinity()}) {
            value = initial; operation(value);
            if (value != initial) return 94;
        }
        value = std::numeric_limits<double>::quiet_NaN(); operation(value);
        if (!std::isnan(value)) return 95;
        value = std::numeric_limits<double>::denorm_min(); operation(value);
        if (value != (increment ? 1.0 : -1.0)) return 96;
    }
    return 8;
}
