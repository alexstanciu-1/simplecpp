#include "scpp/short_names.hpp"
#include "scpp/integers.hpp"
#include "scpp/numeric.hpp"
#include "scpp/native_bridge.hpp"
#include <limits>
#include <stdexcept>
#include <type_traits>
#include <utility>
#include <iostream>

template<class L, class R> constexpr bool rejects_other_binary =
    !requires(L l, R r) { l - r; } && !requires(L l, R r) { l * r; }
    && !requires(L l, R r) { l / r; } && !requires(L l, R r) { l % r; }
    && !requires(L l, R r) { l & r; } && !requires(L l, R r) { l | r; }
    && !requires(L l, R r) { l ^ r; };
static_assert(rejects_other_binary<scpp::int64, double> && rejects_other_binary<double, scpp::int64>);
static_assert(rejects_other_binary<scpp::int64, long long> && rejects_other_binary<long long, scpp::int64>);
static_assert(rejects_other_binary<scpp::int32, scpp::int64>); // Explicit compiler normalization suffices.
template<class L, class R> concept addition = requires(L l, R r) { l + r; };
static_assert(std::is_same_v<decltype(int32{} + int32{}), int32>);
static_assert(std::is_same_v<decltype(int32{} + int64{}), int64>);
static_assert(std::is_same_v<decltype(int64{} + int32{}), int64>);
static_assert(std::is_same_v<decltype(int64{} + int64{}), int64>);
static_assert(!std::is_convertible_v<int, int32>);
static_assert(!std::is_convertible_v<int32, int64>);
static_assert(!std::is_convertible_v<int64, long long>);
static_assert(!std::is_constructible_v<int32, double>);
static_assert(!std::is_constructible_v<int64, bool>);
static_assert(!std::is_constructible_v<int64, char>);
static_assert(!std::is_constructible_v<int64, short>);
static_assert(!std::is_constructible_v<int64, unsigned short>);
static_assert(!std::is_constructible_v<int64, __int128>);
// All public headers are visible: overload sets must compose without ambiguity.
template<class Public, class Native, class Result> constexpr bool accepts() {
    return std::is_same_v<decltype(std::declval<Public>() + std::declval<Native>()), Result>
        && std::is_same_v<decltype(std::declval<Native>() + std::declval<Public>()), Result>
        && std::is_same_v<decltype(std::declval<const Public&>() + std::declval<const Native&>()), Result>
        && std::is_same_v<decltype(std::declval<const Native&>() + std::declval<const Public&>()), Result>
        && std::is_same_v<decltype(std::declval<Public&>() + std::declval<Native&>()), Result>;
}
static_assert(accepts<int32, int, std::conditional_t<std::numeric_limits<int>::digits == 31, int32, int64>>());
static_assert(accepts<int64, int, int64>());
template<class Native> constexpr bool rejected() {
    return !addition<int32, Native> && !addition<Native, int32>
        && !addition<int64, Native> && !addition<Native, int64>;
}
static_assert(rejected<unsigned int>() && rejected<long>() && rejected<long long>());
static_assert(!std::is_convertible_v<long long, int64> && !std::is_convertible_v<double, int64>);
static_assert(rejected<unsigned long>() && rejected<unsigned long long>());
static_assert(rejected<short>() && rejected<unsigned short>());
static_assert(rejected<bool>() && rejected<char>() && rejected<signed char>() && rejected<unsigned char>());
static_assert(rejected<wchar_t>() && rejected<char8_t>() && rejected<char16_t>() && rejected<char32_t>());
static_assert(rejected<float>() && rejected<double>() && rejected<long double>());
static_assert(rejected<__int128>() && rejected<unsigned __int128>());
static_assert(std::is_trivially_copyable_v<int32> && std::is_trivially_copyable_v<int64>);
static_assert(noexcept(int64{int32{}}));
static_assert(!noexcept(int32{int64{}}));
static_assert(sizeof(int32) == 4 && sizeof(int64) == 8);

static void require(bool ok) { if (!ok) throw std::runtime_error("Integer consumer proof failed"); }
template<class F> static void rejects(F work) {
    try { work(); } catch (const std::out_of_range &error) {
        require(std::string(error.what()) == "integer_cast_out_of_range"); return;
    }
    throw std::runtime_error("Expected checked narrowing failure");
}
int main() {
    require(int32{}.native_value() == 0 && int64{}.native_value() == 0);
    int32 a{20}; int64 b{3};
    require((a + a).native_value() == 40);
    require((a + b).native_value() == 23 && (b + a).native_value() == 23);
    require((b + b).native_value() == 6);
    require((a + 1).native_value() == 21 && (1 + a).native_value() == 21);
    require((b + 1).native_value() == 4 && (1 + b).native_value() == 4);
    require((a - 1).native_value() == 19 && (1 - a).native_value() == -19);
    require((a * 2).native_value() == 40 && (2 * a).native_value() == 40);
    require((a / 3).native_value() == 6 && (60 / a).native_value() == 3);
    require((a % 3).native_value() == 2 && (23 % a).native_value() == 3);
    require((a & 3).native_value() == 0 && (3 & a).native_value() == 0);
    require((a | 3).native_value() == 23 && (3 | a).native_value() == 23);
    require((a ^ 3).native_value() == 23 && (3 ^ a).native_value() == 23);
    require((static_cast<int64>(a) - b).native_value() == 17);
    require((static_cast<int64>(a) * b).native_value() == 60);
    require((a + static_cast<int64>(2147483648LL)).native_value() == 2147483668LL);
    require(static_cast<int32>(b).native_value() == 3);
    require(int32{b}.native_value() == static_cast<int32>(b).native_value());
    int32 high{std::numeric_limits<int>::max()};
    require((high + int64{1}).native_value() == 2147483648LL);
    int64 min{std::numeric_limits<long long>::min()}, max{std::numeric_limits<long long>::max()};
    require((min + int64{0}).native_value() == min.native_value());
    require((max + int64{0}).native_value() == max.native_value());
    require(int32{int64{-2147483648LL}}.native_value() == -2147483648LL);
    require(int32{int64{2147483647LL}}.native_value() == 2147483647LL);
    rejects([] { (void)int32{2147483648LL}; });
    rejects([] { (void)int32{-2147483649LL}; });
    rejects([] { (void)static_cast<int32>(int64{2147483648LL}); });
    rejects([] { (void)int64{std::numeric_limits<unsigned long long>::max()}; });
    int32 updated{10};
    require(&(updated += 2) == &updated && updated.native_value() == 12);
    require(&(updated -= 2) == &updated && updated.native_value() == 10);
    updated *= 2; updated /= 4; updated %= 3;
    require(updated.native_value() == 2);
    updated |= 4; updated &= 3; updated ^= 1;
    require(updated.native_value() == 3);
    int32 write_back{2147483647};
    rejects([&] { write_back += int64{1}; });
    require(write_back.native_value() == 2147483647);
    int32 destination{7};
    rejects([&] { destination = int32{max}; });
    require(destination.native_value() == 7);
    std::cout << "Generated integer consumer proofs passed\n";
}
