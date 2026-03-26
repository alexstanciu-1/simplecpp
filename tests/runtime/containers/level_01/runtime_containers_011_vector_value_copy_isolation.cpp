#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::value_p<runtime_test::sample_object>> values;
	values.append(scpp::value<runtime_test::sample_object>(scpp::int_t(5)));
	values.append(values.at(0));
	values.at(0)->value = scpp::int_t(55);
	assert(values.at(0)->value.native_value() == 55);
	assert(values.at(1)->value.native_value() == 5);
	return 0;
}
