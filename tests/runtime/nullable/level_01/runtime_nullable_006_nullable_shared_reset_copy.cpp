#include "tests/runtime/runtime_test_common.hpp"

int main() {
	auto owner = scpp::shared<runtime_test::sample_object>(scpp::int_t(9));
	scpp::nullable<scpp::shared_p<runtime_test::sample_object>> first(owner);
	auto second = first;
	assert(first.value().use_count() == 3U);
	first.reset();
	assert(first.has_value().native_value() == false);
	assert(second.value()->value.native_value() == 9);
	assert(owner.use_count() == 2U);
	return 0;
}
