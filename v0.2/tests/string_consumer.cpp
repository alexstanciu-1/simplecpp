#include <scpp/short_names.hpp>
#include <scpp/native_bridge.hpp>
#include <cmath>
#include <cstring>
#include <limits>
#include <stdexcept>
#include <string>
#include <type_traits>
#include <utility>

static void require(bool ok) { if (!ok) throw std::runtime_error("String consumer proof failed"); }
template<class Error, class F> static void fails(F f) {
    try { f(); } catch (const Error &) { return; }
    throw std::runtime_error("Expected string boundary failure");
}
template<class L, class R> concept adds = requires(L l, R r) { l + r; };
static_assert(!std::is_convertible_v<const char*, string>);
static_assert(!std::is_convertible_v<int64, string>);
static_assert(!std::is_convertible_v<string, int64>);
static_assert(!std::is_constructible_v<string, int>);
static_assert(!std::is_constructible_v<string, null_t>);
static_assert(!std::is_convertible_v<string, bool>);
static_assert(!adds<string, int64> && !adds<string, const char*>);
static_assert(std::is_same_v<decltype(string{} == string{}), bool_t>);
static_assert(std::is_nothrow_move_constructible_v<string> && std::is_nothrow_move_assignable_v<string>);
static_assert(std::is_same_v<decltype(std::declval<string&>() += string{}), string&>);

int main() {
    string empty;
    require(static_cast<bool>(empty.empty()) && empty.size().native_value() == 0 && empty.data()[0] == '\0');
    const char bytes[] = {'A', '\0', 'B'};
    string binary{bytes, int64{3}};
    require(binary.size().native_value() == 3 && std::memcmp(binary.data(), bytes, 3) == 0);
    string ada{"Ada"}, grace{"Grace"};
    auto combined = ada + grace;
    require(static_cast<bool>(combined == string{"AdaGrace"}));
    require(static_cast<bool>(ada < grace) && static_cast<bool>(ada != grace));
    require(static_cast<bool>(ada <= ada) && static_cast<bool>(grace >= ada) && static_cast<bool>(grace > ada));
    string assigned{"old"};
    assigned = binary;
    require(static_cast<bool>(assigned == binary));
    assigned.clear();
    require(binary.size().native_value() == 3);
    assigned = empty;
    require(static_cast<bool>(assigned == string{""}));
    auto copy = binary;
    copy += copy;
    require(copy.size().native_value() == 6 && binary.size().native_value() == 3);
    require(std::memcmp(copy.data() + 3, bytes, 3) == 0);
    auto moved = std::move(copy);
    require(static_cast<bool>(copy.empty()) && moved.size().native_value() == 6);
    copy.append(ada); // A moved-from object remains usable.
    require(static_cast<bool>(copy == ada));
    auto *self = &copy;
    copy = *self;
    copy = std::move(*self);
    require(static_cast<bool>(copy == ada));
    moved = std::move(copy);
    require(static_cast<bool>(copy.empty()) && static_cast<bool>(moved == ada));
    empty.append(empty);
    require(static_cast<bool>(empty.empty()));
    const std::string long_bytes(4096, 'x');
    string large{long_bytes.data(), int64{4096}};
    auto independent = large;
    large.clear();
    require(independent.size().native_value() == 4096 && static_cast<bool>(large.empty()));
    fails<std::invalid_argument>([] { string invalid{nullptr}; });
    fails<std::invalid_argument>([] { string invalid{nullptr, int64{0}}; });
    fails<std::out_of_range>([] { string invalid{"x", int64{-1}}; });
    require(static_cast<int64>(string{"-9223372036854775808"}).native_value() == std::numeric_limits<long long>::min());
    require(static_cast<bool>(static_cast<string>(int64{42}) == string{"42"}));
    require(static_cast<float64>(string{"1.25e2"}).native_value() == 125.0);
    require(static_cast<bool>(static_cast<string>(float64{1.25}) == string{"1.25"}));
    require(static_cast<bool>(static_cast<bool_t>(string{"true"})));
    require(static_cast<bool>(static_cast<string>(bool_t{false}) == string{"false"}));
    fails<std::invalid_argument>([] { static_cast<void>(static_cast<bool_t>(string{"0"})); });
    fails<std::invalid_argument>([&] { static_cast<void>(static_cast<int64>(binary)); });
    fails<std::out_of_range>([] { static_cast<void>(static_cast<int32>(string{"2147483648"})); });
    fails<std::out_of_range>([] { static_cast<void>(static_cast<string>(float64{std::numeric_limits<double>::infinity()})); });
    int32 unchanged{7};
    fails<std::out_of_range>([&] { unchanged = static_cast<int32>(string{"2147483648"}); });
    require(unchanged.native_value() == 7);
    require((int64{2} + 1).native_value() == 3); // Composition with the optional numeric bridge.
}
