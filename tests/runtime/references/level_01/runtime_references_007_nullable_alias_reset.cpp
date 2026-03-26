#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::nullable<scpp::int_t> value(scpp::int_t(5));
	auto &alias = value;
	alias.reset();
	assert(value.has_value().native_value() == false);
	return 0;
}
