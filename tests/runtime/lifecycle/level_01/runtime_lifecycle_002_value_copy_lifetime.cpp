#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	{
		auto value = scpp::value<runtime_test::lifetime_probe>(scpp::int_t(4));
		assert(runtime_test::lifetime_probe::constructions == 1);
		{
			auto copy = value;
			assert(runtime_test::lifetime_probe::constructions == 2);
			copy->value = scpp::int_t(8);
			assert(value->value.native_value() == 4);
			assert(copy->value.native_value() == 8);
		}
		assert(runtime_test::lifetime_probe::destructions == 1);
	}
	assert(runtime_test::lifetime_probe::constructions == 2);
	assert(runtime_test::lifetime_probe::destructions == 2);
	return 0;
}
