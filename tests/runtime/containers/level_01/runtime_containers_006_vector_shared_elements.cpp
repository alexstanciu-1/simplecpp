#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::shared_p<runtime_test::sample_object>> values;
	values.append(scpp::shared<runtime_test::sample_object>(scpp::int_t(1)));
	values.append(scpp::shared<runtime_test::sample_object>(scpp::int_t(2)));

	assert(values.size() == 2U);
	values.at(0)->value = scpp::int_t(11);
	assert(values.at(0)->value.native_value() == 11);

	auto alias = values.at(0);
	assert(alias.use_count() == 2U);
	return 0;
}
