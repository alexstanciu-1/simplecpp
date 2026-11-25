// Native-only observation of source functions; no additional source-language APIs.
#include <cmath>
#include <limits>
#include <stdexcept>
#include <string_view>
#include <initializer_list>
int main() {
    volatile double one = 1.0;
    const double infinity = std::numeric_limits<double>::infinity();
    const double nan = std::numeric_limits<double>::quiet_NaN();
    const double tiny = std::numeric_limits<double>::denorm_min();
    const double normal = std::numeric_limits<double>::min();
    if (@add@(one, 2.0) != 3.0 || @subtract@(one, 2.0) != -1.0
        || @multiply@(one, 2.0) != 2.0 || @divide@(one, 2.0) != 0.5) return 90;
    if (!std::isinf(@multiply@(std::numeric_limits<double>::max(), 2.0))) return 91;
    if (!std::isnan(@subtract@(infinity, infinity)) || !std::isnan(@multiply@(infinity, 0.0))
        || !std::isnan(@divide@(infinity, infinity)) || !std::isnan(@add@(nan, one))) return 92;
    if (@divide@(normal, 2.0) != normal / 2.0 || @multiply@(tiny, 0.5) != 0.0) return 93;
    if (!std::signbit(@divide@(-tiny, 2.0)) || @divide@(-tiny, 2.0) != 0.0) return 94;
    if (!std::signbit(@positive@(-0.0, 0.0)) || std::signbit(@negative@(-0.0, 0.0))
        || !std::signbit(@negative@(0.0, 0.0)) || !std::signbit(@divide@(0.0, -one))) return 95;
    for (double numerator : {0.0, -0.0, 1.0, -1.0, infinity, nan}) {
        for (double denominator : {0.0, -0.0}) {
            try { (void)@divide@(numerator, denominator); return 96; }
            catch (const std::domain_error& error) {
                if (std::string_view(error.what()) != "division_by_zero") return 97;
            }
        }
    }
    return 8;
}
