#include "tests/runtime/runtime_test_common.hpp"

#include <iostream>

int main() {
	scpp::mixed_t ints;
	ints[0] = scpp::int_t(1);
	ints[1] = scpp::int_t(2);
	ints[2] = scpp::int_t(3);

	scpp::int_t extracted_int = ints[1];
	scpp::float_t extracted_float_from_int = ints[1];

	scpp::mixed_t floats;
	floats[0] = scpp::float_t(1.10);
	floats[1] = scpp::float_t(2.01);
	floats[2] = scpp::float_t(3.03);

	scpp::float_t extracted_float = floats[1];

	scpp::mixed_t text = scpp::string_t("hello");
	scpp::string_t extracted_text = text;

	assert(extracted_int.native_value() == 2);
	assert(extracted_float_from_int.native_value() == 2.0);
	assert(extracted_float.native_value() == 2.01);
	assert(extracted_text.native_value() == "hello");

	std::cout << "typed_boundary_extract_ok\n";
	return 0;
}
