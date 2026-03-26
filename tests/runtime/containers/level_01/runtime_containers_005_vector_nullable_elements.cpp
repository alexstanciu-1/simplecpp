#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::nullable<scpp::int_t>> values;
	values.append(scpp::nullable<scpp::int_t>(scpp::null));
	values.append(scpp::nullable<scpp::int_t>(scpp::int_t(9)));

	assert((values.at(0) == scpp::null).native_value() == true);
	assert(values.at(1).value().native_value() == 9);

	values.at(0) = scpp::nullable<scpp::int_t>(scpp::int_t(3));
	assert(values.at(0).value().native_value() == 3);
	return 0;
}
