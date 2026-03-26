#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::int_t> values = { scpp::int_t(2), scpp::int_t(4), scpp::int_t(6) };
	assert(values.size() == 3U);
	assert(values.at(0).native_value() == 2);
	assert(values.at(2).native_value() == 6);

	auto copy = values;
	copy.at(1) = scpp::int_t(9);
	assert(values.at(1).native_value() == 4);
	assert(copy.at(1).native_value() == 9);
	return 0;
}
