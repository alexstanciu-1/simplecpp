#include "tests/runtime/runtime_test_common.hpp"

int main() {
	auto unique = scpp::unique<runtime_test::sample_object>(scpp::int_t(6));
	auto &alias = unique;
	alias->value = scpp::int_t(22);
	assert(unique->value.native_value() == 22);

	alias.reset();
	assert((unique == scpp::null).native_value() == true);
	return 0;
}
