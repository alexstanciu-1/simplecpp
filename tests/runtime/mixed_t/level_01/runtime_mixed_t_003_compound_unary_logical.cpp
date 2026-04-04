#include "tests/runtime/runtime_test_common.hpp"

#include <iostream>

int main() {
	scpp::mixed_t int_value = scpp::int_t(4);
	int_value += scpp::mixed_t(scpp::int_t(3));
	assert(int_value.int_value().native_value() == 7);

	scpp::mixed_t float_value = scpp::float_t(1.5);
	float_value += scpp::mixed_t(scpp::int_t(2));
	assert(float_value.float_value().native_value() == 3.5);

	scpp::mixed_t string_value = scpp::string_t("ab");
	string_value += scpp::mixed_t(scpp::string_t("cd"));
	assert(string_value.get_string().native_value() == "abcd");

	scpp::mixed_t inc_value = scpp::int_t(10);
	++inc_value;
	assert(inc_value.int_value().native_value() == 11);

	scpp::mixed_t dec_value = scpp::float_t(4.5);
	dec_value--;
	assert(dec_value.float_value().native_value() == 3.5);

	assert((!scpp::mixed_t(scpp::int_t(0))).native_value() == true);
	assert((scpp::mixed_t(scpp::int_t(1)) && scpp::mixed_t(scpp::float_t(2.0))).native_value() == true);
	assert((scpp::mixed_t(scpp::float_t(0.0)) || scpp::mixed_t(scpp::bool_t(true))).native_value() == true);

	std::cout << "compound_unary_logical_ok\n";
	return 0;
}
