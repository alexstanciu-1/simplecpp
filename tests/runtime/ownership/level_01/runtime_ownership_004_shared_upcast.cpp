#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::shared_p<runtime_test::counter_reader> derived = scpp::shared<runtime_test::counter_reader>(scpp::int_t(21));
	scpp::shared_p<runtime_test::base_reader> base = derived;

	assert(base.has_value().native_value() == true);
	assert(base->read().native_value() == 21);
	assert(derived.use_count() == 2U);
	assert((base != scpp::null).native_value() == true);
	return 0;
}
