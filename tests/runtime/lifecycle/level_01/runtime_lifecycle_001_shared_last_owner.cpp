#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	{
		auto first = scpp::shared<runtime_test::lifetime_probe>(scpp::int_t(1));
		{
			auto second = first;
			assert(runtime_test::lifetime_probe::constructions == 1);
			assert(runtime_test::lifetime_probe::destructions == 0);
			second->value = scpp::int_t(9);
			assert(first->value.native_value() == 9);
		}
		assert(runtime_test::lifetime_probe::destructions == 0);
	}
	assert(runtime_test::lifetime_probe::constructions == 1);
	assert(runtime_test::lifetime_probe::destructions == 1);
	return 0;
}
