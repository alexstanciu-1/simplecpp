#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::unique_p<runtime_test::counter_reader> derived = scpp::unique<runtime_test::counter_reader>(scpp::int_t(12));
	scpp::unique_p<runtime_test::base_reader> base = std::move(derived);

	assert((derived == scpp::null).native_value() == true);
	assert(base.has_value().native_value() == true);
	assert(base->read().native_value() == 12);
	return 0;
}
