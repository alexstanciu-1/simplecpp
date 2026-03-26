#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::nullable<scpp::vector_t<scpp::int_t>> first(scpp::vector_t<scpp::int_t>{ scpp::int_t(1), scpp::int_t(2) });
	auto second = first;
	second.value().at(0) = scpp::int_t(50);
	assert(first.value().at(0).native_value() == 1);
	assert(second.value().at(0).native_value() == 50);
	return 0;
}
