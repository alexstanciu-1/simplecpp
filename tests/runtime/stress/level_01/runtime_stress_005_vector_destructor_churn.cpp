#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	for (int round = 0; round < 400; ++round) {
		scpp::vector_t<runtime_test::lifetime_probe> values;
		for (int i = 0; i < 128; ++i) {
			values.append(runtime_test::lifetime_probe(scpp::int_t(i + round)));
		}
		assert(values.size() == 128);
		for (int i = 0; i < 128; ++i) {
			assert(values.at(scpp::int_t(i)).value.native_value() == i + round);
		}
		values.clear();
		assert(values.size() == 0);
	}
	runtime_test::assert_lifetime_balanced();
	return 0;
}
