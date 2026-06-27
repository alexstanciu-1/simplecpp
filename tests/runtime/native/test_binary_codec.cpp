#include "test_common.hpp"

#include "scpp/binary.hpp"

#include <cassert>
#include <cstdint>
#include <string>

namespace {

void test_integer_roundtrip_and_endianness() {
	scpp::binary::writer writer;
	writer.write_uint8(0xABU);
	writer.write_uint16(0x1234U);
	writer.write_uint32(0x12345678U);
	writer.write_uint64(0x0123456789ABCDEFULL);

	const auto bytes = writer.native_bytes();
	const std::string expected{
		static_cast<char>(0xABU),
		static_cast<char>(0x34U), static_cast<char>(0x12U),
		static_cast<char>(0x78U), static_cast<char>(0x56U), static_cast<char>(0x34U), static_cast<char>(0x12U),
		static_cast<char>(0xEFU), static_cast<char>(0xCDU), static_cast<char>(0xABU), static_cast<char>(0x89U),
		static_cast<char>(0x67U), static_cast<char>(0x45U), static_cast<char>(0x23U), static_cast<char>(0x01U),
	};
	assert(bytes == expected);

	scpp::binary::reader reader(bytes);
	assert(reader.read_uint8() == 0xABU);
	assert(reader.read_uint16() == 0x1234U);
	assert(reader.read_uint32() == 0x12345678U);
	assert(reader.read_uint64() == 0x0123456789ABCDEFULL);
	assert(reader.done());
}

void test_length_prefixed_string_roundtrip() {
	scpp::binary::writer writer;
	writer.write_string(scpp::string_t("hello"));
	writer.write_string(scpp::string_t(std::string("a\0b", 3)));

	scpp::binary::reader reader(writer.native_bytes());
	assert(reader.read_string().native_value() == "hello");
	assert(reader.read_string().native_value() == std::string("a\0b", 3));
	assert(reader.done());
}

void test_byte_span_write() {
	scpp::string_t source_text("abcdef");
	auto buffer = scpp::source::source_buffer_take(source_text);
	const auto span = scpp::source::source_buffer_span(buffer, scpp::int_t<>(2), scpp::int_t<>(3));

	scpp::binary::writer writer;
	writer.write_uint8(1U);
	writer.write_byte_span(span);
	writer.write_uint8(2U);

	scpp::binary::reader reader(writer.native_bytes());
	assert(reader.read_uint8() == 1U);
	assert(reader.read_bytes(3U) == "cde");
	assert(reader.read_uint8() == 2U);
	assert(reader.done());
}

void test_truncated_input_diagnostics() {
	scpp_test::expect_throw<scpp::runtime_error>([]() {
		scpp::binary::reader reader(std::string("\x01", 1));
		static_cast<void>(reader.read_uint16());
	});

	scpp_test::expect_throw<scpp::runtime_error>([]() {
		scpp::binary::reader reader(std::string("\x05\0\0\0hi", 6));
		static_cast<void>(reader.read_string());
	});
}

} // namespace

int main() {
	test_integer_roundtrip_and_endianness();
	test_length_prefixed_string_roundtrip();
	test_byte_span_write();
	test_truncated_input_diagnostics();
	return 0;
}
