#include "tests/runtime/runtime_test_common.hpp"

int main() {
	auto owner_a = scpp::shared<runtime_test::sample_object>(scpp::int_t(1));
	auto owner_b = scpp::shared<runtime_test::sample_object>(scpp::int_t(2));
	auto first = scpp::weak(owner_a);
	auto second = scpp::weak(owner_b);
	first.swap(second);
	assert(first.lock()->value.native_value() == 2);
	assert(second.lock()->value.native_value() == 1);
	second.reset();
	assert((second == scpp::null).native_value() == true);
	return 0;
}
