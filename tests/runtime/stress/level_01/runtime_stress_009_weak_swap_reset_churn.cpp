#include "tests/runtime/runtime_test_common.hpp"

int main() {
	for (int i = 0; i < 3000; ++i) {
		auto left_owner = scpp::shared<runtime_test::sample_object>(scpp::int_t(i));
		auto right_owner = scpp::shared<runtime_test::sample_object>(scpp::int_t(i + 1));
		auto left = scpp::weak(left_owner);
		auto right = scpp::weak(right_owner);
		left.swap(right);
		assert(left.lock()->value.native_value() == i + 1);
		right.reset();
		assert((right == scpp::null).native_value() == true);
	}
	return 0;
}
