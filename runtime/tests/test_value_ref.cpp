#include "test_common.hpp"

namespace {

struct point final {
	scpp::int_t x;
	scpp::int_t y;

	point(scpp::int_t x_value, scpp::int_t y_value)
		: x(std::move(x_value)), y(std::move(y_value)) {
	}
};

struct reader {
	virtual ~reader() = default;
	virtual scpp::int_t read() const = 0;
};

struct local_reader final : reader {
	scpp::int_t payload;

	explicit local_reader(scpp::int_t initial_payload)
		: payload(std::move(initial_payload)) {
	}

	scpp::int_t read() const override {
		return payload;
	}
};

static void test_value_wrapper_behavior() {
	auto value = scpp::value<point>(scpp::int_t(1), scpp::int_t(2));
	assert(value.has_value().native_value() == true);
	assert(value->x.native_value() == 1);
	assert((*value).y.native_value() == 2);

	auto copy = value;
	copy->x = scpp::int_t(9);
	assert(value->x.native_value() == 1);
	assert(copy->x.native_value() == 9);
}

static void test_native_reference_behavior() {
	auto value = scpp::value<point>(scpp::int_t(3), scpp::int_t(4));
	auto &alias = value.get();
	alias.x = scpp::int_t(7);
	assert(value->x.native_value() == 7);

	auto shared_reader = scpp::shared<local_reader>(scpp::int_t(11));
	auto &shared_alias = shared_reader;
	static_assert(std::is_same_v<decltype(shared_alias), scpp::shared_p<local_reader> &>);
	assert(shared_alias->read().native_value() == 11);
}

static void test_shared_upcast_behavior() {
	scpp::shared_p<local_reader> local = scpp::shared<local_reader>(scpp::int_t(21));
	scpp::shared_p<reader> base = local;
	assert(base->read().native_value() == 21);
}

} // namespace

int main() {
	test_value_wrapper_behavior();
	test_native_reference_behavior();
	test_shared_upcast_behavior();
	return 0;
}
