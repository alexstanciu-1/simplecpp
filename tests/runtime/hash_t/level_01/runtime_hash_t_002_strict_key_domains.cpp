#include "tests/runtime/runtime_test_common.hpp"

#include <iostream>

int main() {
	scpp::hash_t<scpp::mixed_t> table;
	table[scpp::int_t<>(123)] = scpp::mixed_t(scpp::string_t("int-key"));
	table[scpp::string_t("123")] = scpp::mixed_t(scpp::string_t("string-key"));

	assert(table.has(scpp::int_t<>(123)).native_value() == true);
	assert(table.has(scpp::string_t("123")).native_value() == true);
	assert(table.at(scpp::int_t<>(123)).get_string().native_value() == "int-key");
	assert(table.at(scpp::string_t("123")).get_string().native_value() == "string-key");

	std::cout << "strict_key_domains_ok\n";
	return 0;
}
