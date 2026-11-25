#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::string_t value("Hello");
	assert(value.native_value() == "Hello");
	assert(value.empty().native_value() == false);
	assert(value.size() == 5U);

	value.append(scpp::string_t(", world"));
	assert(value.native_value() == "Hello, world");

	const scpp::string_t joined = value + scpp::string_t("!");
	assert(joined.native_value() == "Hello, world!");
	assert((joined == scpp::string_t("Hello, world!")).native_value() == true);
	assert((joined != scpp::string_t("Hello")).native_value() == true);

	value._unset_();
	assert(value.native_value() == "");
	assert(value.empty().native_value() == true);
	return 0;
}
