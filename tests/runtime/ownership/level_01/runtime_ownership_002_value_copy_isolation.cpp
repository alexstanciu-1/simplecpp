#include "tests/runtime/runtime_test_common.hpp"

namespace {

struct point final {
	scpp::int_t x;
	scpp::int_t y;

	point(scpp::int_t x_value, scpp::int_t y_value)
		: x(std::move(x_value)), y(std::move(y_value)) {
	}
};

} // namespace

int main() {
	auto value = scpp::value<point>(scpp::int_t(1), scpp::int_t(2));
	assert(value.has_value().native_value() == true);
	assert(value->x.native_value() == 1);
	assert(value->y.native_value() == 2);

	auto copy = value;
	copy->x = scpp::int_t(9);
	assert(value->x.native_value() == 1);
	assert(copy->x.native_value() == 9);

	auto &native = value.get();
	native.y = scpp::int_t(7);
	assert(value->y.native_value() == 7);
	return 0;
}
