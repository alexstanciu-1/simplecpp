#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	{
		auto value = scpp::unique<runtime_test::lifetime_probe>(scpp::int_t(5));
		assert(runtime_test::lifetime_probe::constructions == 1);
		auto *raw = value.release();
		assert(runtime_test::lifetime_probe::destructions == 0);
		delete raw;
	}
	assert(runtime_test::lifetime_probe::constructions == 1);
	assert(runtime_test::lifetime_probe::destructions == 1);
	return 0;
}
