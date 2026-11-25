#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::string_t> values;
	values.append(scpp::string_t("a"));
	values.append(scpp::string_t("b"));

	auto moved = std::move(values);
	assert(moved.size() == 2U);
	assert(moved.at(0).native_value() == "a");
	assert(moved.at(1).native_value() == "b");
	return 0;
}
