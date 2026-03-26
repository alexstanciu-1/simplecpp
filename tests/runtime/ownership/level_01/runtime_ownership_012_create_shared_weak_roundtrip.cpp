#include "tests/runtime/runtime_test_common.hpp"

int main() {
	auto owner = scpp::create<runtime_test::sample_object>(scpp::int_t(27));
	auto weak = scpp::weak(owner);
	auto again = weak.lock();
	assert(again->value.native_value() == 27);
	assert(owner.use_count() == 2U);
	again.reset();
	owner.reset();
	assert(weak.expired().native_value() == true);
	return 0;
}
