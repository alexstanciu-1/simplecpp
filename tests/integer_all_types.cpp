#include <scpp/short_names.hpp>
#include <scpp/conversions.hpp>
#include <limits>
#include <initializer_list>
#include <stdexcept>
#include <type_traits>

static void require(bool ok) { if (!ok) throw std::runtime_error("Native alias family proof failed"); }
#define CHECK_ALIAS(name, native) static_assert(std::is_same_v<scpp::name, native>)
CHECK_ALIAS(int8, __INT8_TYPE__); CHECK_ALIAS(uint8, __UINT8_TYPE__);
CHECK_ALIAS(int16, __INT16_TYPE__); CHECK_ALIAS(uint16, __UINT16_TYPE__);
CHECK_ALIAS(int32, __INT32_TYPE__); CHECK_ALIAS(uint32, __UINT32_TYPE__);
CHECK_ALIAS(int64, __INT64_TYPE__); CHECK_ALIAS(uint64, __UINT64_TYPE__);
#undef CHECK_ALIAS
static_assert(std::is_same_v<scpp::byte, scpp::uint8>);
static_assert(std::is_same_v<scpp::bool_t, bool> && std::is_same_v<scpp::float64, double>);
static_assert(std::is_same_v<scpp::null_t, decltype(nullptr)> && !std::is_convertible_v<scpp::null_t, bool>);

// Native aliases use ordinary C++ promotion. Source-family behavior remains compiler-owned.
template<class T, class Parse> static void check(Parse parse) {
    require(T{} == 0);
    const T a{20}, b{3};
    require(a + b == 23 && a - b == 17 && a * b == 60);
    require(a / b == 6 && a % b == 2);
    require((a & b) == 0 && (a | b) == 23 && (a ^ b) == 23);
    T target{20};
    require(&(target += b) == &target && target == 23);
    require(target++ == 23 && target == 24);
    require(--target == 23);
    for (T value : {std::numeric_limits<T>::min(), std::numeric_limits<T>::max()}) {
        const scpp::string text{value};
        require(parse(text) == value);
    }
    T destination{7};
    try {
        destination = parse(scpp::string{"18446744073709551616"});
        throw std::runtime_error("Missing parse overflow");
    } catch (const std::out_of_range&) { require(destination == 7); }
}
int main() {
#define RUN(name) check<scpp::name>([](const scpp::string& text) { return scpp::checked_string_to_##name(text); })
    RUN(int8); RUN(uint8); RUN(int16); RUN(uint16);
    RUN(int32); RUN(uint32); RUN(int64); RUN(uint64);
#undef RUN
    require(scpp::checked_string_to_bool_t(scpp::string{"true"}));
    require(!scpp::checked_string_to_bool_t(scpp::string{"false"}));
    require(scpp::checked_string_to_float64(scpp::string{"1.25"}) == 1.25);
}
