#include <scpp/short_names.hpp>
#include <cmath>
#include <limits>
#include <stdexcept>
#include <type_traits>

static void require(bool ok) { if (!ok) throw std::runtime_error("Vector cast proof failed"); }
template<class Error, class F> static void fails(F f) {
    try { f(); } catch (const Error &) { return; }
    throw std::runtime_error("Expected vector cast failure");
}
template<class V> concept native_index = requires(V v) { v.at(0); };
template<class V> concept native_value = requires(V v) { v.append(1); };
template<class V> concept native_text = requires(V v) { v.append("Ada"); };
static_assert(!native_index<vector_string> && !native_index<vector_int64>);
static_assert(!native_value<vector_int64> && !native_text<vector_string>);
static_assert(std::is_constructible_v<vector_int32, const vector_int64&>);
static_assert(!std::is_convertible_v<vector_int64, vector_int32>);
static_assert(!std::is_constructible_v<vector_null_t, vector_int64>);
static_assert(!std::is_constructible_v<vector_int64, vector_float64>); // Not requested.
int main() {
    vector_int64 source;
    source.append(int64{7}); source.append(int64{2147483648LL});
    fails<std::out_of_range>([&] { static_cast<void>(static_cast<vector_int32>(source)); });
    require(source.at(int64{0}).native_value() == 7 && source.at(int64{1}).native_value() == 2147483648LL);
    vector_int32 unchanged; unchanged.append(int32{9});
    fails<std::out_of_range>([&] { unchanged = static_cast<vector_int32>(source); });
    require(unchanged.at(int64{0}).native_value() == 9);
    source.set(int64{1}, int64{20});
    const auto narrow = static_cast<vector_int32>(source);
    require(static_cast<bool>(static_cast<vector_int64>(narrow) == source));
    const auto text = static_cast<vector_string>(source);
    require(static_cast<bool>(text.at(int64{0}) == string{"7"}));
    require(static_cast<bool>(static_cast<vector_int64>(text) == source));
    vector_string invalid;
    invalid.append(string{"1"}); invalid.append(string{"bad"});
    fails<std::invalid_argument>([&] { static_cast<void>(static_cast<vector_int64>(invalid)); });
    require(static_cast<bool>(invalid.at(int64{1}) == string{"bad"}));
    vector_bool_t flags; flags.append(bool_t{true}); flags.append(bool_t{false});
    require(static_cast<bool>(static_cast<vector_bool_t>(static_cast<vector_string>(flags)) == flags));
    vector_float64 floats = static_cast<vector_float64>(source);
    require(floats.at(int64{1}).native_value() == 20.0);
    floats.append(float64{std::numeric_limits<double>::infinity()});
    fails<std::out_of_range>([&] { static_cast<void>(static_cast<vector_string>(floats)); });
    floats.set(int64{2}, float64{std::numeric_limits<double>::quiet_NaN()});
    require(!static_cast<bool>(floats == floats)); // Preserve element IEEE comparison.
    vector_null_t nulls; nulls.append(null); nulls.append(null_t{});
    auto null_copy = nulls;
    null_copy.set(int64{0}, null_t{});
    require(static_cast<bool>(null_copy == nulls));
}
