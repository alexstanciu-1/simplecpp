#include "tests/runtime/runtime_test_common.hpp"

int main() {
	assert(scpp::cast<scpp::bool_t>(scpp::int_t(0)).native_value() == false);
	assert(scpp::cast<scpp::bool_t>(scpp::int_t(7)).native_value() == true);
	assert(scpp::cast<scpp::bool_t>(scpp::float_t(0.0)).native_value() == false);
	assert(scpp::cast<scpp::bool_t>(scpp::float_t(-1.25)).native_value() == true);
	assert(scpp::cast<scpp::int_t>(scpp::float_t(3.75)).native_value() == 3);
	assert(scpp::cast<bool>(scpp::bool_t(true)) == true);
	assert(scpp::cast<scpp::string_t>(scpp::int_t(42)).native_value() == "42");
	assert(scpp::cast<scpp::string_t>(scpp::bool_t(true)).native_value() == "1");
	return 0;
}
