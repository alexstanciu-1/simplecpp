#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::shared_p<runtime_test::sample_object>> first;
	first.append(scpp::shared<runtime_test::sample_object>(scpp::int_t(12)));
	auto second = first;
	assert(first.at(0).use_count() == 2U);
	second.at(0)->value = scpp::int_t(21);
	assert(first.at(0)->value.native_value() == 21);
	return 0;
}
