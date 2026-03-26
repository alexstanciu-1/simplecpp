#include "tests/runtime/runtime_test_common.hpp"

int main() {
	const scpp::nullable<scpp::int_t> empty(scpp::null);
	runtime_test::expect_throw<std::bad_optional_access>([&empty]() {
		(void) empty.value();
	});

	scpp::nullable<scpp::int_t> value(scpp::int_t(15));
	assert(value.value().native_value() == 15);
	return 0;
}
