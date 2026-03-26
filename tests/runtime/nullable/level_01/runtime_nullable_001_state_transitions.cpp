#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::nullable<scpp::int_t> value;
	assert((value == scpp::null).native_value() == true);
	assert(value.has_value().native_value() == false);
	assert(value.value_or(scpp::int_t(99)).native_value() == 99);

	value = scpp::nullable<scpp::int_t>(scpp::int_t(7));
	assert((value != scpp::null).native_value() == true);
	assert(value.has_value().native_value() == true);
	assert(value.value().native_value() == 7);
	assert(value.value_or(scpp::int_t(99)).native_value() == 7);

	const scpp::nullable<scpp::int_t> same(scpp::int_t(7));
	const scpp::nullable<scpp::int_t> different(scpp::int_t(8));
	assert((value == same).native_value() == true);
	assert((value != different).native_value() == true);

	value.reset();
	assert((value == scpp::nullopt).native_value() == true);
	assert(value.has_value().native_value() == false);
	return 0;
}
