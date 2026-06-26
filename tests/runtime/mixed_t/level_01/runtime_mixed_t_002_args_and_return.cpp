#include "tests/runtime/runtime_test_common.hpp"

#include <iostream>

static scpp::float_t add_half(scpp::float_t value) {
	return value + scpp::float_t(0.5);
}

static scpp::string_t wrap_text(scpp::string_t value) {
	return value + scpp::string_t("!");
}

static scpp::float_t return_float_from_mixed(const scpp::mixed_t &value) {
	return value;
}

int main() {
	scpp::mixed_t int_value = scpp::int_t<>(2);
	scpp::mixed_t float_value = scpp::float_t(2.5);
	scpp::mixed_t text_value = scpp::string_t("abc");

	scpp::float_t from_arg_int = add_half(int_value);
	scpp::float_t from_arg_float = add_half(float_value);
	scpp::string_t from_arg_string = wrap_text(text_value);
	scpp::float_t from_return_int = return_float_from_mixed(int_value);
	scpp::float_t from_return_float = return_float_from_mixed(float_value);

	assert(from_arg_int.native_value() == 2.5);
	assert(from_arg_float.native_value() == 3.0);
	assert(from_arg_string.native_value() == "abc!");
	assert(from_return_int.native_value() == 2.0);
	assert(from_return_float.native_value() == 2.5);

	std::cout << "args_and_return_ok\n";
	return 0;
}
