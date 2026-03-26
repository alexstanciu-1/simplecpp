#include "tests/runtime/runtime_test_common.hpp"

namespace {
struct point final {
	scpp::int_t x;
	scpp::int_t y;

	point(scpp::int_t x_value, scpp::int_t y_value)
		: x(std::move(x_value)), y(std::move(y_value)) {
	}
};
}

int main() {
	auto value = scpp::value<point>(scpp::int_t(3), scpp::int_t(4));
	auto &alias = value.get();
	alias.x = scpp::int_t(7);
	alias.y = scpp::int_t(8);
	assert(value->x.native_value() == 7);
	assert(value->y.native_value() == 8);
	return 0;
}
