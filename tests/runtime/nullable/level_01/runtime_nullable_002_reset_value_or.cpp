#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::nullable<scpp::int_t> value(scpp::int_t(7));
	assert(value.has_value().native_value() == true);
	assert(value.value_or(scpp::int_t(99)).native_value() == 7);

	value.reset();
	assert((value == scpp::null).native_value() == true);
	assert(value.value_or(scpp::int_t(99)).native_value() == 99);

	value = scpp::nullable<scpp::int_t>(scpp::int_t(11));
	value.reset(scpp::nullopt);
	assert((value == scpp::nullopt).native_value() == true);
	return 0;
}
