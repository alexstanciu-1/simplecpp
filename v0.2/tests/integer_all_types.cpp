#include <scpp/short_names.hpp>
#include <limits>
#include <cmath>
#include <stdexcept>
#include <type_traits>
#include <string>

static void require(bool ok) { if (!ok) throw std::runtime_error("Integer family proof failed"); }
template<class F> static void zero_fails(F work, const char *message) {
    try { work(); } catch (const std::domain_error &error) {
        require(std::string(error.what()) == message); return;
    }
    throw std::runtime_error("Missing zero-divisor failure");
}
template<class T> static void check() {
    const T a{20}, b{3};
    static_assert(std::is_same_v<decltype(a + b), T>);
    static_assert(std::is_same_v<decltype(a - b), T>);
    static_assert(std::is_same_v<decltype(a * b), T>);
    static_assert(std::is_same_v<decltype(a / b), T>);
    static_assert(std::is_same_v<decltype(a % b), T>);
    static_assert(std::is_same_v<decltype(a & b), T>);
    static_assert(std::is_same_v<decltype(a | b), T>);
    static_assert(std::is_same_v<decltype(a ^ b), T>);
    require((a + b).native_value() == 23);
    require((a - b).native_value() == 17);
    require((a * b).native_value() == 60);
    require((a / b).native_value() == 6);
    require((a % b).native_value() == 2);
    require((a & b).native_value() == 0);
    require((a | b).native_value() == 23);
    require((a ^ b).native_value() == 23);
    zero_fails([&] { (void)(a / T{}); }, "division_by_zero");
    zero_fails([&] { (void)(a % T{}); }, "modulo_by_zero");
    static_assert(std::is_same_v<decltype(+a), T> && std::is_same_v<decltype(-a), T>);
    static_assert(std::is_same_v<decltype(~a), T>);
    static_assert(std::is_same_v<decltype(a << b), T> && std::is_same_v<decltype(a >> b), T>);
    require((+a).native_value() == 20);
    require((~~a).native_value() == 20);
    require((T{3} << T{2}).native_value() == 12);
    require((T{20} >> T{2}).native_value() == 5);
    T target{20};
    static_assert(std::is_same_v<decltype(target += b), T&>);
    static_assert(std::is_same_v<decltype(++target), T&>);
    static_assert(std::is_same_v<decltype(target++), T>);
    require(&(target += b) == &target && target.native_value() == 23);
    require(&(target -= b) == &target && target.native_value() == 20);
    require(&(target *= T{2}) == &target && target.native_value() == 40);
    require(&(target /= T{4}) == &target && target.native_value() == 10);
    require(&(target %= T{7}) == &target && target.native_value() == 3);
    require(&(target <<= T{2}) == &target && target.native_value() == 12);
    require(&(target >>= T{1}) == &target && target.native_value() == 6);
    require(&(target &= T{3}) == &target && target.native_value() == 2);
    require(&(target |= T{4}) == &target && target.native_value() == 6);
    require(&(target ^= T{2}) == &target && target.native_value() == 4);
    require(&(++target) == &target && target.native_value() == 5);
    require((target++).native_value() == 5 && target.native_value() == 6);
    require(&(--target) == &target && target.native_value() == 5);
    require((target--).native_value() == 5 && target.native_value() == 4);
    zero_fails([&] { target /= T{}; }, "division_by_zero");
    zero_fails([&] { target %= T{}; }, "modulo_by_zero");
    require(target.native_value() == 4);
    using Rep = decltype(a.native_value());
    constexpr int width = sizeof(Rep) * 8;
    zero_fails([&] { (void)(T{1} << T{width}); }, "shift_count_out_of_range");
    zero_fails([&] { target >>= T{width}; }, "shift_count_out_of_range");
    require(target.native_value() == 4);
    if constexpr (std::is_signed_v<Rep>) {
        require((-T{3}).native_value() == -3);
        require((T{-4} >> T{1}).native_value() == -2);
        zero_fails([&] { (void)(T{1} << T{-1}); }, "shift_count_out_of_range");
        require((T{-7} / b).native_value() == -2);
        require((T{-7} % b).native_value() == -1);
        require((T{7} / T{-3}).native_value() == -2);
        require((T{7} % T{-3}).native_value() == 1);
    } else {
        const T high{static_cast<unsigned long long>(std::numeric_limits<Rep>::max())};
        require((-T{1}).native_value() == high.native_value());
        require((high + T{1}).native_value() == 0);
        require((T{} - T{1}).native_value() == high.native_value());
        // In particular uint16 max squared must not overflow a promoted signed int.
        require((high * high).native_value() == 1);
    }
}
template<class L, class R> static void compare() {
    static_assert(std::is_same_v<decltype(L{} < R{}), bool_t>);
    static_assert(std::is_same_v<decltype(L{} == R{}), bool_t>);
    static_assert(std::is_same_v<decltype(L{} != R{}), bool_t>);
    require(static_cast<bool>(L{1} == R{1}));
    require(static_cast<bool>(L{1} != R{2}));
    require(static_cast<bool>(L{1} < R{2}));
    require(static_cast<bool>(L{1} <= R{1}));
    require(static_cast<bool>(L{2} > R{1}));
    require(static_cast<bool>(L{2} >= R{2}));
    using LR = decltype(L{}.native_value());
    using RR = decltype(R{}.native_value());
    if constexpr (std::is_signed_v<LR> && !std::is_signed_v<RR>) {
        const R high{static_cast<unsigned long long>(std::numeric_limits<RR>::max())};
        require(static_cast<bool>(L{-1} < high));
        require(static_cast<bool>(high > L{-1}));
        require(!static_cast<bool>(L{-1} == high));
        require(static_cast<bool>(L{0} == R{0}));
    }
}
template<class L> static void compare_all() {
    compare<L, int8>(); compare<L, int16>(); compare<L, int32>(); compare<L, int64>();
    compare<L, uint8>(); compare<L, uint16>(); compare<L, uint32>(); compare<L, uint64>();
}
template<class T> static void scalar_casts() {
    using Rep = decltype(T{}.native_value());
    require(static_cast<float64>(T{3}).native_value() == 3.0);
    require(static_cast<T>(float64{3.9}).native_value() == 3);
    require(static_cast<T>(bool_t{true}).native_value() == 1);
    require(!static_cast<bool_t>(T{}).native_value());
    require(static_cast<bool_t>(T{3}).native_value());
    const double upper = std::ldexp(1.0, std::numeric_limits<Rep>::digits);
    const auto range_fails = [](double value) {
        try { (void)static_cast<T>(float64{value}); }
        catch (const std::out_of_range &) { return; }
        throw std::runtime_error("Missing scalar range failure");
    };
    range_fails(upper);
    range_fails(std::numeric_limits<double>::infinity());
    range_fails(std::numeric_limits<double>::quiet_NaN());
    const double below_upper = std::nextafter(upper, 0.0);
    require(static_cast<T>(float64{below_upper}).native_value() == static_cast<Rep>(below_upper));
    if constexpr (std::is_signed_v<Rep>) {
        require(static_cast<T>(float64{-upper}).native_value() == std::numeric_limits<Rep>::min());
        range_fails(std::floor(std::nextafter(-upper, -INFINITY)));
    } else {
        require(static_cast<T>(float64{-0.5}).native_value() == 0);
        range_fails(-1.0);
    }
    if constexpr (sizeof(Rep) <= 4) {
        require((T{3} + float64{0.5}).native_value() == 3.5);
        require((float64{0.5} + T{3}).native_value() == 3.5);
        require((T{3} - float64{0.5}).native_value() == 2.5);
        require((T{3} * float64{0.5}).native_value() == 1.5);
        require((T{3} / float64{0.5}).native_value() == 6.0);
        require(static_cast<bool>(T{3} == float64{3.0}));
        require(static_cast<bool>(T{3} != float64{3.5}));
        require(static_cast<bool>(T{3} < float64{3.5}));
        require(static_cast<bool>(T{3} <= float64{3.0}));
        require(static_cast<bool>(float64{3.5} > T{3}));
        require(static_cast<bool>(float64{3.0} >= T{3}));
        T target{3};
        require(&(target += float64{1.75}) == &target && target.native_value() == 4);
        target -= float64{0.75}; require(target.native_value() == 3);
        target *= float64{1.5}; require(target.native_value() == 4);
        target /= float64{1.5}; require(target.native_value() == 2);
        try { target += float64{upper}; throw std::runtime_error("Missing mixed write-back failure"); }
        catch (const std::out_of_range &) { require(target.native_value() == 2); }
        float64 floating{3.5};
        floating += T{1}; floating -= T{1}; floating *= T{2}; floating /= T{2};
        require(floating.native_value() == 3.5);
    }
}
int main() {
    scalar_casts<int8>(); scalar_casts<int16>(); scalar_casts<int32>(); scalar_casts<int64>();
    scalar_casts<uint8>(); scalar_casts<uint16>(); scalar_casts<uint32>(); scalar_casts<uint64>();
    check<int8>(); check<int16>(); check<int32>(); check<int64>();
    check<uint8>(); check<uint16>(); check<uint32>(); check<uint64>();
    require((int8{1} << uint64{7}).native_value() == -128);
    zero_fails([] { (void)(int8{1} << uint64{std::numeric_limits<unsigned long long>::max()}); }, "shift_count_out_of_range");
    int8 narrow{120};
    try { narrow += int64{8}; throw std::runtime_error("Missing checked write-back"); }
    catch (const std::out_of_range &) { require(narrow.native_value() == 120); }
    compare_all<int8>(); compare_all<int16>(); compare_all<int32>(); compare_all<int64>();
    compare_all<uint8>(); compare_all<uint16>(); compare_all<uint32>(); compare_all<uint64>();
}
