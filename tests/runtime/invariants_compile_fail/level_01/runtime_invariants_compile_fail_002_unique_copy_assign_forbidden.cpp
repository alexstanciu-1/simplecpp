#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::unique_p<runtime_test::sample_object> first(std::make_unique<runtime_test::sample_object>(scpp::int_t(1)));
	scpp::unique_p<runtime_test::sample_object> second;
	second = first;
	return 0;
}
