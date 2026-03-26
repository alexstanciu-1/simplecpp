#include "tests/runtime/runtime_test_common.hpp"

int main() {
	auto owner = scpp::shared<runtime_test::sample_object>(scpp::int_t(55));
	scpp::weak_p<runtime_test::sample_object> first(owner);
	scpp::weak_p<runtime_test::sample_object> second;
	second = first;

	assert(first.expired().native_value() == false);
	assert(second.expired().native_value() == false);
	assert(first.use_count() == 1U);
	assert(second.lock()->value.native_value() == 55);

	second.reset();
	assert((second == scpp::null).native_value() == true);

	owner.reset();
	assert(first.expired().native_value() == true);
	assert(first.lock().has_value().native_value() == false);
	return 0;
}
