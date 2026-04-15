#include "tests/runtime/runtime_test_common.hpp"

int main() {
	assert(scpp::php::empty(scpp::null).native_value() == true);
	assert(scpp::php::empty(scpp::string_t("" )).native_value() == true);
	assert(scpp::php::empty(scpp::bool_t(false)).native_value() == false);
	assert(scpp::php::empty(scpp::int_t(0)).native_value() == false);
	assert(scpp::php::empty(scpp::string_t("0")).native_value() == false);
	return 0;
}
