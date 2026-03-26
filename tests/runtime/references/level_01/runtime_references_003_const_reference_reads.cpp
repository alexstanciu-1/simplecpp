#include "tests/runtime/runtime_test_common.hpp"

int main() {
	const scpp::string_t text("sample");
	const scpp::string_t &text_ref = text;
	assert(text_ref.native_value() == "sample");
	assert(text_ref.size() == 6U);

	const auto value = scpp::value<runtime_test::sample_object>(scpp::int_t(9));
	const auto &native = value.get();
	assert(native.value.native_value() == 9);
	return 0;
}
