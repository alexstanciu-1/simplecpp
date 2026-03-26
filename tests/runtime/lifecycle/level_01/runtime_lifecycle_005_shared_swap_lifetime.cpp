#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	{
		auto first = scpp::shared<runtime_test::lifetime_probe>(scpp::int_t(1));
		auto second = scpp::shared<runtime_test::lifetime_probe>(scpp::int_t(2));
		assert(runtime_test::lifetime_probe::constructions == 2);
		first.swap(second);
		assert(first->value.native_value() == 2);
		assert(second->value.native_value() == 1);
	}
	assert(runtime_test::lifetime_probe::constructions == 2);
	assert(runtime_test::lifetime_probe::destructions == 2);
	return 0;
}
