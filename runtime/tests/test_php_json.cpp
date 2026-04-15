#include "test_common.hpp"

#include "lang/php/php_json.hpp"
#include "scpp/runtime.hpp"

#include <cmath>
#include <stdexcept>
#include <string>

namespace {

static void test_json_decode_scalar_values() {
	const auto null_value = scpp::php::json_decode(scpp::string_t("null"));
	assert(null_value.kind() == scpp::mixed_t::kind_t::null_v);

	const auto bool_value = scpp::php::json_decode(scpp::string_t("true"));
	assert(bool_value.kind() == scpp::mixed_t::kind_t::bool_v);
	assert(bool_value.get_bool().native_value());

	const auto int_value = scpp::php::json_decode(scpp::string_t("123"));
	assert(int_value.kind() == scpp::mixed_t::kind_t::int_v);
	assert(int_value.get_int().native_value() == 123);

	const auto float_value = scpp::php::json_decode(scpp::string_t("1.5e2"));
	assert(float_value.kind() == scpp::mixed_t::kind_t::float_v);
	assert(float_value.get_float().native_value() == 150.0);

	const auto string_value = scpp::php::json_decode(scpp::string_t("\"hello\""));
	assert(string_value.kind() == scpp::mixed_t::kind_t::string_v);
	assert(string_value.get_string().native_value() == "hello");
}

static void test_json_decode_array_and_object_shapes() {
	const auto array_value = scpp::php::json_decode(scpp::string_t("[1,2,3]"));
	assert(array_value.kind() == scpp::mixed_t::kind_t::dynamic_v);
	assert(array_value.get_hash().is_packed().native_value());
	assert(array_value.get_hash().size() == 3);
	assert(array_value.get_hash().at(scpp::int_t(0)).get_int().native_value() == 1);

	const auto object_value = scpp::php::json_decode(scpp::string_t("{\"id\":10,\"name\":\"Alex\"}"));
	assert(object_value.kind() == scpp::mixed_t::kind_t::dynamic_v);
	assert(!object_value.get_hash().is_packed().native_value());
	assert(object_value.get_hash().at(scpp::string_t("id")).get_int().native_value() == 10);
	assert(object_value.get_hash().at(scpp::string_t("name")).get_string().native_value() == "Alex");
}

static void test_json_decode_nested_shared_model() {
	const auto value = scpp::php::json_decode(scpp::string_t("[{\"id\":10},{\"id\":20}]"));
	assert(value.kind() == scpp::mixed_t::kind_t::dynamic_v);

	const auto &outer = value.get_hash();
	assert(outer.is_packed().native_value());
	assert(outer.at(scpp::int_t(0)).kind() == scpp::mixed_t::kind_t::dynamic_v);
	assert(!outer.at(scpp::int_t(0)).get_hash().is_packed().native_value());
	assert(outer.at(scpp::int_t(0)).get_hash().at(scpp::string_t("id")).get_int().native_value() == 10);
}

static void test_json_decode_unicode_and_escapes() {
	const auto value = scpp::php::json_decode(scpp::string_t("\"A\\n\\u20AC\\uD83D\\uDE00\""));
	assert(value.kind() == scpp::mixed_t::kind_t::string_v);
	assert(value.get_string().native_value() == std::string("A\n€😀"));
}

static void test_json_encode_shapes() {
	const auto decoded_array = scpp::php::json_decode(scpp::string_t("[1,true,null,\"x\"]"));
	assert(scpp::php::json_encode(decoded_array).native_value() == "[1,true,null,\"x\"]");

	const auto decoded_object = scpp::php::json_decode(scpp::string_t("{\"id\":10,\"name\":\"Alex\"}"));
	assert(scpp::php::json_encode(decoded_object).native_value() == "{\"id\":10,\"name\":\"Alex\"}");
	assert(scpp::php::json_encode(decoded_object.get_hash()).native_value() == "{\"id\":10,\"name\":\"Alex\"}");
	assert(decoded_object.dynamic_if() != nullptr);

	auto shared_table = scpp::shared<scpp::hash_t<scpp::mixed_t>>();
	shared_table->append(scpp::mixed_t(scpp::int_t(7)));
	shared_table->append(scpp::mixed_t(scpp::string_t("ok")));
	assert(scpp::php::json_encode(shared_table).native_value() == "[7,\"ok\"]");
}

static void test_json_encode_key_and_error_contracts() {
	scpp::hash_t<scpp::mixed_t> object_like;
	object_like.set(scpp::string_t("a"), scpp::mixed_t(scpp::int_t(1)));
	object_like.set(scpp::int_t(42), scpp::mixed_t(scpp::int_t(2)));
	assert(scpp::php::json_encode(object_like).native_value() == "{\"a\":1,\"42\":2}");

	scpp_test::expect_throw<std::runtime_error>([]() {
		static_cast<void>(scpp::php::json_decode(scpp::string_t("{\"x\":1 trailing")));
	});

	scpp_test::expect_throw<std::runtime_error>([]() {
		static_cast<void>(scpp::php::json_encode(scpp::mixed_t(scpp::float_t(std::numeric_limits<double>::infinity()))));
	});
}

} // namespace

int main() {
	test_json_decode_scalar_values();
	test_json_decode_array_and_object_shapes();
	test_json_decode_nested_shared_model();
	test_json_decode_unicode_and_escapes();
	test_json_encode_shapes();
	test_json_encode_key_and_error_contracts();
	return 0;
}
