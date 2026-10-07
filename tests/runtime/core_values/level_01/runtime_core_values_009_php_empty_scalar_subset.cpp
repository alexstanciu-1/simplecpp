#include "tests/runtime/runtime_test_common.hpp"

int main() {
	runtime_test::expect_throw<scpp::runtime_error>([&]() { (void)scpp::php::empty(scpp::null); });
	assert(scpp::php::empty(scpp::string_t("" )).native_value() == true);
	runtime_test::expect_throw<scpp::runtime_error>([&]() { (void)scpp::php::empty(scpp::bool_t(false)); });
	runtime_test::expect_throw<scpp::runtime_error>([&]() { (void)scpp::php::empty(scpp::int_t<>(0)); });
	runtime_test::expect_throw<scpp::runtime_error>([&]() { (void)scpp::php::empty(scpp::float_t(0.0)); });
	assert(scpp::php::empty(scpp::string_t("0")).native_value() == false);
	return 0;
}
