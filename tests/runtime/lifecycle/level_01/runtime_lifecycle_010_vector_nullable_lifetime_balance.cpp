#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	{
		scpp::vector_t<scpp::nullable<scpp::value_p<runtime_test::lifetime_probe>>> values;
		values.append(scpp::nullable<scpp::value_p<runtime_test::lifetime_probe>>(scpp::value<runtime_test::lifetime_probe>(scpp::int_t(1))));
		values.append(scpp::nullable<scpp::value_p<runtime_test::lifetime_probe>>(scpp::value<runtime_test::lifetime_probe>(scpp::int_t(2))));
		assert(runtime_test::lifetime_probe::alive == 2);
		values.clear();
		assert(runtime_test::lifetime_probe::alive == 0);
	}
	runtime_test::assert_lifetime_balanced();
	return 0;
}
