#include "tests/runtime/runtime_test_common.hpp"

int main() {
	for (int i = 0; i < 3000; ++i) {
		scpp::nullable<scpp::shared_p<runtime_test::sample_object>> value(scpp::shared<runtime_test::sample_object>(scpp::int_t(i)));
		assert(value.value()->value.native_value() == i);
		value.reset();
		assert(value.has_value().native_value() == false);
	}
	return 0;
}
