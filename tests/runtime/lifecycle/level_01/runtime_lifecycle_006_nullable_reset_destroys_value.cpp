#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	{
		scpp::nullable<runtime_test::lifetime_probe> value(runtime_test::lifetime_probe(scpp::int_t(9)));
		assert(value.has_value().native_value() == true);
		assert(runtime_test::lifetime_probe::constructions >= 1);
		value.reset();
		assert(value.has_value().native_value() == false);
		assert(runtime_test::lifetime_probe::destructions >= 1);
	}
	assert(runtime_test::lifetime_probe::destructions == runtime_test::lifetime_probe::constructions);
	return 0;
}
