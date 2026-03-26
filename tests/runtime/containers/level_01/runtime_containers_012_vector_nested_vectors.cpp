#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::vector_t<scpp::int_t>> outer;
	scpp::vector_t<scpp::int_t> inner_a { scpp::int_t(1), scpp::int_t(2) };
	scpp::vector_t<scpp::int_t> inner_b { scpp::int_t(3) };
	outer.append(inner_a);
	outer.append(std::move(inner_b));
	assert(outer.size() == 2U);
	assert(outer.at(0).at(1).native_value() == 2);
	assert(outer.at(1).at(0).native_value() == 3);
	return 0;
}
