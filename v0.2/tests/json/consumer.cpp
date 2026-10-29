#include "scpp/json.hpp"
#include "../hash/allocation_probe.hpp"
#include <cstdio>
#include <cstdlib>
#include <limits>
#include <locale>
#include <string>
#include <type_traits>
using namespace scpp;
void require(bool ok) { if (!ok) std::abort(); }
std::string bytes(const string& value) { return {value.data(), static_cast<std::size_t>(value.size().native_value())}; }
static_assert(std::is_same_v<decltype(json::decode(string{})), result_dynamic>);
static_assert(std::is_same_v<decltype(json::decode_mixed(string{})), result_mixed>);
template<class Value> auto decode(const char* text) {
    if constexpr (std::is_same_v<Value, mixed>) return json::decode_mixed(string{text});
    else return json::decode(string{text});
}
template<class Value> void grammar() {
    auto null = decode<Value>("null");
    require(null.has_value().native_value() && null.value().kind() == boxed_kind::null);
    auto no = decode<Value>("false");
    require(no.has_value().native_value() && !no.value().get_boolean().native_value());
    auto tree = decode<Value>(R"({"a":[null,false,true,42,-9,1.25,"\uD834\uDD1E\u0000"]})");
    require(tree.has_value().native_value());
    auto row = tree.value().read(string{"a"});
    require(row.container_size().native_value() == 7);
    require(row.read(int64{3LL}).get_integer().native_value() == 42);
    require(row.read(int64{4LL}).get_integer().native_value() == -9);
    require(row.read(int64{5LL}).get_floating().native_value() == 1.25);
    require(bytes(row.read(int64{6LL}).get_string()) == std::string("\xF0\x9D\x84\x9E\0", 5));
    for (auto bad : {"", " ", "nul", "null x", "01", "+1", ".1", "1.", "1e", "1e+", "--1", "1e999", "[", "[1,]", "{", "{\"a\"}", "{\"a\":1,}", "{\"a\":}", "\"\\x\"", "\"\\uD800\"", "\"\\uDC00\"", "\"\\uD800\\u0041\"", "\"\n\"", "\"unterminated"}) {
        auto failed = decode<Value>(bad);
        require(failed.has_error().native_value());
        require(bytes(failed.error().get_message()).find("byte ") != std::string::npos);
    }
    auto duplicate = decode<Value>(R"({"a":1,"\u0061":2})");
    require(duplicate.has_error().native_value());
    require(bytes(duplicate.error().get_message()).find("byte 7: duplicate") != std::string::npos);
    require(decode<Value>(R"({"a":{"x":1},"b":{"x":2}})").has_value().native_value());
    require(decode<Value>("9223372036854775807").value().get_integer().native_value() == std::numeric_limits<long long>::max());
    require(decode<Value>("-9223372036854775808").value().get_integer().native_value() == std::numeric_limits<long long>::min());
    require(decode<Value>("9223372036854775808").value().kind() == boxed_kind::floating);
    require(decode<Value>("1e-999").value().get_floating().native_value() == 0);
    for (auto bad : {R"({"2147483648":0})", R"({"4294967295":0})"}) {
        auto failed = decode<Value>(bad);
        require(failed.has_error().native_value());
        require(bytes(failed.error().get_message()).find("byte 1: numeric object key exceeds INT32_MAX") != std::string::npos);
    }
    auto keys = decode<Value>(R"({"0":1,"00":2,"-1":3,"2147483647":4,"4294967296":5,"99999999999999999999999":6})");
    require(keys.has_value().native_value());
    require(keys.value().read(int64{0LL}).get_integer().native_value() == 1);
    require(keys.value().read(string{"00"}).get_integer().native_value() == 2);
    require(keys.value().read(string{"-1"}).get_integer().native_value() == 3);
    require(keys.value().read(int64{2147483647LL}).get_integer().native_value() == 4);
    require(keys.value().read(string{"4294967296"}).get_integer().native_value() == 5);
    require(keys.value().read(string{"99999999999999999999999"}).get_integer().native_value() == 6);
    require(bytes(json::encode(decode<Value>("{}").value()).value()) == "[]");
    require(bytes(json::encode(decode<Value>(R"({"0":1,"1":2})").value()).value()) == "[1,2]");
    require(bytes(json::encode(decode<Value>(R"({"1":2,"0":1})").value()).value()) == R"({"1":2,"0":1})");
    require(bytes(json::encode(keys.value()).value()) == R"({"0":1,"00":2,"-1":3,"2147483647":4,"4294967296":5,"99999999999999999999999":6})");
    auto escaped = Value::from_string(string{"\0\x01\b\f\n\r\t\"\\/", int64{10LL}});
    require(bytes(json::encode(escaped).value()) == R"("\u0000\u0001\b\f\n\r\t\"\\/")");
    // Raw non-ASCII bytes remain donor-compatible; escaped Unicode is validated above.
    auto raw = Value::from_string(string{"\xff", int64{1LL}});
    require(bytes(json::encode(raw).value()) == std::string("\"\xff\"", 3));
    for (double bad : {std::numeric_limits<double>::infinity(), -std::numeric_limits<double>::infinity(), std::numeric_limits<double>::quiet_NaN()})
        require(json::encode(Value::from_floating(float64{bad})).has_error().native_value());
    require(bytes(json::encode(Value::from_integer(int64{42LL})).value()) == "42");
}
void ownership_and_cycles() {
    auto mixed_result = json::decode_mixed(string{R"({"a":1})"});
    auto independent = mixed_result.value(); independent.set(string{"a"}, mixed::from_integer(int64{2LL}));
    require(mixed_result.value().read(string{"a"}).get_integer().native_value() == 1);
    auto dynamic_result = json::decode(string{R"({"a":1})"});
    auto shared = dynamic_result.value(); shared.set(string{"a"}, dynamic::from_integer(int64{2LL}));
    require(dynamic_result.value().read(string{"a"}).get_integer().native_value() == 2);
    auto graph = dynamic::container();
    graph.set(string{"left"}, shared); graph.set(string{"right"}, shared);
    require(bytes(json::encode(graph).value()) == R"({"left":{"a":2},"right":{"a":2}})");
    shared.set(string{"back"}, graph);
    require(json::encode(graph).has_error().native_value());
    require(shared.remove(string{"back"}).native_value());
    require(json::encode(graph).has_value().native_value());
    graph.set(string{"self"}, graph);
    require(json::encode(graph).has_error().native_value());
    require(graph.remove(string{"self"}).native_value());
}
void allocation_failures() {
    const string input{R"({"long unique allocation probe name":[1,2,"a string long enough to allocate its contents"]})"};
    auto tree = json::decode(input).value();
    bool decode_failed = false, decode_succeeded = false, encode_failed = false, encode_succeeded = false;
    for (long n = 0; n < 80; ++n) {
        allocation_probe::fail_after = n;
        try { auto value = json::decode(input); allocation_probe::fail_after = -1; require(value.has_value().native_value()); decode_succeeded = true; }
        catch (const std::bad_alloc&) { allocation_probe::fail_after = -1; decode_failed = true; }
        allocation_probe::fail_after = n;
        try { auto value = json::encode(tree); allocation_probe::fail_after = -1; require(value.has_value().native_value()); encode_succeeded = true; }
        catch (const std::bad_alloc&) { allocation_probe::fail_after = -1; encode_failed = true; }
    }
    require(decode_failed && decode_succeeded && encode_failed && encode_succeeded);
    require(json::encode(tree).has_value().native_value());
    // Formatting must not swallow a failed stream allocation into partial output.
    auto number = dynamic::from_floating(float64{1.2345678901234567e100});
    auto expected = bytes(json::encode(number).value());
    for (long n = 0; n < 24; ++n) {
        allocation_probe::fail_after = n;
        try { auto encoded = json::encode(number); allocation_probe::fail_after = -1; require(bytes(encoded.value()) == expected); }
        catch (const std::bad_alloc&) { allocation_probe::fail_after = -1; }
    }
}
struct comma_punctuation : std::numpunct<char> { char do_decimal_point() const override { return ','; } };
int main() {
    grammar<mixed>(); grammar<dynamic>(); ownership_and_cycles(); allocation_failures();
    auto old = std::locale::global(std::locale(std::locale::classic(), new comma_punctuation));
    require(bytes(json::encode(dynamic::from_floating(float64{1.25})).value()) == "1.25");
    require(json::decode(string{"1.25"}).value().get_floating().native_value() == 1.25);
    std::locale::global(old);
    std::puts("JSON consumer passed");
}
