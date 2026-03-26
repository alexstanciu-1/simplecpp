#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	for (int i = 0; i < 15000; ++i) {
		auto owner = scpp::unique<runtime_test::lifetime_probe>(scpp::int_t(i));
		assert(owner->value.native_value() == i);
		auto moved = std::move(owner);
		assert((owner == scpp::null).native_value() == true);
		assert(moved->value.native_value() == i);
		moved.reset(new runtime_test::lifetime_probe(scpp::int_t(i + 1)));
		assert(moved->value.native_value() == i + 1);
		auto raw = moved.release();
		assert(moved.has_value().native_value() == false);
		delete raw;
	}
	runtime_test::assert_lifetime_balanced();
	return 0;
}
