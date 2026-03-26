#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	for (int i = 0; i < 20000; ++i) {
		auto first = scpp::value<runtime_test::lifetime_probe>(scpp::int_t(i));
		auto copy = first;
		auto moved = std::move(copy);
		assert(first->value.native_value() == i);
		assert(moved->value.native_value() == i);
		moved->value = scpp::int_t(i + 10);
		assert(first->value.native_value() == i);
		assert(moved->value.native_value() == i + 10);
	}
	runtime_test::assert_lifetime_balanced();
	assert(runtime_test::lifetime_probe::copies > 0);
	assert(runtime_test::lifetime_probe::moves > 0);
	return 0;
}
