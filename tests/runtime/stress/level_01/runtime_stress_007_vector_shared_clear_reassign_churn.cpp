#include "tests/runtime/runtime_test_common.hpp"

int main() {
	for (int i = 0; i < 2000; ++i) {
		scpp::vector_t<scpp::shared_p<runtime_test::sample_object>> values;
		for (int j = 0; j < 8; ++j) {
			values.append(scpp::shared<runtime_test::sample_object>(scpp::int_t(i + j)));
		}
		values.clear();
		values.append(scpp::shared<runtime_test::sample_object>(scpp::int_t(i)));
		assert(values.at(0)->value.native_value() == i);
	}
	return 0;
}
