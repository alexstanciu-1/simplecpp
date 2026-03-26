#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::value_p<runtime_test::sample_object> value(scpp::int_t(10));
	auto &alias = value;
	alias->value = scpp::int_t(70);
	assert(value->value.native_value() == 70);
	return 0;
}
