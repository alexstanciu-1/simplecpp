#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::int_t> values { scpp::int_t(1), scpp::int_t(2) };
	auto &slot = values.at(1);
	slot = scpp::int_t(22);
	assert(values.at(1).native_value() == 22);
	return 0;
}
