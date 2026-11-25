#include "tests/runtime/runtime_test_common.hpp"

#include <iostream>

int main() {
	scpp::hash_t<scpp::mixed_t> table;
	table[scpp::string_t("name")] = scpp::mixed_t(scpp::string_t("alex"));

	scpp::mixed_t found_value = table._find_val(scpp::string_t("name"));
	scpp::mixed_t missing_value = table._find_val(scpp::string_t("missing"));
	const auto &const_table = table;
	const scpp::mixed_t &const_hit = const_table[scpp::string_t("name")];
	const scpp::mixed_t &const_miss = const_table[scpp::string_t("missing")];

	assert(found_value.get_string().native_value() == "alex");
	assert(missing_value.is_null().native_value() == true);
	assert(const_hit.get_string().native_value() == "alex");
	assert(const_miss.is_null().native_value() == true);

	std::cout << "find_val_and_const_read_ok\n";
	return 0;
}
