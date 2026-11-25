// Independent IEEE boundary inputs cannot all be written as source literals.
#include <limits>
#include <stdexcept>
#include <cmath>
#include <string_view>
#include <initializer_list>
int main() {
    const double nan = std::numeric_limits<double>::quiet_NaN();
    const double inf = std::numeric_limits<double>::infinity();
    for (double a : {0.0, -0.0, 1.0, -1.0, inf, -inf, nan}) {
        for (double b : {0.0, -0.0, 1.0, -1.0, inf, -inf, nan}) {
            if (std::isnan(a) || std::isnan(b)) {
                try { (void)@compare_float@(a,b); return 93; }
                catch (const std::domain_error& error) {
                    if (std::string_view(error.what()) != "unordered_comparison") return 94;
                }
            } else if (@compare_float@(a,b) != ((a > b) - (a < b))) return 95;
            if (@same_float@(a,b) != (a == b) || @different_float@(a,b) != (a != b)) return 90;
        }
    }
    try { (void)@identity_fail_right@(); return 91; } catch (const std::domain_error&) {}
    try { (void)@identity_fail_left@(); return 92; } catch (const std::domain_error&) {}
    return 8;
}
