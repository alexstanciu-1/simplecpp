#include "tests/runtime/runtime_test_common.hpp"

#include <iostream>

int main() {
	scpp::mixed_t table;
	table[10] = scpp::string_t("test");
	table["name"][10] = scpp::string_t("value");
	table["name"][11] = scpp::int_t(22);

	scpp::string_t first = table[10];
	scpp::string_t nested_text = table["name"][10];
	scpp::int_t nested_int = table["name"][11];

	assert(first.native_value() == "test");
	assert(nested_text.native_value() == "value");
	assert(nested_int.native_value() == 22);
	assert(table.isset(10).native_value() == true);
	assert(table.isset("name").native_value() == true);
	assert(table["name"].size().native_value() == 2);

	std::cout << "table_access_autovivify_ok\n";
	return 0;
}
