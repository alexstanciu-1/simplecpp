#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::nullable<scpp::int_t>> first;
	first.append(scpp::nullable<scpp::int_t>(scpp::int_t(11)));
	first.append(scpp::nullable<scpp::int_t>(scpp::null));
	auto second = first;
	second.at(0).value() = scpp::int_t(99);
	second.at(1) = scpp::nullable<scpp::int_t>(scpp::int_t(3));
	assert(first.at(0).value().native_value() == 11);
	assert(first.at(1).has_value().native_value() == false);
	assert(second.at(0).value().native_value() == 99);
	assert(second.at(1).value().native_value() == 3);
	return 0;
}
