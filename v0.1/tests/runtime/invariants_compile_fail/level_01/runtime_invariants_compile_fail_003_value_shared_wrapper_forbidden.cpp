#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::value_p<scpp::shared_p<runtime_test::sample_object>> invalid_value;
	(void) invalid_value;
	return 0;
}
