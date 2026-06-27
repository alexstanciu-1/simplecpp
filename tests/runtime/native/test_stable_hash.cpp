#include "test_common.hpp"

#include "scpp/runtime.hpp"

#include <cassert>
#include <cstdint>

namespace {

void test_known_string_hashes() {
	assert(scpp::stable_hash::string_u64(scpp::string_t("")) == 2108955521386288165ULL);
	assert(scpp::stable_hash::string_hex(scpp::string_t("")).native_value() == "1d4483df7b541425");

	assert(scpp::stable_hash::string_u64(scpp::string_t("abc")) == 15257871626132560565ULL);
	assert(scpp::stable_hash::string_hex(scpp::string_t("abc")).native_value() == "d3bed96780249ab5");

	assert(scpp::stable_hash::string_u64(scpp::string_t("aă")) == 10372154157619366995ULL);
	assert(scpp::stable_hash::string_hex(scpp::string_t("aă")).native_value() == "8ff14b40987e4c53");
}

void test_byte_span_hash_matches_string_hash() {
	scpp::string_t source_text("abcdef");
	auto buffer = scpp::source::source_buffer_take(source_text);
	const auto span = scpp::source::source_buffer_span(buffer, scpp::int_t<>(1), scpp::int_t<>(3));

	assert(scpp::source::hash_bytes(span).native_value() == "bbd3bfcaaf9c53d5");
	assert(scpp::source::stable_hash_bytes_u64(span).native_value() == 13534372182429029333ULL);
	assert(scpp::source::stable_hash_bytes_u64(span).native_value()
		== scpp::stable_hash::string_u64(scpp::string_t("bcd")));
}

} // namespace

int main() {
	test_known_string_hashes();
	test_byte_span_hash_matches_string_hash();
	return 0;
}
