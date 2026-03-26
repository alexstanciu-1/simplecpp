#include "tests/runtime/runtime_test_common.hpp"

int main() {
	for (int i = 0; i < 1000; ++i) {
		scpp::vector_t<scpp::vector_t<scpp::int_t>> outer;
		for (int j = 0; j < 4; ++j) {
			scpp::vector_t<scpp::int_t> inner;
			inner.append(scpp::int_t(i + j));
			inner.append(scpp::int_t(i + j + 1));
			outer.append(std::move(inner));
		}
		assert(outer.at(3).at(1).native_value() == i + 4);
	}
	return 0;
}
