#include "tests/runtime/runtime_test_common.hpp"

int main() {
	const scpp::error_t empty;
	const scpp::error_t real_empty_message(scpp::string_t(""));
	assert(!scpp::condition_truthy(empty).native_value());
	assert(!scpp::condition_truthy(scpp::error_t::no_error).native_value());
	assert(scpp::condition_truthy(real_empty_message).native_value());
	assert(!scpp::identical(empty, real_empty_message).native_value());
	static_assert(std::is_const_v<std::remove_reference_t<decltype(scpp::error_t::no_error)>>);
	static_assert(std::is_same_v<decltype(std::declval<scpp::result<scpp::int_t<>>&>().error()), const scpp::error_t*>);
	static_assert(std::is_same_v<decltype(std::declval<scpp::result<scpp::int_t<>>&>().require_error("inspect")), const scpp::error_t&>);
	runtime_test::expect_throw<std::invalid_argument>([&]() {
		scpp::result<scpp::int_t<>> rejected(empty);
	});
	runtime_test::expect_throw<std::invalid_argument>([&]() {
		scpp::result<scpp::int_t<>> rejected(scpp::error_t{});
	});
	scpp::result<scpp::int_t<>> guarded(scpp::int_t<>(5));
	runtime_test::expect_throw<std::invalid_argument>([&]() { guarded = empty; });
	assert(guarded.value().native_value() == 5);
	guarded = real_empty_message;
	runtime_test::expect_throw<std::invalid_argument>([&]() { guarded = scpp::error_t{}; });
	assert(guarded.has_error().native_value());
	assert(guarded.error()->has_error().native_value());
	scpp::result<scpp::int_t<>> explicit_failure(scpp::error);
	assert(explicit_failure.error()->has_error().native_value());
	scpp::int_t<> value(0);
	scpp::error_t error_state(scpp::string_t("seed"), scpp::int_t<>(9), scpp::string_t("seed.php"));
	scpp::bool_t bool_state(false);

	scpp::nullable<scpp::int_t<>> nullable_value(scpp::int_t<>(7));
	assert(scpp::php::take(value, nullable_value).native_value() == true);
	assert(value.native_value() == 7);
	nullable_value = scpp::null;
	assert(scpp::php::take(value, nullable_value).native_value() == false);
	assert(value.native_value() == 7);

	const scpp::shared_p<int> empty_handle;
	const scpp::shared_p<int> present_handle(std::make_shared<int>(17));
	scpp::shared_p<int> handle_output(std::make_shared<int>(3));
	const auto previous_handle = handle_output;
	assert(!scpp::php::take(handle_output, empty_handle).native_value());
	assert(handle_output.get() == previous_handle.get());
	assert(scpp::php::take(handle_output, present_handle).native_value());
	assert(handle_output.get() == present_handle.get());
	assert(scpp::php::take(handle_output, handle_output).native_value());
	assert(*handle_output.get() == 17);

	scpp::result_or_false<scpp::int_t<>> falseable_value(scpp::int_t<>(11));
	assert(scpp::php::take(value, falseable_value).native_value() == true);
	assert(value.native_value() == 11);
	falseable_value = scpp::false_sentinel;
	assert(scpp::php::take(value, falseable_value).native_value() == false);
	assert(value.native_value() == 11);

	scpp::result<scpp::int_t<>> ok_value(scpp::int_t<>(13));
	assert(scpp::php::take(value, error_state, ok_value).native_value() == true);
	assert(value.native_value() == 13);
	assert(!error_state.has_error().native_value());
	scpp::result<scpp::int_t<>> err_value(scpp::error_t(scpp::string_t("boom"), scpp::int_t<>(21), scpp::string_t("unit.php")));
	assert(scpp::php::take(value, error_state, err_value).native_value() == false);
	assert(value.native_value() == 13);
	assert(error_state.get_message().native_value() == "boom");
	assert(error_state.get_line().native_value() == 21);
	assert(scpp::condition_truthy(error_state).native_value());
	assert(scpp::php::take(value, error_state, ok_value).native_value());
	assert(!scpp::condition_truthy(error_state).native_value());

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
