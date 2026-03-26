#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::nullable<scpp::int_t> first(scpp::int_t(8));
	scpp::nullable<scpp::int_t> second(std::move(first));
	first = scpp::nullable<scpp::int_t>(scpp::null);
	assert(first.has_value().native_value() == false);
	assert(second.value().native_value() == 8);
	return 0;
}
