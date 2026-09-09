#include "test_common.hpp"

#include "scpp/str.hpp"

#include <cassert>
#include <stdexcept>

namespace {

void test_append_and_reuse() {
	auto builder = scpp::str::string_parts_builder_create();
	scpp::str::string_parts_builder_reserve(builder, scpp::int_t<>(8));
	assert(scpp::str::string_parts_builder_capacity(builder).native_value() >= 8);

	scpp::str::string_parts_builder_append_string(builder, scpp::string_t("hello"));
	scpp::str::string_parts_builder_append_string(builder, scpp::string_t("-"));
	scpp::str::string_parts_builder_append_int(builder, scpp::int_t<>(42));
	scpp::str::string_parts_builder_append_string(builder, scpp::string_t("-"));
	scpp::str::string_parts_builder_append_bool(builder, scpp::bool_t(true));
	scpp::str::string_parts_builder_append_bool(builder, scpp::bool_t(false));

	assert(scpp::str::string_parts_builder_count(builder).native_value() == 6);
	assert(scpp::str::string_parts_builder_byte_len(builder).native_value() == 10);
	assert(scpp::str::string_parts_builder_to_string(builder).native_value() == "hello-42-1");

	scpp::str::string_parts_builder_clear(builder);
	assert(scpp::str::string_parts_builder_count(builder).native_value() == 0);
	assert(scpp::str::string_parts_builder_byte_len(builder).native_value() == 0);

	scpp::str::string_parts_builder_append_string(builder, scpp::string_t("again"));
	assert(scpp::str::string_parts_builder_to_string(builder).native_value() == "again");
}

void test_negative_reserve_rejected() {
	auto builder = scpp::str::string_parts_builder_create();
	scpp_test::expect_throw<scpp::ValueError>([&]() {
		scpp::str::string_parts_builder_reserve(builder, scpp::int_t<>(-1));
	});
}

} // namespace

int main() {
	test_append_and_reuse();
	test_negative_reserve_rejected();
	return 0;
}
