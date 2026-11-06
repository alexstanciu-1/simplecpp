#include <cmath>
#include <limits>
#include <stdexcept>
#include <string>
#include <type_traits>
#include <scpp/short_names.hpp>
#include <scpp/conversions.hpp>
#include <scpp/arithmetic.hpp>

namespace app {
using scpp::float_t; // Local import coexists with the C math ::float_t typedef.
static_assert(std::is_same_v<float_t, float64>);
}
template<class L, class R> concept adds = requires(L l, R r) { l + r; };
template<class L, class R> concept equals = requires(L l, R r) { l == r; };
static_assert(std::is_same_v<scpp::float_t, float64>);
// C-library float_t may now share the native representation; the qualified alias is unambiguous.
static_assert(std::is_same_v<decltype(float64{} + int32{}), float64>);
static_assert(std::is_same_v<decltype(int32{} + float64{}), float64>);
static_assert(std::is_same_v<decltype(float64{} == float64{}), bool_t>);
static_assert(std::is_same_v<scpp::float64, double>);
static_assert(std::is_convertible_v<int64, float64>);
static_assert(!std::is_constructible_v<float64, null_t>);
static_assert(noexcept(scpp::checked_int64_to_float64(int64{})));
static_assert(!noexcept(scpp::checked_float64_to_int64(float64{})));
static void require(bool ok) { if (!ok) throw std::runtime_error("Floating consumer proof failed"); }
template<class F> static void range_fails(F f) {
    try { f(); } catch (const std::out_of_range &e) {
        require(std::string(e.what()) == "integer_cast_out_of_range"); return;
    }
    throw std::runtime_error("Expected floating range failure");
}
template<class F> static void zero_fails(F f) {
    try { f(); } catch (const std::domain_error &e) {
        require(std::string(e.what()) == "division_by_zero"); return;
    }
    throw std::runtime_error("Expected floating division failure");
}
int main() {
    require(float64{} == 0.0);
    float64 a{7.5}, b{2.0};
    require((a + b) == 9.5 && (a - b) == 5.5);
    require((a * b) == 15.0 && (a / b) == 3.75);
    require((a + 1) == 8.5 && (1 + a) == 8.5);
    require((a - 1) == 6.5 && (10 - a) == 2.5);
    require((a * 2) == 15.0 && (2 * a) == 15.0);
    require((a / 2) == 3.75 && (15 / a) == 2.0);
    require((a + int32{2}) == 9.5);
    require((static_cast<float64>(int64{20}) + b) == 22.0);
    require((+a) == 7.5 && (-a) == -7.5);
    require(std::signbit((-float64{0.0})));
    require(&(a += 1) == &a && a == 8.5);
    a -= 1; a *= 2; a /= 3;
    require(a == 5.0);
    require(&(++a) == &a && a == 6.0);
    require((a++) == 6.0 && a == 7.0);
    require(&(--a) == &a && a == 6.0);
    require((a--) == 6.0 && a == 5.0);
    require(static_cast<bool>(a > b) && static_cast<bool>(a >= b));
    require(static_cast<bool>(b < a) && static_cast<bool>(b <= a));
    require(static_cast<bool>(a == a) && static_cast<bool>(a != b));
    zero_fails([&] { (void)scpp::float64_divide(a, 0.0); });
    zero_fails([&] { a = scpp::float64_divide(a, -0.0); });
    require(a == 5.0);
    require(scpp::checked_float64_to_int64(float64{3.9}) == 3);
    require(scpp::checked_float64_to_int64(float64{-3.9}) == -3);
    require(scpp::checked_float64_to_int32(float64{2147483647.9}) == 2147483647);
    range_fails([] { (void)scpp::checked_float64_to_int32(float64{2147483648.0}); });
    const double upper = std::ldexp(1.0, 63);
    require(scpp::checked_float64_to_int64(float64{-upper}) == std::numeric_limits<long long>::min());
    range_fails([&] { (void)scpp::checked_float64_to_int64(float64{upper}); });
    range_fails([&] { (void)scpp::checked_float64_to_int64(float64{std::nextafter(-upper, -INFINITY)}); });
    require(static_cast<float64>(int64{9007199254740993LL}) == 9007199254740992.0);
    float64 nan{std::numeric_limits<double>::quiet_NaN()}, infinity{std::numeric_limits<double>::infinity()};
    require(!static_cast<bool>(nan == nan) && static_cast<bool>(nan != nan));
    require(!static_cast<bool>(nan < a) && !static_cast<bool>(nan >= a));
    require(std::isinf((float64{std::numeric_limits<double>::max()} * b)));
    range_fails([&] { (void)scpp::checked_float64_to_int64(nan); });
    range_fails([&] { (void)scpp::checked_float64_to_int64(infinity); });
    require(static_cast<bool_t>(nan));
    require(static_cast<bool_t>(infinity));
    require(!static_cast<bool_t>(float64{-0.0}));
    require(static_cast<bool_t>(int64{-3}));
    require(!static_cast<bool_t>(int64{}));
    require(scpp::checked_bool_t_to_int64(bool_t{true}) == 1);
    require(static_cast<float64>(bool_t{false}) == 0.0);
}
