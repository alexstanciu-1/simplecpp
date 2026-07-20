#include "test_common.hpp"

#include "scpp/source.hpp"
#include "scpp/str.hpp"

#include <cassert>

namespace {

void test_append_span_and_reuse() {
	auto builder = scpp::str::text_builder_create();
	scpp::str::text_builder_reserve_bytes(builder, scpp::int_t<>(32));
	assert(scpp::str::text_builder_capacity_bytes(builder).native_value() >= 32);

	scpp::str::text_builder_append_string(builder, scpp::string_t("hello"));
	scpp::str::text_builder_append_string(builder, scpp::string_t("-"));
	scpp::str::text_builder_append_int(builder, scpp::int_t<>(42));
	scpp::str::text_builder_append_string(builder, scpp::string_t("-"));
	scpp::str::text_builder_append_bool(builder, scpp::bool_t(true));
	scpp::str::text_builder_append_bool(builder, scpp::bool_t(false));

	scpp::string_t source_text("xxspanzz");
	auto buffer = scpp::source::source_buffer_take(source_text);
	const auto span = scpp::source::source_buffer_span(buffer, scpp::int_t<>(2), scpp::int_t<>(4));
	scpp::str::text_builder_append_string(builder, scpp::string_t("-"));
	scpp::str::text_builder_append_byte_span(builder, span);

	assert(scpp::str::text_builder_byte_len(builder).native_value() == 15);
	assert(scpp::str::text_builder_to_string(builder).native_value() == "hello-42-1-span");

	assert(scpp::str::text_builder_take_string(builder).native_value() == "hello-42-1-span");
	assert(scpp::str::text_builder_byte_len(builder).native_value() == 0);

	scpp::str::text_builder_append_string(builder, scpp::string_t("again"));
	assert(scpp::str::text_builder_to_string(builder).native_value() == "again");

	scpp::str::text_builder_clear(builder);
	assert(scpp::str::text_builder_byte_len(builder).native_value() == 0);
}

void test_negative_reserve_rejected() {
	auto builder = scpp::str::text_builder_create();
	scpp_test::expect_throw<scpp::ValueError>([&]() {
		scpp::str::text_builder_reserve_bytes(builder, scpp::int_t<>(-1));
	});
}

} // namespace

int main() {
	test_append_span_and_reuse();
	test_negative_reserve_rejected();
	return 0;
}
