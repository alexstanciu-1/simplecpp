#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::string_t> values;
	values.push_back(scpp::string_t("ab"));
	values.push_back(scpp::string_t("cd"));

	values.at(0).append(scpp::string_t("ef"));
	assert(values.index(0).native_value() == "abef");
	assert(values.index(scpp::int_t(1)).native_value() == "cd");

	values.clear();
	assert(values.empty().native_value() == true);
	return 0;
}
