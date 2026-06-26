#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::fixed_array_t<scpp::int_t, 3> values{scpp::int_t(2), scpp::int_t(4), scpp::int_t(6)};
	assert(values.empty().native_value() == false);
	assert(values.size() == 3U);
	assert(values.static_size() == 3U);
	assert(values.at(0).native_value() == 2);
	assert(values.at(scpp::int_t(1)).native_value() == 4);
	assert(values.index(scpp::int_t(2)).native_value() == 6);

	values.at(1) = scpp::int_t(10);
	assert(values.native_value().at(1).native_value() == 10);
	assert(scpp::count(values).native_value() == 3);
	assert(scpp::empty(values).native_value() == false);

	scpp::int_t total(0);
	for (auto entry : scpp::foreach_range(values)) {
		total = total + entry.value_copy();
	}
	assert(total.native_value() == 18);

	runtime_test::expect_throw<scpp::runtime_error>([&]() {
		(void) values.at(scpp::int_t(3));
	});
	runtime_test::expect_throw<scpp::runtime_error>([]() {
		scpp::fixed_array_t<scpp::int_t, 2> mismatch{scpp::int_t(1)};
		(void) mismatch;
	});

	return 0;
}
