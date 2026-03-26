#include "tests/runtime/runtime_test_common.hpp"

int main() {
	auto value = scpp::shared<runtime_test::sample_object>(scpp::int_t(14));
	value = value;
	assert(value.use_count() == 1U);
	assert(value->value.native_value() == 14);
	value = scpp::null;
	assert((value == scpp::null).native_value() == true);
	return 0;
}
