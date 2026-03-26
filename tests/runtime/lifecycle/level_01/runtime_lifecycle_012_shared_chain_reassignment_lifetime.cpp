#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	{
		auto first = scpp::shared<runtime_test::lifetime_probe>(scpp::int_t(1));
		auto second = first;
		assert(runtime_test::lifetime_probe::alive == 1);
		second = scpp::shared<runtime_test::lifetime_probe>(scpp::int_t(2));
		assert(runtime_test::lifetime_probe::alive == 2);
		first.reset();
		assert(runtime_test::lifetime_probe::alive == 1);
	}
	runtime_test::assert_lifetime_balanced();
	return 0;
}
