#include "tests/runtime/runtime_test_common.hpp"

int main() {
	const scpp::nullable<scpp::int_t> left(scpp::int_t(5));
	const scpp::nullable<scpp::int_t> same(scpp::int_t(5));
	const scpp::nullable<scpp::int_t> different(scpp::int_t(6));
	const scpp::nullable<scpp::int_t> empty(scpp::null);

	assert((left == same).native_value() == true);
	assert((left != same).native_value() == false);
	assert((left == different).native_value() == false);
	assert((left != different).native_value() == true);
	assert((left == empty).native_value() == false);
	assert((empty != left).native_value() == true);
	assert((empty == scpp::null).native_value() == true);
	return 0;
}
