#include "tests/runtime/runtime_test_common.hpp"

#include <iostream>

int main() {
	assert(scpp::php::condition_truthy(scpp::string_t("")).native_value() == false);
	assert(scpp::php::condition_truthy(scpp::string_t("0")).native_value() == false);
	assert(scpp::php::condition_truthy(scpp::string_t("hello")).native_value() == true);

	auto shared_box = scpp::shared<runtime_test::sample_object>(scpp::int_t(5));
	scpp::shared_p<runtime_test::sample_object> empty_shared;
	assert(scpp::php::condition_truthy(shared_box).native_value() == true);
	assert(scpp::php::condition_truthy(empty_shared).native_value() == false);

	auto unique_box = scpp::unique<runtime_test::sample_object>(scpp::int_t(7));
	scpp::unique_p<runtime_test::sample_object> empty_unique;
	assert(scpp::php::condition_truthy(unique_box).native_value() == true);
	assert(scpp::php::condition_truthy(empty_unique).native_value() == false);

	scpp::weak_p<runtime_test::sample_object> live_weak = scpp::weak(shared_box);
	assert(scpp::php::condition_truthy(live_weak).native_value() == true);
	shared_box.reset();
	assert(scpp::php::condition_truthy(live_weak).native_value() == false);

	assert(scpp::php::condition_truthy(scpp::mixed_t(scpp::null)).native_value() == false);
	assert(scpp::php::condition_truthy(scpp::mixed_t(scpp::string_t(""))).native_value() == false);
	assert(scpp::php::condition_truthy(scpp::mixed_t(scpp::string_t("0"))).native_value() == false);
	assert(scpp::php::condition_truthy(scpp::mixed_t(scpp::string_t("hello"))).native_value() == true);
	assert((!scpp::mixed_t(scpp::string_t("hello"))).native_value() == false);
	assert((scpp::mixed_t(scpp::string_t("")) || scpp::mixed_t(scpp::string_t("hello"))).native_value() == true);
	assert((scpp::mixed_t(scpp::string_t("0")) && scpp::mixed_t(scpp::string_t("hello"))).native_value() == false);


	scpp::result_or_false<scpp::int_t> false_result(scpp::false_sentinel);
	scpp::result_or_bool<scpp::int_t> false_or_bool(scpp::false_sentinel);
	scpp::result_or_bool<scpp::int_t> true_or_bool(scpp::bool_t(true));
	scpp::result<scpp::int_t> error_result(scpp::error_sentinel_t{});
	assert(scpp::php::isset(false_result).native_value() == false);
	assert(scpp::php::isset(false_or_bool).native_value() == false);
	assert(scpp::php::isset(true_or_bool).native_value() == true);
	assert(scpp::php::isset(error_result).native_value() == false);

	assert(scpp::cast<scpp::bool_t>(scpp::string_t("")).native_value() == false);
	assert(scpp::cast<scpp::bool_t>(scpp::string_t("0")).native_value() == false);
	assert(scpp::cast<scpp::bool_t>(scpp::string_t("false")).native_value() == false);
	assert(scpp::cast<scpp::bool_t>(scpp::string_t("1")).native_value() == true);
	assert(scpp::cast<scpp::bool_t>(scpp::string_t("true")).native_value() == true);
	runtime_test::expect_throw<std::runtime_error>([]() {
		(void) scpp::cast<scpp::bool_t>(scpp::string_t("TRUE"));
	});

	std::cout << "condition_truthiness_ok\n";
	return 0;
}
