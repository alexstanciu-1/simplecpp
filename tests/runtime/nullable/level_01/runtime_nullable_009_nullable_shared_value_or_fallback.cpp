#include "tests/runtime/runtime_test_common.hpp"

int main() {
	auto fallback = scpp::shared<runtime_test::sample_object>(scpp::int_t(33));
	scpp::nullable<scpp::shared_p<runtime_test::sample_object>> empty(scpp::null);
	auto chosen = empty.value_or(fallback);
	assert(chosen->value.native_value() == 33);
	assert(chosen.use_count() == 2U);
	return 0;
}
