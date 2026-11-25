#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::int_t<>> values;
	values.append(scpp::int_t<>(1));

	runtime_test::expect_throw<scpp::runtime_error>([&values]() {
		(void) values.at(scpp::int_t<>(-1));
	});

	runtime_test::expect_throw<scpp::runtime_error>([&values]() {
		(void) values.at(2);
	});

	values._unset_();
	assert(values.size() == 0U);
	assert(values.empty().native_value() == true);
	return 0;
}
