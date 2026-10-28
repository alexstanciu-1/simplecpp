#include <scpp/values.hpp>
#include <scpp/short_names.hpp>
#include <scpp/native_bridge.hpp>
#include <type_traits>
#include <stdexcept>

static_assert(std::is_same_v<int_t, int64>);
static_assert(std::is_same_v<decltype(int_t{} + int64{}), int64>);
template<class L, class R> concept adds = requires(L l, R r) { l + r; };
template<class L, class R> concept equals = requires(L l, R r) { l == r; };
static_assert(!std::is_convertible_v<bool, bool_t> && !std::is_convertible_v<bool_t, bool>);
static_assert(!std::is_constructible_v<bool_t, int> && !std::is_constructible_v<bool_t, double>);
static_assert(!std::is_constructible_v<bool_t, const char*> && !std::is_constructible_v<bool_t, int*>);
static_assert(std::is_constructible_v<int64, bool_t> && std::is_constructible_v<bool_t, int64>);
static_assert(!std::is_convertible_v<int64, bool_t> && !std::is_convertible_v<bool_t, int64>);
static_assert(!std::is_constructible_v<null_t, bool> && !std::is_constructible_v<null_t, int>);
static_assert(!std::is_convertible_v<null_t, bool> && !std::is_constructible_v<int64, null_t>);
static_assert(!adds<bool_t, bool_t> && !adds<null_t, int64>);
static_assert(!equals<null_t, bool_t> && !equals<null_t, int64>);
static_assert(std::is_same_v<decltype(!bool_t{}), bool_t>);
static_assert(std::is_same_v<decltype(bool_t{} == bool_t{}), bool_t>);
static_assert(std::is_same_v<decltype(bool_t{} != bool_t{}), bool_t>);
static_assert(std::is_same_v<decltype(null == null_t{}), bool_t>);
static_assert(std::is_same_v<decltype(null != null_t{}), bool_t>);
static_assert(std::is_same_v<decltype(int64{} < int64{}), bool_t>);
static_assert(std::is_trivially_copyable_v<bool_t> && std::is_empty_v<null_t>);
static_assert(static_cast<bool>(!bool_t{}));
static_assert(static_cast<bool>(bool_t{true} != bool_t{false}));
static_assert(static_cast<bool>(null == null_t{}));
static_assert(!static_cast<bool>(null != null_t{}));
static void require(bool ok) { if (!ok) throw std::runtime_error("Value consumer proof failed"); }
int main() {
    bool_t yes{true}, no{};
    int calls = 0;
    const auto effect = [&] { ++calls; return yes; };
    require(!(no && effect()) && calls == 0);
    require((yes || effect()) && calls == 0);
    require((yes && effect()) && calls == 1);
    require((no || effect()) && calls == 2);
    if (yes) ++calls;
    if (no) throw std::runtime_error("Default bool_t must be false");
    require(calls == 3);
    const int64 a{-3}, b{4};
    require(static_cast<bool>(a < b));
    require(static_cast<bool>(a <= b));
    require(static_cast<bool>(b > a));
    require(static_cast<bool>(b >= a));
    require(static_cast<bool>(a != b));
    require(static_cast<bool>(a == a));
}
