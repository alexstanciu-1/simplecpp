#include "test_common.hpp"

#include "modules/json/json.hpp"
#include "scpp/json/from_json.hpp"
#include "scpp/lang/php.hpp"

#include <cmath>
#include <string>

namespace {

void require(bool condition) {
	if (!condition) throw std::runtime_error("JSON contract check failed");
}

static void test_json_decode_scalar_values() {
	const auto null_value = scpp::json::json_decode(scpp::string_t("null")).value();
	require(null_value.kind() == scpp::detail::dynamic_value::kind_t::null_v);

	const auto bool_value = scpp::json::json_decode(scpp::string_t("true")).value();
	require(bool_value.kind() == scpp::detail::dynamic_value::kind_t::bool_v);
	require(bool_value.get_bool().native_value());

	const auto int_value = scpp::json::json_decode(scpp::string_t("123")).value();
	require(int_value.kind() == scpp::detail::dynamic_value::kind_t::int_v);
	require(int_value.get_int().native_value() == 123);

	const auto float_value = scpp::json::json_decode(scpp::string_t("1.5e2")).value();
	require(float_value.kind() == scpp::detail::dynamic_value::kind_t::float_v);
	require(float_value.get_float().native_value() == 150.0);

	const auto string_value = scpp::json::json_decode(scpp::string_t("\"hello\"")).value();
	require(string_value.kind() == scpp::detail::dynamic_value::kind_t::string_v);
	require(string_value.get_string().native_value() == "hello");
}

static void test_json_decode_array_and_object_shapes() {
	const auto array_value = scpp::json::json_decode(scpp::string_t("[1,2,3]")).value();
	require(array_value.kind() == scpp::detail::dynamic_value::kind_t::table_v);
	require(array_value.get_table().is_packed().native_value());
	require(array_value.get_table().size() == 3);
	require(array_value.get_table().at(scpp::int_t<>(0)).get_int().native_value() == 1);

	const auto object_value = scpp::json::json_decode(scpp::string_t("{\"id\":10,\"name\":\"Alex\"}")).value();
	require(object_value.kind() == scpp::detail::dynamic_value::kind_t::table_v);
	require(!object_value.get_table().is_packed().native_value());
	require(object_value.get_table().at(scpp::string_t("id")).get_int().native_value() == 10);
	require(object_value.get_table().at(scpp::string_t("name")).get_string().native_value() == "Alex");
}

static void test_json_decode_nested_shared_model() {
	const auto value = scpp::json::json_decode(scpp::string_t("[{\"id\":10},{\"id\":20}]")).value();
	require(value.kind() == scpp::detail::dynamic_value::kind_t::table_v);

	const auto &outer = value.get_table();
	require(outer.is_packed().native_value());
	require(outer.at(scpp::int_t<>(0)).kind() == scpp::detail::dynamic_value::kind_t::table_v);
	require(!outer.at(scpp::int_t<>(0)).get_table().is_packed().native_value());
	require(outer.at(scpp::int_t<>(0)).get_table().at(scpp::string_t("id")).get_int().native_value() == 10);
}

static void test_json_decode_unicode_and_escapes() {
	const auto value = scpp::json::json_decode(scpp::string_t("\"A\\n\\u20AC\\uD83D\\uDE00\"")).value();
	require(value.kind() == scpp::detail::dynamic_value::kind_t::string_v);
	require(value.get_string().native_value() == std::string("A\n€😀"));
}

static void test_json_encode_shapes() {
	const auto decoded_array = scpp::json::json_decode(scpp::string_t("[1,true,null,\"x\"]")).value();
	require(scpp::json::json_encode(decoded_array).value().native_value() == "[1,true,null,\"x\"]");

	const auto decoded_object = scpp::json::json_decode(scpp::string_t("{\"id\":10,\"name\":\"Alex\"}")).value();
	require(scpp::json::json_encode(decoded_object).value().native_value() == "{\"id\":10,\"name\":\"Alex\"}");
	require(decoded_object.table_if() != nullptr);

	auto shared_table = scpp::shared<scpp::hash_t<scpp::mixed_t>>();
	static_cast<void>(shared_table->append(scpp::mixed_t(scpp::int_t<>(7))));
	static_cast<void>(shared_table->append(scpp::mixed_t(scpp::string_t("ok"))));
	require(scpp::json::json_encode(scpp::mixed_t(scpp::dynamic_box(scpp::dynamic_t<>(shared_table)))).value().native_value() == "[7,\"ok\"]");
}

static void test_json_encode_typed_values() {
	scpp::vector_t<scpp::int_t<>> items;
	items.append(scpp::int_t<>(1));
	items.append(scpp::int_t<>(2));
	items.append(scpp::int_t<>(3));
	require(scpp::json::json_encode(items).value().native_value() == "[1,2,3]");

	scpp::hash_t<scpp::int_t<>> scores;
	scores.set(scpp::string_t("a"), scpp::int_t<>(1));
	scores.set(scpp::string_t("b"), scpp::int_t<>(2));
	require(scpp::json::json_encode(scores).value().native_value() == "{\"a\":1,\"b\":2}");

	scpp::hash_t<scpp::vector_t<scpp::int_t<>>> matrix;
	matrix.set(scpp::string_t("row"), items);
	require(scpp::json::json_encode(matrix).value().native_value() == "{\"row\":[1,2,3]}");

	scpp::nullable<scpp::int_t<>> empty;
	require(scpp::json::json_encode(empty).value().native_value() == "null");
	scpp::nullable<scpp::int_t<>> present(scpp::int_t<>(7));
	require(scpp::json::json_encode(present).value().native_value() == "7");
}

static void test_json_encode_key_contracts() {
	auto object_like = scpp::shared<scpp::hash_t<scpp::mixed_t>>();
	object_like->set(scpp::string_t("a"), scpp::mixed_t(scpp::int_t<>(1)));
	object_like->set(scpp::int_t<>(42), scpp::mixed_t(scpp::int_t<>(2)));
	require(scpp::json::json_encode(scpp::mixed_t(scpp::dynamic_box(scpp::dynamic_t<>(object_like)))).value().native_value() == "{\"a\":1,\"42\":2}");

}

// Exercise repeated requests in one process, including failures after partial parsing.
static void test_json_decode_checked_failures() {
	const char *invalid[] = {
		"", "{", "[", "{\"x\":1 trailing", "[1,]", "{\"a\":}", "null trailing",
		"tru", "01", "-", "1.", "1e+", "1e9999", "\"unterminated",
		"\"\\x\"", "\"\\uZZZZ\"", "\"\\uD800\"", "\"\\uDC00\"",
		"\"\\uD800\\u0041\"", "\"\n\""
	};
	scpp::detail::dynamic_value output(scpp::string_t("unchanged"));
	scpp::error_t error;
	for (const char *text : invalid) {
		const auto parsed = scpp::json::decode(scpp::string_t(text));
		require(parsed.has_error().native_value());
		require(!scpp::php::take(output, error, parsed).native_value());
		require(output.get_string().native_value() == "unchanged");
		require(error.get_message().native_value().starts_with("json error at byte "));
	}
	require(!scpp::php::take(output, error, scpp::php::json_decode(scpp::string_t("{"))).native_value());
	require(error.get_message().native_value() == "json error at byte 1: expected string key");
	require(scpp::php::take(output, error, scpp::json::decode(scpp::string_t("{\"name\":\"repaired\"}"))).native_value());
	require(output.get_table().at(scpp::string_t("name")).get_string().native_value() == "repaired");
	require(scpp::php::take(output, error, scpp::json::decode(scpp::string_t("null"))).native_value());
	require(output.kind() == scpp::detail::dynamic_value::kind_t::null_v);
	require(scpp::php::take(output, error, scpp::json::decode(scpp::string_t("false"))).native_value());
	require(output.kind() == scpp::detail::dynamic_value::kind_t::bool_v);
	require(!output.get_bool().native_value());
}

static void test_json_encode_checked_failures() {
	scpp::string_t output("unchanged");
	scpp::error_t error;
	for (const double number : {
		std::numeric_limits<double>::infinity(),
		-std::numeric_limits<double>::infinity(),
		std::numeric_limits<double>::quiet_NaN()
	}) {
		require(!scpp::php::take(output, error, scpp::json::encode(scpp::float_t(number))).native_value());
		require(error.get_message().native_value() == "json_encode: non-finite float_t is not supported");
		require(output.native_value() == "unchanged");
	}

	// The writer has already produced an array prefix when the nested value fails.
	scpp::vector_t<scpp::float_t> numbers;
	numbers.append(scpp::float_t(1.0));
	numbers.append(scpp::float_t(std::numeric_limits<double>::infinity()));
	require(!scpp::php::take(output, error, scpp::json::encode(numbers)).native_value());
	require(output.native_value() == "unchanged");

	auto owner = scpp::shared<scpp::hash_t<scpp::mixed_t>>();
	scpp::mixed_t weak{scpp::weak_p<scpp::hash_t<scpp::mixed_t>>(owner)};
	require(!scpp::php::take(output, error, scpp::php::json_encode(weak)).native_value());
	require(error.get_message().native_value() == "json_encode: weak tables are not supported");
	require(output.native_value() == "unchanged");

	scpp::vector_t<scpp::mixed_t> nested;
	nested.append(scpp::mixed_t(scpp::int_t<>(1)));
	nested.append(weak);
	require(!scpp::php::take(output, error, scpp::json::encode(nested)).native_value());
	require(output.native_value() == "unchanged");

	require(scpp::php::take(output, error, scpp::json::encode(scpp::string_t("repaired"))).native_value());
	require(output.native_value() == "\"repaired\"");
	require(scpp::php::take(output, error, scpp::json::encode(scpp::null_t{})).native_value());
	require(output.native_value() == "null");
	require(scpp::php::take(output, error, scpp::json::encode(scpp::bool_t(false))).native_value());
	require(output.native_value() == "false");
}

static void test_json_alias_surface() {
	const auto value = scpp::json::decode(scpp::string_t("[1,2]")).value();
	require(value.kind() == scpp::detail::dynamic_value::kind_t::table_v);
	require(scpp::json::encode(value).value().native_value() == "[1,2]");
}

static void test_separated_families() {
	using value = scpp::detail::dynamic_value;
	using scpp::string_t;
	using scpp::int_t;
	const string_t text{"{\"child\":{\"id\":1},\"items\":[null,false,2.5]}"};
	auto shared = scpp::json::decode(text).value();
	auto alias = shared;
	alias.get_table().at(string_t{"child"}).get_table().set(string_t{"id"}, value{int_t<>{9}});
	require(shared.get_table().at(string_t{"child"}).get_table().at(string_t{"id"}).get_int().native_value() == 9);
	auto owned = scpp::json::decode_mixed(text).value();
	auto copied = owned;
	copied.get_hash().at(string_t{"child"}).get_hash().set(string_t{"id"}, scpp::mixed_t{int_t<>{9}});
	require(owned.kind() == scpp::mixed_t::kind_t::table_v);
	require(owned.get_hash().at(string_t{"child"}).kind() == scpp::mixed_t::kind_t::table_v);
	require(owned.get_hash().at(string_t{"child"}).get_hash().at(string_t{"id"}).get_int().native_value() == 1);
	require(scpp::json::encode(owned).value().native_value() == text.native_value());
	require(scpp::json::encode(copied).value().native_value() == scpp::json::encode(shared).value().native_value());
	for (const char *input : {"null", "false", "123", "2.5", "\"hello\"", "[]", "{}"}) {
		const auto dynamic = scpp::json::decode(string_t{input});
		const auto mixed = scpp::php::json_decode_mixed(string_t{input});
		require(dynamic.has_value().native_value() && mixed.has_value().native_value());
		require(scpp::json::encode(dynamic.value()).value().native_value()
			== scpp::json::encode(mixed.value()).value().native_value());
	}
	const auto bad = scpp::json::decode_mixed(string_t{"[1,]"});
	require(bad.has_error().native_value());
	// Repeated aliases are valid JSON trees; only an active-path cycle rejects.
	value root = value::make_container();
	root.get_table().append(shared);
	root.get_table().append(shared);
	require(scpp::json::encode(root).has_value().native_value());
	root.get_table().append(root);
	const auto cyclic = scpp::json::encode(root);
	(void)root.get_table().remove(int_t<>{2});
	require(cyclic.has_error().native_value());
	root.get_table().append(value{scpp::float_t{std::numeric_limits<double>::infinity()}});
	require(scpp::json::encode(root).has_error().native_value());
	require(scpp::json::encode(value{int_t<>{7}}).value().native_value() == "7");
	const auto typed = scpp::json::from_json<scpp::vector_t<int_t<>>>(string_t{"[3,4]"});
	require(typed.size() == 2 && typed.native_value()[1].native_value() == 4);
}

} // namespace

int main() {
	test_json_decode_scalar_values();
	test_json_decode_array_and_object_shapes();
	test_json_decode_nested_shared_model();
	test_json_decode_unicode_and_escapes();
	test_json_encode_shapes();
	test_json_encode_typed_values();
	test_json_encode_key_contracts();
	test_json_alias_surface();
	test_separated_families();
	test_json_decode_checked_failures();
	test_json_encode_checked_failures();
	return 0;
}
