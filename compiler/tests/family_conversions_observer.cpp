// Test-only native inputs expose precise boundaries and nonfinite values without new source APIs.
#include <limits>
#include <cmath>
#include <stdexcept>
#include <initializer_list>
int main() {
    for (double value : {-0.99, -0.0, 0.0, 0.99, 1.99, 254.99, 255.0,
            std::nextafter(256.0, 0.0), std::numeric_limits<double>::denorm_min()}) {
        if (@float_to_byte@(value) != static_cast<__UINT8_TYPE__>(std::trunc(value))) return 90;
    }
    for (double value : {-1.0, std::nextafter(-1.0, -2.0), 256.0, 1e100,
            std::numeric_limits<double>::infinity(), -std::numeric_limits<double>::infinity(),
            std::numeric_limits<double>::quiet_NaN()}) {
        bool failed = false;
        try { (void)@float_to_byte@(value); } catch (const std::out_of_range&) { failed = true; }
        if (!failed) return 91;
    }
    for (unsigned value = 0; value < 256; ++value) {
        auto byte = static_cast<__UINT8_TYPE__>(value);
        if (@byte_to_float@(byte) != static_cast<double>(value)) return 92;
        if (@byte_to_bool@(byte) != (value != 0)) return 93;
    }
    if (@bool_to_byte@(false) != 0 || @bool_to_byte@(true) != 1) return 94;
    return 8;
}
