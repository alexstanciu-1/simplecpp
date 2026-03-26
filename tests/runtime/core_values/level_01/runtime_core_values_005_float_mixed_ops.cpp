#include "tests/runtime/runtime_test_common.hpp"

int main() {
	const scpp::float_t left(2.5);
	const scpp::float_t right(0.5);
	const scpp::int_t count(2);

	assert((+left).native_value() == 2.5);
	assert((-right).native_value() == -0.5);
	assert((left + right).native_value() == 3.0);
	assert((left - right).native_value() == 2.0);
	assert((left * right).native_value() == 1.25);
	assert((left / right).native_value() == 5.0);
	assert((count + left).native_value() == 4.5);
	assert((left + count).native_value() == 4.5);
	assert((count * left).native_value() == 5.0);
	assert((left > count).native_value() == true);

	scpp::float_t value(1.5);
	value += scpp::float_t(0.5);
	value -= scpp::int_t(1);
	value *= scpp::int_t(4);
	value /= scpp::float_t(2.0);
	assert(value.native_value() == 2.0);
	return 0;
}
