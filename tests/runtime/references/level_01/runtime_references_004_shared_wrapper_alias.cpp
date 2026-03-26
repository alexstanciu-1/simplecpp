#include "tests/runtime/runtime_test_common.hpp"

int main() {
	auto shared = scpp::shared<runtime_test::sample_object>(scpp::int_t(4));
	auto &alias = shared;
	alias->value = scpp::int_t(17);
	assert(shared->value.native_value() == 17);

	auto second = alias;
	assert(second.use_count() == 2U);
	assert(second->value.native_value() == 17);
	return 0;
}
