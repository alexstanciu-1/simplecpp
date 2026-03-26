#include "tests/runtime/runtime_test_common.hpp"

int main() {
	const scpp::int_t left(20);
	const scpp::int_t right(3);
	assert((left + right).native_value() == 23);
	assert((left - right).native_value() == 17);
	assert((left * right).native_value() == 60);
	assert((left / right).native_value() == 6);
	assert((left % right).native_value() == 2);
	assert((left > right).native_value() == true);
	assert((left < right).native_value() == false);

	scpp::int_t compound(10);
	compound += scpp::int_t(5);
	compound *= scpp::int_t(2);
	compound -= scpp::int_t(4);
	assert(compound.native_value() == 26);

	const scpp::float_t pi(3.5);
	const scpp::float_t delta(1.25);
	assert((pi + delta).native_value() == 4.75);
	assert((pi - delta).native_value() == 2.25);
	assert((pi > delta).native_value() == true);

	const scpp::bool_t truth(true);
	const scpp::bool_t lie(false);
	assert((truth == truth).native_value() == true);
	assert((truth != lie).native_value() == true);
	assert(truth.native_value() == true);
	assert(lie.native_value() == false);
	return 0;
}
