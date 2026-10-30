#include <scpp/numeric.hpp>
#include <type_traits>
#include <stdexcept>
#include <cstdlib>
void require(bool ok) { if (!ok) std::abort(); }

using scpp::byte;
using scpp::int64;
template<class A, class B> concept adds = requires(A a, B b) { a + b; };
template<class A> concept increments = requires(A a) { ++a; };
static_assert(sizeof(byte) == 1);
static_assert(!std::is_same_v<byte, int64>);
static_assert(!std::is_convertible_v<int64, byte> && !std::is_convertible_v<byte, int64>);
static_assert(!std::is_constructible_v<byte, scpp::float64> && !std::is_constructible_v<scpp::float64, byte>);
static_assert(!std::is_constructible_v<byte, scpp::bool_t> && !std::is_constructible_v<scpp::bool_t, byte>);
static_assert(!adds<byte, byte> && !adds<byte, int64> && !increments<byte>);
static_assert(std::is_same_v<decltype(byte{} & byte{}), byte>);
static_assert(std::is_same_v<decltype(byte{} << int64{}), byte>);
static_assert(std::is_same_v<decltype(byte{} == byte{}), scpp::bool_t>);
int main() {
    byte a{int64{240}}, b{15};
    require((a | b).native_value() == 255);
    require((a & b).native_value() == 0);
    require((~b).native_value() == 240);
    require((a ^ b).native_value() == 255);
    require((byte{1} << int64{7}).native_value() == 128);
    require((a >> int64{4}).native_value() == 15);
    require(int64{byte{255}}.native_value() == 255);
    a |= b;
    require(a.native_value() == 255);
    int rejected = 0;
    try { a = byte{int64{256}}; } catch (const std::out_of_range&) { ++rejected; }
    try { a = byte{int64{-1}}; } catch (const std::out_of_range&) { ++rejected; }
    try { (void)(a << int64{8}); } catch (const std::domain_error&) { ++rejected; }
    try { (void)(a >> int64{-1}); } catch (const std::domain_error&) { ++rejected; }
    require(rejected == 4 && a.native_value() == 255);
}
