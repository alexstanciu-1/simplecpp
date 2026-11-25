// Native-only observer supplies IEEE edge inputs without adding source APIs.
#include <cmath>
#include <limits>
#include <stdexcept>
#include <string_view>
#include <initializer_list>
bool same(double actual, double expected) {
    if (std::isnan(expected)) return std::isnan(actual);
    return actual == expected && std::signbit(actual) == std::signbit(expected);
}
int main() {
    for (double floating : {std::numeric_limits<double>::quiet_NaN(), std::numeric_limits<double>::infinity(),
        -std::numeric_limits<double>::infinity(), std::numeric_limits<double>::max(),
        std::numeric_limits<double>::denorm_min(), -std::numeric_limits<double>::denorm_min(),
        -0.0, 0.0, -2.0, 2.0, 254.5, 255.0, 255.5}) {
        for (unsigned int n = 0; n < 256; ++n) {
            const auto integer = static_cast<__UINT8_TYPE__>(n);
            const double converted = static_cast<double>(integer);
            if (!same(@add_bf@(integer, floating), converted + floating) || !same(@add_fb@(floating, integer), floating + converted)) return 90;
            if (!same(@subtract_bf@(integer, floating), converted - floating) || !same(@subtract_fb@(floating, integer), floating - converted)) return 91;
            if (!same(@multiply_bf@(integer, floating), converted * floating) || !same(@multiply_fb@(floating, integer), floating * converted)) return 92;
            if (floating == 0.0) {
                try { (void)@divide_bf@(integer, floating); return 93; }
                catch (const std::domain_error& error) { if (std::string_view(error.what()) != "division_by_zero") return 94; }
            } else if (!same(@divide_bf@(integer, floating), converted / floating)) return 95;
            if (integer == 0) {
                try { (void)@divide_fb@(floating, integer); return 96; }
                catch (const std::domain_error& error) { if (std::string_view(error.what()) != "division_by_zero") return 97; }
            } else if (!same(@divide_fb@(floating, integer), floating / converted)) return 98;
            if (@equal_bf@(integer, floating) != (converted == floating) || @equal_fb@(floating, integer) != (floating == converted)) return 99;
            if (@not_equal_bf@(integer, floating) != (converted != floating) || @not_equal_fb@(floating, integer) != (floating != converted)) return 100;
            if (@less_bf@(integer, floating) != (converted < floating) || @less_fb@(floating, integer) != (floating < converted)) return 101;
            if (@less_equal_bf@(integer, floating) != (converted <= floating) || @less_equal_fb@(floating, integer) != (floating <= converted)) return 102;
            if (@greater_bf@(integer, floating) != (converted > floating) || @greater_fb@(floating, integer) != (floating > converted)) return 103;
            if (@greater_equal_bf@(integer, floating) != (converted >= floating) || @greater_equal_fb@(floating, integer) != (floating >= converted)) return 104;
            double result = floating;
            @add_write@(result, integer); if (!same(result, floating + converted)) return 105;
            result = floating; @subtract_write@(result, integer); if (!same(result, floating - converted)) return 106;
            result = floating; @multiply_write@(result, integer); if (!same(result, floating * converted)) return 107;
            result = floating;
            if (integer == 0) {
                try { @divide_write@(result, integer); return 108; }
                catch (const std::domain_error& error) {
                    if (std::string_view(error.what()) != "division_by_zero" || !same(result, floating)) return 109;
                }
            } else { @divide_write@(result, integer); if (!same(result, floating / converted)) return 110; }
        }
    }
    double value = 10.0;
    try { @failed_write@(value); return 111; }
    catch (const std::domain_error& error) {
        if (value != 100.0 || std::string_view(error.what()) != "division_by_zero") return 112;
    }
    return 8;
}
