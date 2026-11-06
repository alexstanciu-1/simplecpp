#include "scpp/short_names.hpp"
#include "scpp/numeric.hpp"
#include "scpp/conversions.hpp"
#include <limits>
#include <stdexcept>
#include <type_traits>
#include <utility>
#include <iostream>

static_assert(std::is_same_v<int32, __INT32_TYPE__>);
static_assert(std::is_same_v<int64, __INT64_TYPE__>);
static_assert(std::is_same_v<decltype(int32{} + int64{}), int64>);
static_assert(std::is_convertible_v<int32, int64>);
static_assert(sizeof(int32) == 4 && sizeof(int64) == 8);
static_assert(std::is_trivially_copyable_v<int32> && std::is_trivially_copyable_v<int64>);

static void require(bool ok) { if (!ok) throw std::runtime_error("Integer consumer proof failed"); }
template<class F> static void rejects(F work) {
    try { work(); } catch (const std::out_of_range &error) {
        require(std::string(error.what()) == "integer_cast_out_of_range"); return;
    }
    throw std::runtime_error("Expected checked narrowing failure");
}
int main() {
    require(int32{} == 0 && int64{} == 0);
    int32 a{20}; int64 b{3};
    require((a + a) == 40);
    require((a + b) == 23 && (b + a) == 23);
    require((b + b) == 6);
    require((a + 1) == 21 && (1 + a) == 21);
    require((b + 1) == 4 && (1 + b) == 4);
    require((a - 1) == 19 && (1 - a) == -19);
    require((a * 2) == 40 && (2 * a) == 40);
    require((a / 3) == 6 && (60 / a) == 3);
    require((a % 3) == 2 && (23 % a) == 3);
    require((a & 3) == 0 && (3 & a) == 0);
    require((a | 3) == 23 && (3 | a) == 23);
    require((a ^ 3) == 23 && (3 ^ a) == 23);
    require((static_cast<int64>(a) - b) == 17);
    require((static_cast<int64>(a) * b) == 60);
    require((a + static_cast<int64>(2147483648LL)) == 2147483668LL);
    require(static_cast<int32>(b) == 3);
    require(scpp::checked_int64_to_int32(b) == static_cast<int32>(b));
    int32 high{std::numeric_limits<int>::max()};
    require((high + int64{1}) == 2147483648LL);
    int64 min{std::numeric_limits<long long>::min()}, max{std::numeric_limits<long long>::max()};
    require((min + int64{0}) == min);
    require((max + int64{0}) == max);
    require(scpp::checked_int64_to_int32(int64{-2147483648LL}) == -2147483648LL);
    require(scpp::checked_int64_to_int32(int64{2147483647LL}) == 2147483647LL);
    rejects([] { (void)scpp::checked_int64_to_int32(int64{2147483648LL}); });
    rejects([] { (void)scpp::checked_int64_to_int32(int64{-2147483649LL}); });
    rejects([] { (void)scpp::checked_int64_to_int32(int64{2147483648LL}); });
    int32 updated{10};
    require(&(updated += 2) == &updated && updated == 12);
    require(&(updated -= 2) == &updated && updated == 10);
    updated *= 2; updated /= 4; updated %= 3;
    require(updated == 2);
    updated |= 4; updated &= 3; updated ^= 1;
    require(updated == 3);
    int32 write_back{2147483647};
    rejects([&] { write_back = scpp::checked_int64_to_int32(int64{write_back} + int64{1}); });
    require(write_back == 2147483647);
    int32 destination{7};
    rejects([&] { destination = scpp::checked_int64_to_int32(max); });
    require(destination == 7);
    std::cout << "Generated integer consumer proofs passed\n";
}
