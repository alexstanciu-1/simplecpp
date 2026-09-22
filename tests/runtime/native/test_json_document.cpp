#include "test_common.hpp"
#include "modules/json/document.hpp"
#include <limits>
#include <type_traits>

using namespace scpp;
using namespace scpp::json;
static_assert(!std::is_default_constructible_v<json_document>);
static_assert(!std::is_default_constructible_v<json_node>);
static_assert(!std::is_copy_assignable_v<json_document>);
static_assert(!std::is_copy_assignable_v<json_node>);
static shared_p<json_document> parse(const std::string &text, int depth = 128) {
	shared_p<json_parse_error> diagnostic;
	auto r = document_parse(string_t(text), diagnostic, int_t<>(depth));
	assert(r.has_value().native_value()); assert(!diagnostic.has_value().native_value());
	return r.value();
}
static shared_p<json_node> root(const std::string &text, int depth = 128) { return document_root(parse(text, depth)).value(); }
static void fails(const std::string &text, const char *category, std::int64_t offset, int depth = 128) {
	shared_p<json_parse_error> diagnostic;
	auto r = document_parse(string_t(text), diagnostic, int_t<>(depth));
	assert(r.has_error().native_value()); assert(diagnostic.has_value().native_value());
	assert(diagnostic->category.native_value() == category);
	if (offset >= 0) assert(diagnostic->byte_offset.native_value() == offset);
	assert(!diagnostic->message.native_value().empty());
	assert(document_parse(string_t("null"), diagnostic).has_value().native_value());
	assert(!diagnostic.has_value().native_value());
}
int main() {
	for (const auto &[text, kind] : std::initializer_list<std::pair<std::string, std::string>>{
		{"null","null"},{"false","boolean"},{"true","boolean"},{"0","number"},{"\"text\"","string"},{"[]","array"},{"{}","object"}}) {
		assert(node_kind(root(text)).value().native_value() == kind);
	}
	assert(!node_boolean(root("false")).value().native_value());
	assert(node_boolean(root("true")).value().native_value());
	for (const auto &text : {"[]", "{}"}) assert(node_size(root(text)).value().native_value() == 0);
	auto tree = root(R"({"0":"zero","01":null,"false":false,"a":[{},[],{"x":7}],"\u0030":"last","0":"final"})");
	assert(node_size(tree).value().native_value() == 4);
	assert(node_key(tree, int_t<>(0)).value().native_value() == "0");
	assert(node_key(tree, int_t<>(1)).value().native_value() == "01");
	assert(node_string(node_member(tree, string_t("0")).value()).value().native_value() == "final");
	assert(node_has(tree, string_t("01")).value().native_value());
	assert(!node_has(tree, string_t("1")).value().native_value());
	assert(node_kind(node_member(tree, string_t("01")).value()).value().native_value() == "null");
	assert(node_member(tree, string_t("1")).has_error().native_value());
	auto array = node_member(tree, string_t("a")).value();
	assert(node_kind(node_at(array, int_t<>(0)).value()).value().native_value() == "object");
	assert(node_kind(node_at(array, int_t<>(1)).value()).value().native_value() == "array");
	assert(node_int(node_member(node_at(array, int_t<>(2)).value(), string_t("x")).value()).value().native_value() == 7);
	assert(node_at(array, int_t<>(-1)).has_error().native_value());
	assert(node_at(array, int_t<>(3)).has_error().native_value());
	assert(node_key(tree, int_t<>(-1)).has_error().native_value());
	assert(node_key(tree, int_t<>(4)).has_error().native_value());
	assert(node_at(tree, int_t<>(0)).has_error().native_value());
	assert(node_key(array, int_t<>(0)).has_error().native_value());
	assert(node_has(array, string_t("0")).has_error().native_value());
	assert(node_member(array, string_t("0")).has_error().native_value());
	assert(node_size(root("null")).has_error().native_value());
	assert(node_string(root("null")).has_error().native_value());
	assert(node_boolean(root("0")).has_error().native_value());
	assert(node_number(root("true")).has_error().native_value());
	assert(node_int(root("\"1\"")).has_error().native_value());
	shared_p<json_node> absent;
	shared_p<json_document> absent_document;
	assert(document_root(absent_document).has_error().native_value());
	assert(node_kind(absent).has_error().native_value());
	assert(node_at(absent, int_t<>(0)).has_error().native_value());

	// Decoded key equality, exact bytes, no normalization; embedded NUL is retained.
	auto keys = root("{\"\\u0061\":1,\"a\":2,\"\\u0000\":3,\"é\":4,\"e\\u0301\":5}");
	assert(node_size(keys).value().native_value() == 4);
	assert(node_key(keys, int_t<>(0)).value().native_value() == "a");
	assert(node_int(node_member(keys, string_t("a")).value()).value().native_value() == 2);
	assert(node_int(node_member(keys, string_t(std::string(1, '\0'))).value()).value().native_value() == 3);
	assert(node_key(keys, int_t<>(2)).value().native_value() != node_key(keys, int_t<>(3)).value().native_value());
	assert(node_string(root(R"("\"\\\/\b\f\n\r\t\u0000")")).value().native_value() == std::string("\"\\/\b\f\n\r\t\0", 9));
	assert(node_string(root(R"("\uD83D\uDE00")")).value().native_value() == "😀");
	assert(node_string(root("\"€é😀\"")).value().native_value() == "€é😀");
	for (auto bytes : {std::string("\xC0\xAF"),std::string("\x80"),std::string("\xE0\x80\x80"),std::string("\xED\xA0\x80"),std::string("\xF4\x90\x80\x80"),std::string("\xF5\x80\x80\x80"),std::string("\xC2x"),std::string("\xE2\x82")}) fails("\"" + bytes + "\"", "utf8", 1);
	fails(std::string("\xC2"), "utf8", 0);
	for (auto text : {R"("\uD800")",R"("\uDC00")",R"("\uD800\u0041")",R"("\uZZZZ")",R"("\u12")"}) fails(text, "unicode_escape", -1);

	for (const auto &[text, expected] : std::initializer_list<std::pair<std::string, std::int64_t>>{
		{"9223372036854775807",std::numeric_limits<std::int64_t>::max()}, {"-9223372036854775808",std::numeric_limits<std::int64_t>::min()}, {"-0",0}, {"17",17}})
		assert(node_int(root(text)).value().native_value() == expected);
	for (auto text : {"9223372036854775808","-9223372036854775809","1.0","1e0","1.25","1e999999","1e-999999"}) {
		auto n = root(text); assert(node_number(n).value().native_value() == text); assert(node_int(n).has_error().native_value());
	}
	for (auto text : {"", " ", "null false", "[1,]", "{\"x\":1,}", "[", "{", "{x:1}", "01", "-", "1.", "1e+", "+1", "NaN", "\"\\q\"", "\"a\nb\"", "\"unterminated", "{\"a\" 1}"}) fails(text, "syntax", -1);
	fails("null x", "syntax", 5);
	fails("[", "syntax", 1);
	fails("\"x\n\"", "syntax", 2);
	fails("[]", "invalid_limit", 0, -1);
	fails(std::string("\xEF\xBB\xBF") + "null", "syntax", 0);
	fails(std::string(129, '[') + "0" + std::string(129, ']'), "depth", 128);
	fails("[]", "invalid_limit", 0, 0); fails("[]", "invalid_limit", 0, 257);
	assert(node_kind(root("[{}]", 2)).value().native_value() == "array");
	fails("[{}]", "depth", 1, 1);
	assert(node_kind(root(std::string(256,'[') + "0" + std::string(256,']'),256)).value().native_value() == "array");
	fails(std::string(257,'[') + "0" + std::string(257,']'), "depth", 256, 256);

	// Views own their arena after document/input replacement, including duplicates.
	auto doc = parse("{\"v\":[42]}");
	auto held = node_at(node_member(document_root(doc).value(), string_t("v")).value(), int_t<>(0)).value();
	doc = parse("null"); doc.reset(); tree.reset();
	assert(node_int(held).value().native_value() == 42);
}
