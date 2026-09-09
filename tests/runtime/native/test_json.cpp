#include "test_common.hpp"

#include "modules/json/json.hpp"
#include "scpp/lang/php.hpp"

#include <cmath>
#include <string>

namespace {

static void test_json_decode_scalar_values() {
	const auto null_value = scpp::json::json_decode(scpp::string_t("null")).value();
	assert(null_value.kind() == scpp::mixed_t::kind_t::null_v);

	const auto bool_value = scpp::json::json_decode(scpp::string_t("true")).value();
	assert(bool_value.kind() == scpp::mixed_t::kind_t::bool_v);
	assert(bool_value.get_bool().native_value());

	const auto int_value = scpp::json::json_decode(scpp::string_t("123")).value();
	assert(int_value.kind() == scpp::mixed_t::kind_t::int_v);
	assert(int_value.get_int().native_value() == 123);

	const auto float_value = scpp::json::json_decode(scpp::string_t("1.5e2")).value();
	assert(float_value.kind() == scpp::mixed_t::kind_t::float_v);
	assert(float_value.get_float().native_value() == 150.0);

	const auto string_value = scpp::json::json_decode(scpp::string_t("\"hello\"")).value();
	assert(string_value.kind() == scpp::mixed_t::kind_t::string_v);
	assert(string_value.get_string().native_value() == "hello");
}

static void test_json_decode_array_and_object_shapes() {
	const auto array_value = scpp::json::json_decode(scpp::string_t("[1,2,3]")).value();
	assert(array_value.kind() == scpp::mixed_t::kind_t::dynamic_v);
	assert(array_value.get_hash().is_packed().native_value());
	assert(array_value.get_hash().size() == 3);
	assert(array_value.get_hash().at(scpp::int_t<>(0)).get_int().native_value() == 1);

	const auto object_value = scpp::json::json_decode(scpp::string_t("{\"id\":10,\"name\":\"Alex\"}")).value();
	assert(object_value.kind() == scpp::mixed_t::kind_t::dynamic_v);
	assert(!object_value.get_hash().is_packed().native_value());
	assert(object_value.get_hash().at(scpp::string_t("id")).get_int().native_value() == 10);
	assert(object_value.get_hash().at(scpp::string_t("name")).get_string().native_value() == "Alex");
}

static void test_json_decode_nested_shared_model() {
	const auto value = scpp::json::json_decode(scpp::string_t("[{\"id\":10},{\"id\":20}]")).value();
	assert(value.kind() == scpp::mixed_t::kind_t::dynamic_v);

	const auto &outer = value.get_hash();
	assert(outer.is_packed().native_value());
	assert(outer.at(scpp::int_t<>(0)).kind() == scpp::mixed_t::kind_t::dynamic_v);
	assert(!outer.at(scpp::int_t<>(0)).get_hash().is_packed().native_value());
	assert(outer.at(scpp::int_t<>(0)).get_hash().at(scpp::string_t("id")).get_int().native_value() == 10);
}

static void test_json_decode_unicode_and_escapes() {
	const auto value = scpp::json::json_decode(scpp::string_t("\"A\\n\\u20AC\\uD83D\\uDE00\"")).value();
	assert(value.kind() == scpp::mixed_t::kind_t::string_v);
	assert(value.get_string().native_value() == std::string("A\n€😀"));
}

static void test_json_encode_shapes() {
	const auto decoded_array = scpp::json::json_decode(scpp::string_t("[1,true,null,\"x\"]")).value();
	assert(scpp::json::json_encode(decoded_array).value().native_value() == "[1,true,null,\"x\"]");

	const auto decoded_object = scpp::json::json_decode(scpp::string_t("{\"id\":10,\"name\":\"Alex\"}")).value();
	assert(scpp::json::json_encode(decoded_object).value().native_value() == "{\"id\":10,\"name\":\"Alex\"}");
	assert(decoded_object.dynamic_if() != nullptr);

	auto shared_table = scpp::shared<scpp::hash_t<scpp::mixed_t>>();
	static_cast<void>(shared_table->append(scpp::mixed_t(scpp::int_t<>(7))));
	static_cast<void>(shared_table->append(scpp::mixed_t(scpp::string_t("ok"))));
	assert(scpp::json::json_encode(scpp::mixed_t(scpp::dynamic_box(scpp::dynamic_t<>(shared_table)))).value().native_value() == "[7,\"ok\"]");
}

static void test_json_encode_typed_values() {
	scpp::vector_t<scpp::int_t<>> items;
	items.append(scpp::int_t<>(1));
	items.append(scpp::int_t<>(2));
	items.append(scpp::int_t<>(3));
	assert(scpp::json::json_encode(items).value().native_value() == "[1,2,3]");

	scpp::hash_t<scpp::int_t<>> scores;
	scores.set(scpp::string_t("a"), scpp::int_t<>(1));
	scores.set(scpp::string_t("b"), scpp::int_t<>(2));
	assert(scpp::json::json_encode(scores).value().native_value() == "{\"a\":1,\"b\":2}");

	scpp::hash_t<scpp::vector_t<scpp::int_t<>>> matrix;
	matrix.set(scpp::string_t("row"), items);
	assert(scpp::json::json_encode(matrix).value().native_value() == "{\"row\":[1,2,3]}");

	scpp::nullable<scpp::int_t<>> empty;
	assert(scpp::json::json_encode(empty).value().native_value() == "null");
	scpp::nullable<scpp::int_t<>> present(scpp::int_t<>(7));
	assert(scpp::json::json_encode(present).value().native_value() == "7");
}

static void test_json_encode_key_contracts() {
	auto object_like = scpp::shared<scpp::hash_t<scpp::mixed_t>>();
	object_like->set(scpp::string_t("a"), scpp::mixed_t(scpp::int_t<>(1)));
	object_like->set(scpp::int_t<>(42), scpp::mixed_t(scpp::int_t<>(2)));
	assert(scpp::json::json_encode(scpp::mixed_t(scpp::dynamic_box(scpp::dynamic_t<>(object_like)))).value().native_value() == "{\"a\":1,\"42\":2}");

}

// Exercise repeated requests in one process, including failures after partial parsing.
static void test_json_decode_checked_failures() {
	const char *invalid[] = {
		"", "{", "[", "{\"x\":1 trailing", "[1,]", "{\"a\":}", "null trailing",
		"tru", "01", "-", "1.", "1e+", "1e9999", "\"unterminated",
		"\"\\x\"", "\"\\uZZZZ\"", "\"\\uD800\"", "\"\\uDC00\"",
		"\"\\uD800\\u0041\"", "\"\n\""
	};
	scpp::mixed_t output(scpp::string_t("unchanged"));
	scpp::error_t error;
	for (const char *text : invalid) {
		const auto parsed = scpp::json::decode(scpp::string_t(text));
		assert(parsed.has_error().native_value());
		assert(!scpp::php::take(output, error, parsed).native_value());
		assert(output.get_string().native_value() == "unchanged");
		assert(error.get_message().native_value().starts_with("json error at byte "));
	}
	assert(!scpp::php::take(output, error, scpp::php::json_decode(scpp::string_t("{"))).native_value());
	assert(error.get_message().native_value() == "json error at byte 1: expected string key");
	assert(scpp::php::take(output, error, scpp::json::decode(scpp::string_t("{\"name\":\"repaired\"}"))).native_value());
	assert(output.get_hash().at(scpp::string_t("name")).get_string().native_value() == "repaired");
	assert(scpp::php::take(output, error, scpp::json::decode(scpp::string_t("null"))).native_value());
	assert(output.kind() == scpp::mixed_t::kind_t::null_v);
	assert(scpp::php::take(output, error, scpp::json::decode(scpp::string_t("false"))).native_value());
	assert(output.kind() == scpp::mixed_t::kind_t::bool_v);
	assert(!output.get_bool().native_value());
}

static void test_json_encode_checked_failures() {
	scpp::string_t output("unchanged");
	scpp::error_t error;
	for (const double number : {
		std::numeric_limits<double>::infinity(),
		-std::numeric_limits<double>::infinity(),
		std::numeric_limits<double>::quiet_NaN()
	}) {
		assert(!scpp::php::take(output, error, scpp::json::encode(scpp::float_t(number))).native_value());
		assert(error.get_message().native_value() == "json_encode: non-finite float_t is not supported");
		assert(output.native_value() == "unchanged");
	}

	// The writer has already produced an array prefix when the nested value fails.
	scpp::vector_t<scpp::float_t> numbers;
	numbers.append(scpp::float_t(1.0));
	numbers.append(scpp::float_t(std::numeric_limits<double>::infinity()));
	assert(!scpp::php::take(output, error, scpp::json::encode(numbers)).native_value());
	assert(output.native_value() == "unchanged");

	auto owner = scpp::shared<scpp::hash_t<scpp::mixed_t>>();
	scpp::mixed_t weak{scpp::weak_p<scpp::hash_t<scpp::mixed_t>>(owner)};
	assert(!scpp::php::take(output, error, scpp::php::json_encode(weak)).native_value());
	assert(error.get_message().native_value() == "json_encode: weak tables are not supported");
	assert(output.native_value() == "unchanged");

	scpp::vector_t<scpp::mixed_t> nested;
	nested.append(scpp::mixed_t(scpp::int_t<>(1)));
	nested.append(weak);
	assert(!scpp::php::take(output, error, scpp::json::encode(nested)).native_value());
	assert(output.native_value() == "unchanged");

	assert(scpp::php::take(output, error, scpp::json::encode(scpp::string_t("repaired"))).native_value());
	assert(output.native_value() == "\"repaired\"");
	assert(scpp::php::take(output, error, scpp::json::encode(scpp::null_t{})).native_value());
	assert(output.native_value() == "null");
	assert(scpp::php::take(output, error, scpp::json::encode(scpp::bool_t(false))).native_value());
	assert(output.native_value() == "false");
}

static void test_json_alias_surface() {
	const auto value = scpp::json::decode(scpp::string_t("[1,2]")).value();
	assert(value.kind() == scpp::mixed_t::kind_t::dynamic_v);
	assert(scpp::json::encode(value).value().native_value() == "[1,2]");
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
	test_json_decode_checked_failures();
	test_json_encode_checked_failures();
	return 0;
}
