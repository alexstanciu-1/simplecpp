#include "tests/runtime/runtime_test_common.hpp"

int main() {
	const scpp::mixed_t mixed_true(scpp::bool_t(true));
	const scpp::mixed_t mixed_false(scpp::bool_t(false));
	const scpp::mixed_t mixed_int_one(scpp::int_t<>(1));
	const scpp::mixed_t mixed_float_one(scpp::float_t(1.0));
	const scpp::mixed_t mixed_label(scpp::string_t("alpha"));

	assert(static_cast<bool>(scpp::php::identical(mixed_true, scpp::bool_t(true))));
	assert(!static_cast<bool>(scpp::php::identical(mixed_true, scpp::int_t<>(1))));
	assert(static_cast<bool>(scpp::php::identical(mixed_false, scpp::bool_t(false))));
	assert(static_cast<bool>(scpp::php::identical(mixed_int_one, scpp::int_t<>(1))));
	assert(!static_cast<bool>(scpp::php::identical(mixed_int_one, scpp::float_t(1.0))));
	assert(static_cast<bool>(scpp::php::identical(mixed_float_one, scpp::float_t(1.0))));
	assert(!static_cast<bool>(scpp::php::identical(mixed_float_one, scpp::int_t<>(1))));
	assert(static_cast<bool>(scpp::php::identical(mixed_label, scpp::string_t("alpha"))));
	assert(!static_cast<bool>(scpp::php::identical(mixed_label, scpp::string_t("beta"))));

	scpp::nullable<scpp::string_t> maybe_label(scpp::string_t("ready"));
	scpp::nullable<scpp::string_t> missing_label(scpp::null);
	assert(static_cast<bool>(scpp::php::identical(maybe_label, scpp::string_t("ready"))));
	assert(static_cast<bool>(scpp::php::identical(missing_label, scpp::null)));

	scpp::result_or_false<scpp::int_t<>> false_or_number(scpp::false_sentinel);
	scpp::result_or_false<scpp::int_t<>> value_or_number(scpp::int_t<>(12));
	assert(static_cast<bool>(scpp::php::identical(false_or_number, scpp::bool_t(false))));
	assert(static_cast<bool>(scpp::php::identical(value_or_number, scpp::int_t<>(12))));

	auto rof_coalesce = scpp::php::coalesce_eval(
		[&]() -> decltype(auto) { return false_or_number; },
		[&]() -> decltype(auto) { return scpp::int_t<>(99); }
	);
	assert(rof_coalesce.native_value() == 99);
	auto rof_coalesce_value = scpp::php::coalesce_eval(
		[&]() -> decltype(auto) { return value_or_number; },
		[&]() -> decltype(auto) { return scpp::int_t<>(99); }
	);
	assert(rof_coalesce_value.native_value() == 12);

	auto rof_elvis = scpp::php::ternary_eval(
		[&]() -> decltype(auto) { return false_or_number; },
		[&]() -> decltype(auto) { return false_or_number; },
		[&]() -> decltype(auto) { return scpp::int_t<>(77); }
	);
	assert(rof_elvis.has_value().native_value() == true);
	assert(rof_elvis.value().native_value() == 77);

	scpp::result_or_bool<scpp::int_t<>> bool_true_or_number(scpp::bool_t(true));
	scpp::result_or_bool<scpp::int_t<>> bool_false_or_number(scpp::false_sentinel);
	assert(static_cast<bool>(scpp::php::identical(bool_true_or_number, scpp::bool_t(true))));
	assert(static_cast<bool>(scpp::php::identical(bool_false_or_number, scpp::bool_t(false))));

	runtime_test::expect_throw<std::runtime_error>([&]() {
		(void) scpp::php::coalesce_eval(
			[&]() -> decltype(auto) { return bool_false_or_number; },
			[&]() -> decltype(auto) { return scpp::int_t<>(15); }
		);
	});

	auto rob_elvis_true = scpp::php::ternary_eval(
		[&]() -> decltype(auto) { return bool_true_or_number; },
		[&]() -> decltype(auto) { return bool_true_or_number; },
		[&]() -> decltype(auto) { return scpp::int_t<>(21); }
	);
	assert(rob_elvis_true.has_value().native_value() == false);
	assert(rob_elvis_true.is_true().native_value() == true);
	assert(rob_elvis_true.is_false().native_value() == false);

	auto rob_elvis_false = scpp::php::ternary_eval(
		[&]() -> decltype(auto) { return bool_false_or_number; },
		[&]() -> decltype(auto) { return bool_false_or_number; },
		[&]() -> decltype(auto) { return scpp::int_t<>(21); }
	);
	assert(rob_elvis_false.has_value().native_value() == true);
	assert(rob_elvis_false.value().native_value() == 21);

	return 0;
}
