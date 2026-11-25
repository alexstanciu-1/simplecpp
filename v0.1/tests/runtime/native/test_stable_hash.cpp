#include "test_common.hpp"

#include "scpp/runtime.hpp"

#include <cassert>
#include <cstdint>

namespace {

void test_known_string_hashes() {
	assert(scpp::stable_hash::string_u64(scpp::string_t("")) == 14695981039346656037ULL);
	assert(scpp::stable_hash::string_hex(scpp::string_t("")).native_value() == "cbf29ce484222325");

	assert(scpp::stable_hash::string_u64(scpp::string_t("abc")) == 16654208175385433931ULL);
	assert(scpp::stable_hash::string_hex(scpp::string_t("abc")).native_value() == "e71fa2190541574b");

	assert(scpp::stable_hash::string_u64(scpp::string_t("aă")) == 16495667394721844769ULL);
	assert(scpp::stable_hash::string_hex(scpp::string_t("aă")).native_value() == "e4ec621903638e21");
}

void test_byte_span_hash_matches_string_hash() {
	scpp::string_t source_text("abcdef");
	auto buffer = scpp::source::source_buffer_take(source_text);
	const auto span = scpp::source::source_buffer_span(buffer, scpp::int_t<>(1), scpp::int_t<>(3));

	assert(scpp::source::hash_bytes(span).native_value() == "003f3219133dae62");
	assert(scpp::source::stable_hash_bytes_u64(span).native_value() == 17788006811151970ULL);
	assert(scpp::source::stable_hash_bytes_u64(span).native_value()
		== scpp::stable_hash::string_u64(scpp::string_t("bcd")));
}

} // namespace

int main() {
	test_known_string_hashes();
	test_byte_span_hash_matches_string_hash();
	return 0;
}
