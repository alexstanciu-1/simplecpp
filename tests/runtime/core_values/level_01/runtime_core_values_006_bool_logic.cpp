#include "tests/runtime/runtime_test_common.hpp"

int main() {
	const scpp::bool_t truth(true);
	const scpp::bool_t lie(false);

	assert((!truth).native_value() == false);
	assert((!lie).native_value() == true);
	assert((truth && truth).native_value() == true);
	assert((truth && lie).native_value() == false);
	assert((truth || lie).native_value() == true);
	assert((lie || lie).native_value() == false);
	assert((truth == scpp::bool_t(true)).native_value() == true);
	assert((truth != lie).native_value() == true);
	return 0;
}
