#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::nullable<scpp::shared_p<runtime_test::sample_object>>> values;
	values.append(scpp::nullable<scpp::shared_p<runtime_test::sample_object>>(scpp::shared<runtime_test::sample_object>(scpp::int_t(7))));
	values.append(scpp::nullable<scpp::shared_p<runtime_test::sample_object>>(scpp::null));
	assert(values.at(0).has_value().native_value() == true);
	assert(values.at(0).value()->value.native_value() == 7);
	assert(values.at(1).has_value().native_value() == false);
	return 0;
}
