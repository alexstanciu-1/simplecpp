#include <scpp/numeric.hpp>
#include <scpp/conversions.hpp>
#include <scpp/arithmetic.hpp>
#include <type_traits>
#include <stdexcept>
#include <cstdlib>
void require(bool ok) { if (!ok) std::abort(); }

using scpp::byte;
using scpp::int64;
template<class A, class B> concept adds = requires(A a, B b) { a + b; };
template<class A> concept increments = requires(A a) { ++a; };
static_assert(sizeof(byte) == 1);
static_assert(std::is_same_v<byte, scpp::uint8>);
static_assert(adds<int64, int64> && adds<int64, int> && adds<int, int64>);
static_assert(!std::is_same_v<byte, int64>);
static_assert(std::is_convertible_v<int64, byte> && std::is_convertible_v<byte, int64>);
static_assert(std::is_constructible_v<byte, scpp::float64> && std::is_constructible_v<scpp::float64, byte>);
static_assert(std::is_constructible_v<byte, scpp::bool_t> && std::is_constructible_v<scpp::bool_t, byte>);
static_assert(adds<byte, byte> && adds<byte, int64> && increments<byte>);
static_assert(std::is_same_v<decltype(byte{} & byte{}), int>);
static_assert(std::is_same_v<decltype(byte{} << int64{}), int>);
static_assert(std::is_same_v<decltype(byte{} == byte{}), scpp::bool_t>);
int main() {
    byte a{int64{240}}, b{15};
    require((a | b) == 255);
    require((a & b) == 0);
    require(static_cast<byte>(~b) == 240);
    require((a ^ b) == 255);
    require((byte{1} << int64{7}) == 128);
    require((a >> int64{4}) == 15);
    require(int64{byte{255}} == 255);
    a |= b;
    require(a == 255);
    require(scpp::checked_float64_to_uint8(255.9) == 255);
    require(scpp::checked_float64_to_uint8(-0.9) == 0);
    require(byte{scpp::bool_t{true}} == 1);
    require(scpp::float64{byte{255}} == 255.0);
    require(scpp::checked_uint8_to_bool_t(byte{255}));
    int rejected = 0;
    try { a = scpp::checked_int64_to_uint8(int64{256}); } catch (const std::out_of_range&) { ++rejected; }
    try { a = scpp::checked_int64_to_uint8(int64{-1}); } catch (const std::out_of_range&) { ++rejected; }
    try { (void)scpp::uint8_shift_left(a, int64{8}); } catch (const std::domain_error&) { ++rejected; }
    try { (void)scpp::uint8_shift_right(a, int64{-1}); } catch (const std::domain_error&) { ++rejected; }
    require(rejected == 4 && a == 255);
}
