#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::string_t empty(nullptr);
	assert(empty.empty().native_value() == true);
	assert(empty.size() == 0U);

	scpp::string_t value("Hello");
	value.append(scpp::string_t(", world"));
	assert(value.native_value() == "Hello, world");
	assert(value.size() == 12U);
	assert((value == scpp::string_t("Hello, world")).native_value() == true);
	assert((value != scpp::string_t("Hello")).native_value() == true);

	auto combined = value + scpp::string_t("!");
	assert(combined.native_value() == "Hello, world!");
	value._unset_();
	assert(value.empty().native_value() == true);
	return 0;
}
