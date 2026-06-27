#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::string_t text("abcdef");
	auto buffer = scpp::source::source_buffer_take(text);
	assert(text.native_value().empty());
	assert(scpp::source::source_buffer_byte_len(buffer).native_value() == 6U);
	assert(scpp::source::source_buffer_byte_at(buffer, scpp::int_t<>(1)).native_value() == static_cast<std::uint8_t>('b'));
	assert(scpp::source::source_buffer_slice(buffer, scpp::int_t<>(2), scpp::int_t<>(3)).native_value() == "cde");

	auto span = scpp::source::source_buffer_span(buffer, scpp::int_t<>(1), scpp::int_t<>(3));
	assert(scpp::source::byte_span_len(span).native_value() == 3U);
	assert(scpp::source::byte_span_at(span, scpp::int_t<>(2)).native_value() == static_cast<std::uint8_t>('d'));
	assert(scpp::source::byte_span_to_string(span).native_value() == "bcd");

	const auto released = scpp::source::source_buffer_release(buffer);
	assert(released.native_value() == "abcdef");
	assert(scpp::source::source_buffer_byte_len(buffer).native_value() == 0U);
	runtime_test::expect_throw<scpp::runtime_error>([&span]() {
		static_cast<void>(scpp::source::byte_span_len(span));
	});
	return 0;
}
