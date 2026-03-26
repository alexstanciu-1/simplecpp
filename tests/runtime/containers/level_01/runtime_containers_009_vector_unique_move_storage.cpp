#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::unique_p<runtime_test::sample_object>> values;
	auto first = scpp::unique<runtime_test::sample_object>(scpp::int_t(10));
	values.append(std::move(first));
	assert((first == scpp::null).native_value() == true);
	assert(values.size() == 1U);
	assert(values.at(0)->value.native_value() == 10);

	values.append(scpp::unique<runtime_test::sample_object>(scpp::int_t(20)));
	assert(values.at(1)->value.native_value() == 20);
	return 0;
}
