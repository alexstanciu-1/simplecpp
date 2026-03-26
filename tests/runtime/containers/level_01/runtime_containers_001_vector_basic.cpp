#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::int_t> values;
	assert(values.empty().native_value() == true);
	assert(values.size() == 0U);

	values.append(scpp::int_t(4));
	values.push_back(scpp::int_t(7));
	assert(values.empty().native_value() == false);
	assert(values.size() == 2U);
	assert(values.at(0).native_value() == 4);
	assert(values.at(scpp::int_t(1)).native_value() == 7);
	assert(values.index(scpp::int_t(0)).native_value() == 4);

	values.at(0) = scpp::int_t(10);
	assert(values.native_value().at(0).native_value() == 10);

	values.clear();
	assert(values.size() == 0U);
	assert(values.empty().native_value() == true);
	return 0;
}
