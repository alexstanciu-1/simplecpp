#include "tests/runtime/runtime_test_common.hpp"

int main() {
	auto first = scpp::shared<runtime_test::sample_object>(scpp::int_t(3));
	auto second = scpp::shared<runtime_test::sample_object>(scpp::int_t(7));

	assert(first.use_count() == 1U);
	first.swap(second);
	assert(first->value.native_value() == 7);
	assert(second->value.native_value() == 3);

	auto alias = first;
	assert(first.use_count() == 2U);
	assert(first.unique().native_value() == false);
	alias.reset();
	assert(first.use_count() == 1U);
	assert(first.unique().native_value() == true);

	first.reset();
	assert((first == scpp::null).native_value() == true);
	return 0;
}
