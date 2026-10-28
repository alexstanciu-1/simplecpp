#include "json/pull_reader.hpp"
#include <limits>
#include <type_traits>
#include <iostream>

using scpp::tools::json::pull_reader;
using scpp::tools::json::read_error;

static void require(bool condition) {
    if (!condition) throw std::runtime_error("JSON proof failed");
}

template<class F>
static void fails(F work, const std::string &category) {
    try { work(); }
    catch (const read_error &error) {
        require(error.category == category);
        require(std::string(error.what()).find("probe.json: byte ") == 0);
        return;
    }
    throw std::runtime_error("Expected JSON failure: " + category);
}

static void consume(pull_reader &reader) {
    const auto kind = reader.kind();
    if (kind == "object") {
        reader.begin_object();
        while (reader.next_property()) consume(reader);
    } else if (kind == "array") {
        reader.begin_array();
        while (reader.next_element()) consume(reader);
    } else if (kind == "string") reader.read_string();
    else if (kind == "number") reader.read_number();
    else if (kind == "boolean") reader.read_boolean();
    else reader.read_null();
}

int main() {
    static_assert(!std::is_copy_constructible_v<pull_reader>);
    static_assert(!std::is_move_constructible_v<pull_reader>);
    std::string source = R"({"values":[-9223372036854775808,9223372036854775807],"raw":1e9999})";
    pull_reader reader(source, "probe.json");
    source.assign("replaced");
    reader.begin_object(); require(reader.next_property());
    require(reader.property_name() == "values");
    reader.begin_array(); require(reader.next_element());
    require(reader.read_integer() == std::numeric_limits<std::int64_t>::min());
    require(reader.next_element());
    require(reader.read_integer() == std::numeric_limits<std::int64_t>::max());
    require(!reader.next_element()); require(reader.next_property());
    require(reader.read_number() == "1e9999");
    require(!reader.next_property()); reader.finish_document();
    fails([&] { reader.kind(); }, "protocol");

    pull_reader strings(R"(["\uD83D\uDE00","\u0000","\"\\\/\b\f\n\r\t"])", "probe.json");
    strings.begin_array(); require(strings.next_element());
    require(strings.read_string() == "\xF0\x9F\x98\x80");
    require(strings.next_element()); require(strings.read_string() == std::string(1, '\0'));
    require(strings.next_element()); require(strings.read_string() == "\"\\/\b\f\n\r\t");
    require(!strings.next_element()); strings.finish_document();

    pull_reader wrong(R"({"codes":[false]})", "probe.json");
    wrong.begin_object(); wrong.next_property(); wrong.begin_array(); wrong.next_element();
    try { wrong.read_integer(); throw std::runtime_error("Expected type error"); }
    catch (const read_error &error) {
        require(error.category == "type" && error.path == "$[\"codes\"][0]");
        require(error.position == 10);
    }
    fails([&] { wrong.read_boolean(); }, "protocol");

    for (const std::string input : {"", "[1,]", "{\"a\":1,}", "01", "-", "1.", "1e+", "true false", "[", "{", "nul", "NaN"}) {
        fails([&] { pull_reader r(input, "probe.json"); consume(r); r.finish_document(); }, "syntax");
    }
    for (const std::string input : {R"("\uD800")", R"("\uDC00")", R"("\uD800\u0041")", R"("\uZZZZ")"}) {
        fails([&] { pull_reader r(input, "probe.json"); consume(r); }, "unicode_escape");
    }
    for (const std::string input : {"\"\xC0\xAF\"", "\"\xED\xA0\x80\"", "\"\xF4\x90\x80\x80\"", "\"\xE2\x82"}) {
        fails([&] { pull_reader r(input, "probe.json"); }, "utf8");
    }
    fails([] { pull_reader r(R"({"a":1,"\u0061":2})", "probe.json"); consume(r); }, "duplicate_key");
    for (const std::string input : {"9223372036854775808", "-9223372036854775809", "1.0", "1e0"}) {
        fails([&] { pull_reader r(input, "probe.json"); r.read_integer(); }, "integer");
    }
    for (int depth : {0, 257}) fails([&] { pull_reader r("[]", "probe.json", depth); }, "invalid_limit");
    pull_reader bounded("[[]]", "probe.json", 2); consume(bounded); bounded.finish_document();
    fails([] { pull_reader r("[[[]]]", "probe.json", 2); consume(r); }, "depth");
    fails([] { pull_reader r("[]", "probe.json"); r.begin_array(); r.finish_document(); }, "protocol");
    fails([] { pull_reader r("{}", "probe.json"); r.reject("missing field"); }, "schema");
    pull_reader values("[true,false,null]", "probe.json"); consume(values); values.finish_document();
    std::cout << "Native JSON reader proofs passed\n";
}
