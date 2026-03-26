#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	{
		scpp::vector_t<runtime_test::lifetime_probe> values;
		values.append(runtime_test::lifetime_probe(scpp::int_t(1)));
		values.append(runtime_test::lifetime_probe(scpp::int_t(2)));
		assert(runtime_test::lifetime_probe::constructions >= 2);
		values.clear();
		assert(runtime_test::lifetime_probe::destructions >= 2);
	}
	assert(runtime_test::lifetime_probe::destructions == runtime_test::lifetime_probe::constructions);
	return 0;
}
