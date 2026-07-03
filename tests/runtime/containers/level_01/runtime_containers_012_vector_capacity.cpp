#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::int_t<>> values;
	assert(values.capacity() == 0U);
	values.reserve(8U);
	assert(values.capacity() >= 8U);
	values.append(scpp::int_t<>(1));
	values.append(scpp::int_t<>(2));
	values.resize(5U, scpp::int_t<>(9));
	assert(values.size() == 5U);
	assert(values.at(4).native_value() == 9);
	values.resize(1U, scpp::int_t<>(0));
	assert(values.size() == 1U);
	assert(values.at(0).native_value() == 1);
	values.clear();
	assert(values.size() == 0U);
	assert(values.capacity() >= 8U);
	values.append(scpp::int_t<>(3));
	values.compact(4U);
	assert(values.size() == 1U);
	assert(values.capacity() >= 4U);
	assert(values.at(0).native_value() == 3);
	values.compact();
	assert(values.size() == 1U);
	assert(values.at(0).native_value() == 3);
	return 0;
}
