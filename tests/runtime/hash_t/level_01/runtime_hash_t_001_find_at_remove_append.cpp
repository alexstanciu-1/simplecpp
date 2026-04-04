#include "tests/runtime/runtime_test_common.hpp"

#include <iostream>

int main() {
	scpp::hash_t<scpp::mixed_t> table;
	assert(table.append(scpp::mixed_t(scpp::int_t(100))).native_value() == 0);
	assert(table.append(scpp::mixed_t(scpp::int_t(200))).native_value() == 1);

	auto found_before_remove = table.find(scpp::int_t(1));
	assert(scpp::was_found(found_before_remove).native_value() == true);
	assert(found_before_remove.value().get_int().native_value() == 200);

	assert(table.remove(scpp::int_t(0)) == true);
	assert(table.has(scpp::int_t(0)).native_value() == false);
	assert(table.has(scpp::int_t(1)).native_value() == true);
	assert(table.append(scpp::mixed_t(scpp::int_t(300))).native_value() == 2);
	assert(table.at(scpp::int_t(2)).get_int().native_value() == 300);

	runtime_test::expect_throw<std::out_of_range>([&]() {
		(void) table.at(scpp::int_t(0));
	});

	std::cout << "find_at_remove_append_ok\n";
	return 0;
}
