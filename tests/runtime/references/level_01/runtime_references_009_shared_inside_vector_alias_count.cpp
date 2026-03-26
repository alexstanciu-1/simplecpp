#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::shared_p<runtime_test::sample_object>> values;
	values.append(scpp::shared<runtime_test::sample_object>(scpp::int_t(3)));
	auto &slot = values.at(0);
	auto copy = slot;
	assert(slot.use_count() == 2U);
	copy->value = scpp::int_t(30);
	assert(values.at(0)->value.native_value() == 30);
	return 0;
}
