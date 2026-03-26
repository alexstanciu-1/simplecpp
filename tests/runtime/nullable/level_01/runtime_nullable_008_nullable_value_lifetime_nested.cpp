#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	{
		scpp::nullable<scpp::value_p<runtime_test::lifetime_probe>> value(scpp::value<runtime_test::lifetime_probe>(scpp::int_t(4)));
		assert(runtime_test::lifetime_probe::alive == 1);
		assert(value.value()->value.native_value() == 4);
		value.reset();
		assert(runtime_test::lifetime_probe::alive == 0);
	}
	runtime_test::assert_lifetime_balanced();
	return 0;
}
