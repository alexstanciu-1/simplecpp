#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	{
		auto value = scpp::unique<runtime_test::lifetime_probe>(scpp::int_t(1));
		assert(runtime_test::lifetime_probe::constructions == 1);
		value.reset(new runtime_test::lifetime_probe(scpp::int_t(2)));
		assert(runtime_test::lifetime_probe::constructions == 2);
		assert(runtime_test::lifetime_probe::destructions == 1);
		assert(value->value.native_value() == 2);
	}
	assert(runtime_test::lifetime_probe::destructions == 2);
	return 0;
}
