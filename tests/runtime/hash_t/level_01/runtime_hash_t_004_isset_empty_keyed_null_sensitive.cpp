#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::hash_t<scpp::mixed_t> row;
	row.set(scpp::string_t("id"), scpp::mixed_t(scpp::int_t<>(42)));
	row.set(scpp::string_t("maybe"), scpp::mixed_t(scpp::null));
	row.set(scpp::string_t("name"), scpp::mixed_t(scpp::string_t("")));

	assert(scpp::php::isset(row, scpp::string_t("id")).native_value() == true);
	assert(scpp::php::isset(row, scpp::string_t("maybe")).native_value() == false);
	assert(scpp::php::isset(row, scpp::string_t("missing")).native_value() == false);

	assert(scpp::php::empty(row, scpp::string_t("name")).native_value() == true);
	runtime_test::expect_throw<scpp::runtime_error>([&]() { (void)scpp::php::empty(row, scpp::string_t("maybe")); });
	runtime_test::expect_throw<scpp::runtime_error>([&]() { (void)scpp::php::empty(row, scpp::string_t("missing")); });
	return 0;
}
