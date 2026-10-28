#include "api_generator/value.hpp"
#include "json/pull_reader.hpp"
#include <iostream>
#include <locale>

using namespace scpp::api_generation;

static void require(bool condition) {
    if (!condition) throw std::runtime_error("Metadata value proof failed");
}
template<class F> static void fails(F work, const std::string &message) {
    try { work(); }
    catch (const std::exception &error) {
        require(std::string(error.what()).find(message) != std::string::npos);
        return;
    }
    throw std::runtime_error("Expected failure: " + message);
}
static bool equal(const value &a, const value &b) {
    if (a.type != b.type || a.scalar != b.scalar || a.items.size() != b.items.size() || a.fields.size() != b.fields.size()) return false;
    for (std::size_t i = 0; i < a.items.size(); ++i) if (!equal(a.items[i], b.items[i])) return false;
    for (const auto &[key, item] : a.fields) {
        const auto found = b.fields.find(key);
        if (found == b.fields.end() || !equal(item, found->second)) return false;
    }
    return true;
}
struct grouped_numbers final : std::numpunct<char> {
    char do_thousands_sep() const override { return '_'; }
    std::string do_grouping() const override { return "\1"; }
};
int main() {
    auto tree = parse_json(R"({"z":[null,true,false,18446744073709551615,-0,1.2300e+99],"a":{"text":"\u0000\uD83D\uDE00"}})");
    require(tree.at("z").items[3].scalar == "18446744073709551615");
    require(tree.at("z").items[4].scalar == "-0");
    require(tree.at("z").items[5].scalar == "1.2300e+99");
    const auto encoded = json_text(tree);
    require(encoded.starts_with("{\"a\":"));
    require(equal(tree, parse_json(encoded)));
    require(encoded == json_text(parse_json(encoded)));
    for (const std::string input : {"null", "true", "false", "-0", "1e9999", "[]", "{}", "\"hello\""}) {
        const auto item = parse_json(input);
        require(equal(item, parse_json(json_text(item))));
    }
    std::string controls;
    for (int c = 0; c < 32; ++c) controls.push_back(static_cast<char>(c));
    controls += "\"\\/";
    const auto previous = std::locale();
    std::locale::global(std::locale(previous, new grouped_numbers));
    const auto escaped = json_text(value(controls));
    std::locale::global(previous);
    require(parse_json(escaped).text() == controls);

    value object;
    object["name"] = "integer";
    object["enabled"] = parse_json("true");
    require(object.has("name") && object.at("name").text() == "integer");
    require(object.get("missing", "fallback") == "fallback");
    require(object.yes("enabled") && !object.yes("absent"));
    fields_only(object, "name enabled", "type");
    fails([&] { fields_only(object, "name", "type"); }, "type: unknown field enabled");
    fails([&] { object.at("missing"); }, "Missing metadata field");
    fails([&] { object.yes("name"); }, "Expected metadata boolean");
    fails([&] { object.text(); }, "Expected metadata string");
    auto array = value::array();
    fails([&] { array["wrong"] = "value"; }, "Expected metadata object");
    fails([&] { array.has("wrong"); }, "Expected metadata object");
    fails([&] { fields_only(array, "", "type"); }, "expected object");

    try { parse_json(R"({"a":[1,]})", "integers.json"); throw std::runtime_error("Expected parse failure"); }
    catch (const scpp::tools::json::read_error &error) {
        require(error.category == "syntax" && error.path == "$[\"a\"][1]");
        require(std::string(error.what()).starts_with("integers.json: byte "));
    }
    fails([] { parse_json(R"({"a":1,"\u0061":2})"); }, "duplicate object key");
    fails([] { parse_json("{} trailing"); }, "trailing");
    auto malformed = value::array(); malformed.fields["x"] = "value";
    fails([&] { json_text(malformed); }, "Unexpected metadata object fields");
    auto number = parse_json("1"); number.scalar = "1,2";
    fails([&] { json_text(number); }, "trailing");
    number.scalar = " 1";
    fails([&] { json_text(number); }, "Invalid metadata number token");
    auto boolean = parse_json("true"); boolean.scalar = "yes";
    fails([&] { json_text(boolean); }, "Invalid metadata boolean");
    fails([] { json_text(value(std::string("\xC0\xAF"))); }, "UTF-8");
    value nested;
    for (unsigned i = 0; i < 128; ++i) {
        auto outer = value::array(); outer.items.push_back(std::move(nested)); nested = std::move(outer);
    }
    require(equal(nested, parse_json(json_text(nested))));
    auto excessive = value::array(); excessive.items.push_back(std::move(nested));
    fails([&] { json_text(excessive); }, "nesting limit");
    fails([] { parse_json(std::string(129, '[') + "null" + std::string(129, ']')); }, "nesting limit");
    std::cout << "Metadata value proofs passed\n";
}
