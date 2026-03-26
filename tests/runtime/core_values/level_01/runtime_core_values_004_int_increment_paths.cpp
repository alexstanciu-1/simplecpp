#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::int_t value(5);
	assert((++value).native_value() == 6);
	assert(value.native_value() == 6);
	assert((value++).native_value() == 6);
	assert(value.native_value() == 7);
	assert((--value).native_value() == 6);
	assert(value.native_value() == 6);
	assert((value--).native_value() == 6);
	assert(value.native_value() == 5);
	return 0;
}
