#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::value_p<scpp::vector_t<scpp::int_t>> first(scpp::vector_t<scpp::int_t>{ scpp::int_t(1), scpp::int_t(2) });
	auto second = first;
	second->at(0) = scpp::int_t(90);
	assert(first->at(0).native_value() == 1);
	assert(second->at(0).native_value() == 90);
	return 0;
}
