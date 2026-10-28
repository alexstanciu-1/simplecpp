#include <scpp/short_names.hpp>
#include <scpp/native_bridge.hpp>
#include <stdexcept>
#include <type_traits>
#include <utility>

static void require(bool ok) { if (!ok) throw std::runtime_error("Vector consumer proof failed"); }
template<class Error, class F> static void fails(F f) {
    try { f(); } catch (const Error &) { return; }
    throw std::runtime_error("Expected vector failure");
}
template<class V, class I> concept reads = requires(V v, I i) { v.at(i); };
template<class V, class T> concept appends = requires(V v, T x) { v.append(x); };
template<class V, class I, class T> concept sets = requires(V v, I i, T x) { v.set(i, x); };
template<class V> concept adds = requires(V v) { v + v; };
static_assert(std::is_same_v<decltype(vector_string{}.at(0)), string>);
static_assert(std::is_same_v<decltype(vector_int64{}.at(int64{})), int64>);
static_assert(!reads<vector_string, long> && !reads<vector_int64, unsigned>);
static_assert(!reads<vector_string, double> && !reads<vector_string, bool>);
static_assert(!appends<vector_int64, double> && !appends<vector_int64, long long>);
static_assert(!appends<vector_string, int> && !appends<vector_int64, int32>);
static_assert(!sets<vector_string, long, const char*> && !sets<vector_string, int, int>);
static_assert(!sets<vector_int64, unsigned, int> && !sets<vector_int64, int, double>);
static_assert(!sets<vector_int64, long, double>);
static_assert(!std::is_constructible_v<vector_int64, vector_string>);
static_assert(!adds<vector_string> && !std::is_convertible_v<vector_string, bool>);
static_assert(std::is_nothrow_move_constructible_v<vector_string>);
static_assert(std::is_nothrow_move_assignable_v<vector_string>);

int main() {
    vector_string names;
    require(static_cast<bool>(names.empty()));
    names.append("Ada");
    names.append(string{"Grace"});
    require(names.size().native_value() == 2);
    require(static_cast<bool>(names.at(0) == string{"Ada"}));
    auto detached = names.at(0);
    detached.append(string{"!"});
    require(static_cast<bool>(names.at(0) == string{"Ada"}));
    auto copy = names;
    copy.set(0, "Linus");
    require(static_cast<bool>(copy != names));
    require(static_cast<bool>(names.at(0) == string{"Ada"}));
    names.set(int64{1}, names.at(0));
    names.append(names.at(0));
    require(static_cast<bool>(names.at(1) == names.at(2)));
    for (int i = 0; i < 200; ++i) names.append(names.at(0));
    require(names.size().native_value() == 203 && static_cast<bool>(names.at(202) == string{"Ada"}));
    const auto snapshot = names;
    fails<std::out_of_range>([&] { names.at(-1); });
    fails<std::out_of_range>([&] { names.at(203); });
    fails<std::out_of_range>([&] { names.set(-1, "bad"); });
    fails<std::out_of_range>([&] { names.set(203, string{"bad"}); });
    fails<std::invalid_argument>([&] { names.append(nullptr); });
    fails<std::invalid_argument>([&] { names.set(0, nullptr); });
    require(static_cast<bool>(names == snapshot));
    auto moved = std::move(names);
    require(static_cast<bool>(names.empty()) && static_cast<bool>(moved == snapshot));
    names.append("Reusable");
    auto *self = &names;
    names = *self; names = std::move(*self);
    require(static_cast<bool>(names.at(0) == string{"Reusable"}));
    names = moved;
    moved.clear();
    require(static_cast<bool>(moved.empty()) && names.size().native_value() == 203);
    fails<std::out_of_range>([&] { moved.set(0, "no element"); });
    fails<std::out_of_range>([&] { moved.at(0); });
    require(static_cast<bool>(moved == vector_string{}));
    vector_int64 numbers;
    numbers.append(20); numbers.append(int64{3});
    numbers.set(0, numbers.at(0) + numbers.at(1));
    require(numbers.at(0).native_value() == 23);
    numbers.set(int64{1}, 7);
    numbers.set(0, int64{4});
    require(numbers.at(0).native_value() == 4 && numbers.at(1).native_value() == 7);
    auto integer_copy = numbers;
    numbers.clear();
    require(static_cast<bool>(integer_copy != numbers) && integer_copy.size().native_value() == 2);
}
