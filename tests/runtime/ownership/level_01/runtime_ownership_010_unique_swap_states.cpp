#include "tests/runtime/runtime_test_common.hpp"

int main() {
	auto first = scpp::unique<runtime_test::sample_object>(scpp::int_t(1));
	scpp::unique_p<runtime_test::sample_object> second;
	first.swap(second);
	assert((first == scpp::null).native_value() == true);
	assert(second->value.native_value() == 1);
	return 0;
}
