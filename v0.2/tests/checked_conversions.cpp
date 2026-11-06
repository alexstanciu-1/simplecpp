#include <scpp/conversions.hpp>
#include <scpp/short_names.hpp>
#include <cmath>
#include <cstdint>
#include <limits>
#include <stdexcept>
#include <string>
#include <type_traits>
#include <utility>

using I = __INT64_TYPE__;
using U = __UINT64_TYPE__;
using B = __UINT8_TYPE__;
static_assert(std::is_same_v<decltype(scpp::checked_int64_to_int16(I{})), __INT16_TYPE__>);
static_assert(std::is_same_v<decltype(scpp::checked_int64_to_uint16(I{})), __UINT16_TYPE__>);
template<class T> concept accepts_integer = requires(T value) { scpp::checked_int64_to_uint8(value); };
template<class T> concept accepts_float = requires(T value) { scpp::checked_float64_to_int64(value); };
template<class T> concept accepts_bool = requires(T value) { scpp::checked_bool_t_to_int64(value); };
static_assert(accepts_integer<I> && !accepts_integer<double> && !accepts_integer<bool>);
static_assert(!accepts_integer<U> && accepts_integer<scpp::int64> && !accepts_integer<decltype(nullptr)>);
static_assert(accepts_integer<long> == std::is_same_v<I, long>);
static_assert(accepts_integer<long long> == std::is_same_v<I, long long>);
static_assert(accepts_float<double> && !accepts_float<float> && !accepts_float<I>);
static_assert(accepts_bool<bool> && !accepts_bool<int> && !accepts_bool<int*> && !accepts_bool<decltype(nullptr)>);
static_assert(std::is_same_v<decltype(scpp::checked_int64_to_uint8(I{})), B>);
static_assert(!noexcept(scpp::checked_int64_to_uint8(I{})));
static_assert(noexcept(scpp::checked_uint8_to_int64(B{})));
static_assert(noexcept(scpp::checked_float64_to_bool_t(0.0)));

static void require(bool value) { if (!value) throw std::runtime_error("Named conversion proof failed"); }
template<class Function> void out_of_range(Function function) {
    try { function(); } catch (const std::out_of_range& error) {
        require(std::string(error.what()) == "integer_cast_out_of_range"); return;
    }
    throw std::runtime_error("Expected checked conversion failure");
}

// Check admitted values independently of the runtime implementation; never cast an out-of-range input.
template<class To, class From, class Function, class Value>
void equivalent(Function function, Value value) {
    using Result = decltype(function(value));
    Result expected{};
    bool fits = true;
    if constexpr (std::is_integral_v<To> && !std::is_same_v<To, bool>) {
        if constexpr (std::is_floating_point_v<From>) {
            const long double truncated = std::trunc(static_cast<long double>(value));
            const long double bound = std::ldexp(1.0L, std::numeric_limits<To>::digits);
            fits = std::isfinite(value) && truncated >= (std::is_signed_v<To> ? -bound : 0) && truncated < bound;
        } else if constexpr (!std::is_same_v<From, bool>) fits = std::in_range<To>(value);
    }
    if (!fits) { out_of_range([&] { function(value); }); return; }
    expected = static_cast<To>(value);
    const auto actual = function(value);
    if constexpr (std::is_floating_point_v<Result>) {
        require((std::isnan(expected) && std::isnan(actual)) || expected == actual);
        if (expected == 0) require(std::signbit(expected) == std::signbit(actual));
    } else require(expected == actual);
}

int main() {
    for (I value : {std::numeric_limits<I>::min(), I{-1}, I{0}, I{1}, I{255}, I{256}, std::numeric_limits<I>::max()}) {
        equivalent<scpp::int8, scpp::int64>([](I x) { return scpp::checked_int64_to_int8(x); }, value);
        equivalent<scpp::int16, scpp::int64>([](I x) { return scpp::checked_int64_to_int16(x); }, value);
        equivalent<scpp::uint16, scpp::int64>([](I x) { return scpp::checked_int64_to_uint16(x); }, value);
        equivalent<scpp::int32, scpp::int64>([](I x) { return scpp::checked_int64_to_int32(x); }, value);
        equivalent<scpp::uint32, scpp::int64>([](I x) { return scpp::checked_int64_to_uint32(x); }, value);
        equivalent<scpp::uint8, scpp::int64>([](I x) { return scpp::checked_int64_to_uint8(x); }, value);
        equivalent<scpp::uint64, scpp::int64>([](I x) { return scpp::checked_int64_to_uint64(x); }, value);
        equivalent<scpp::float64, scpp::int64>([](I x) { return scpp::checked_int64_to_float64(x); }, value);
        equivalent<scpp::bool_t, scpp::int64>([](I x) { return scpp::checked_int64_to_bool_t(x); }, value);
    }
    for (U value : {U{0}, U{1}, U{9223372036854775807ULL}, U{9223372036854775808ULL}, std::numeric_limits<U>::max()})
        equivalent<scpp::int64, scpp::uint64>([](U x) { return scpp::checked_uint64_to_int64(x); }, value);
    for (double value : {-std::numeric_limits<double>::infinity(), -0x1p63, std::nextafter(-0x1p63, -INFINITY),
            -1.9, -0.5, -0.0, 0.0, 0.5, 1.9, 255.9, 256.0,
            std::nextafter(0x1p63, 0.0), 0x1p63, std::nextafter(0x1p64, 0.0), 0x1p64,
            std::numeric_limits<double>::infinity(), std::numeric_limits<double>::quiet_NaN()}) {
        equivalent<scpp::int64, scpp::float64>([](double x) { return scpp::checked_float64_to_int64(x); }, value);
        equivalent<scpp::uint64, scpp::float64>([](double x) { return scpp::checked_float64_to_uint64(x); }, value);
        equivalent<scpp::uint8, scpp::float64>([](double x) { return scpp::checked_float64_to_uint8(x); }, value);
        equivalent<scpp::bool_t, scpp::float64>([](double x) { return scpp::checked_float64_to_bool_t(x); }, value);
    }
    require(scpp::checked_float64_to_int64(-1.9) == -1);
    require(scpp::checked_float64_to_uint8(255.9) == 255);
    require(scpp::checked_float64_to_uint8(-0.5) == 0);
    out_of_range([] { scpp::checked_float64_to_uint8(-1.0); });
    out_of_range([] { scpp::checked_float64_to_int64(0x1p63); });
    out_of_range([] { scpp::checked_float64_to_uint64(0x1p64); });
    for (bool value : {false, true}) {
        require(scpp::checked_bool_t_to_int64(value) == (value ? 1 : 0));
        require(scpp::checked_bool_t_to_float64(value) == (value ? 1.0 : 0.0));
        require(scpp::checked_bool_t_to_uint8(value) == (value ? 1 : 0));
    }
    require(scpp::checked_uint8_to_int64(B{255}) == 255);
    require(scpp::checked_int64_to_float64(I{9007199254740993LL}) == 9007199254740992.0);
    require(!scpp::checked_float64_to_bool_t(-0.0));
    require(scpp::checked_float64_to_bool_t(std::numeric_limits<double>::quiet_NaN()));
    require(scpp::checked_int64_to_int16(I{-32768}) == -32768);
    require(scpp::checked_int64_to_int16(I{32767}) == 32767);
    out_of_range([] { scpp::checked_int64_to_int16(I{32768}); });
    require(scpp::checked_int64_to_uint16(I{65535}) == 65535);
    out_of_range([] { scpp::checked_int64_to_uint16(I{65536}); });
    B destination = 7;
    out_of_range([&] { destination = scpp::checked_int64_to_uint8(I{256}); });
    require(destination == 7);
}
