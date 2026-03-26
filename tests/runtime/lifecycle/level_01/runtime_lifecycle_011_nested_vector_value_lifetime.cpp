#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::lifetime_probe::reset_counts();
	{
		scpp::vector_t<scpp::vector_t<scpp::value_p<runtime_test::lifetime_probe>>> outer;
		scpp::vector_t<scpp::value_p<runtime_test::lifetime_probe>> inner;
		inner.append(scpp::value<runtime_test::lifetime_probe>(scpp::int_t(1)));
		inner.append(scpp::value<runtime_test::lifetime_probe>(scpp::int_t(2)));
		outer.append(std::move(inner));
		assert(runtime_test::lifetime_probe::alive == 2);
	}
	runtime_test::assert_lifetime_balanced();
	return 0;
}
