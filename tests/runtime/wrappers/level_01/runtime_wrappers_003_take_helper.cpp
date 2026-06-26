#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::int_t<> value(0);
	scpp::error_t error_state(scpp::string_t("seed"), scpp::int_t<>(9), scpp::string_t("seed.php"));
	scpp::bool_t bool_state(false);

	scpp::nullable<scpp::int_t<>> nullable_value(scpp::int_t<>(7));
	assert(scpp::php::take(value, nullable_value).native_value() == true);
	assert(value.native_value() == 7);
	nullable_value = scpp::null;
	assert(scpp::php::take(value, nullable_value).native_value() == false);
	assert(value.native_value() == 7);

	scpp::result_or_false<scpp::int_t<>> falseable_value(scpp::int_t<>(11));
	assert(scpp::php::take(value, falseable_value).native_value() == true);
	assert(value.native_value() == 11);
	falseable_value = scpp::false_sentinel;
	assert(scpp::php::take(value, falseable_value).native_value() == false);
	assert(value.native_value() == 11);

	scpp::result<scpp::int_t<>> ok_value(scpp::int_t<>(13));
	assert(scpp::php::take(value, error_state, ok_value).native_value() == true);
	assert(value.native_value() == 13);
	assert(error_state.get_message().native_value() == "seed");
	scpp::result<scpp::int_t<>> err_value(scpp::error_t(scpp::string_t("boom"), scpp::int_t<>(21), scpp::string_t("unit.php")));
	assert(scpp::php::take(value, error_state, err_value).native_value() == false);
	assert(value.native_value() == 13);
	assert(error_state.get_message().native_value() == "boom");
	assert(error_state.get_line().native_value() == 21);

	scpp::result_or_bool<scpp::int_t<>> bool_true(scpp::bool_t(true));
	assert(scpp::php::take(value, bool_state, bool_true).native_value() == true);
	assert(bool_state.native_value() == true);
	assert(value.native_value() == 13);
	scpp::result_or_bool<scpp::int_t<>> bool_false(scpp::false_sentinel);
	assert(scpp::php::take(value, bool_state, bool_false).native_value() == false);
	assert(bool_state.native_value() == false);
	assert(value.native_value() == 13);
	scpp::result_or_bool<scpp::int_t<>> bool_value(scpp::int_t<>(42));
	assert(scpp::php::take(value, bool_state, bool_value).native_value() == true);
	assert(value.native_value() == 42);
	assert(bool_state.native_value() == false);

	return 0;
}
