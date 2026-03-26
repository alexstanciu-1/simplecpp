#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	scpp::nullable<runtime_test::lifetime_probe> value = scpp::null;
	for (int i = 0; i < 20000; ++i) {
		assert((value == scpp::null).native_value() == true);
		value = runtime_test::lifetime_probe(scpp::int_t(i));
		assert(value.has_value().native_value() == true);
		assert(value.value().value.native_value() == i);
		auto copy = value;
		assert(copy.value().value.native_value() == i);
		value.reset();
		assert((value == scpp::null).native_value() == true);
	}
	assert(runtime_test::lifetime_probe::alive == 0);
	assert(runtime_test::lifetime_probe::destructions == runtime_test::lifetime_probe::constructions);
	return 0;
}
