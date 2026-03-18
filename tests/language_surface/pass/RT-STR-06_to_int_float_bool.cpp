#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-STR-06 and RT-STR-07.
// Primary requirements:
// - RT-STR-06 Explicit conversion from string_t to int_t, float_t, and bool_t must follow the allowed conversion matrix.
// - RT-STR-07 string_t to bool_t conversion must enforce the currently allowed values exactly.

int main() {
	using namespace scpp;

	if (to_int(string_t("42")).native_value() != 42) {
		return 1;
	}
	if (to_float(string_t("3.5")).native_value() != 3.5) {
		return 2;
	}
	if (!(to_bool(string_t("true")) == bool_t(true))) {
		return 3;
	}
	if (!(to_bool(string_t("0")) == bool_t(false))) {
		return 4;
	}
	if (!(to_string(int_t(12)) == string_t("12"))) {
		return 5;
	}
	if (!(to_string(bool_t(true)) == string_t("true"))) {
		return 6;
	}

	return 0;
}
