#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	for (int i = 0; i < 20000; ++i) {
		auto owner = scpp::shared<runtime_test::lifetime_probe>(scpp::int_t(i));
		assert(owner.debug_use_count() == 1);
		{
			auto copy_a = owner;
			auto copy_b = copy_a;
			assert(owner.debug_use_count() == 3);
			copy_a->value = scpp::int_t(i + 1);
			assert(copy_b->value.native_value() == i + 1);
			copy_b.reset();
			assert(owner.debug_use_count() == 2);
		}
		assert(owner.debug_use_count() == 1);
		owner.reset();
	}
	runtime_test::assert_lifetime_balanced();
	return 0;
}
