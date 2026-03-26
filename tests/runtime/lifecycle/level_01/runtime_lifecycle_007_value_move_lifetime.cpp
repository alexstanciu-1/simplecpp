#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	{
		auto first = scpp::value<runtime_test::lifetime_probe>(scpp::int_t(4));
		assert(runtime_test::lifetime_probe::constructions == 1);
		{
			auto second = std::move(first);
			assert(runtime_test::lifetime_probe::constructions == 2);
			assert(second->value.native_value() == 4);
		}
		assert(runtime_test::lifetime_probe::destructions == 1);
	}
	assert(runtime_test::lifetime_probe::destructions == runtime_test::lifetime_probe::constructions);
	return 0;
}
