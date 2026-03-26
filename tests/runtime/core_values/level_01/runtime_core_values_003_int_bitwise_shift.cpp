#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::int_t value(12);
	assert((~scpp::int_t(0)).native_value() == -1);
	assert((value & scpp::int_t(10)).native_value() == 8);
	assert((value | scpp::int_t(3)).native_value() == 15);
	assert((value ^ scpp::int_t(5)).native_value() == 9);
	assert((value << scpp::int_t(1)).native_value() == 24);
	assert((value >> scpp::int_t(2)).native_value() == 3);

	value |= scpp::int_t(1);
	value &= scpp::int_t(13);
	value ^= scpp::int_t(4);
	value <<= scpp::int_t(1);
	value >>= scpp::int_t(2);
	assert(value.native_value() == 4);
	return 0;
}
