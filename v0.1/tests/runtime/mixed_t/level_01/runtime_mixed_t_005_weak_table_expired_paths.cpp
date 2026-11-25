#include "tests/runtime/runtime_test_common.hpp"

#include <iostream>
#include <memory>
#include <stdexcept>

int main() {
	std::shared_ptr<scpp::hash_t<scpp::mixed_t>> owner = std::make_shared<scpp::hash_t<scpp::mixed_t>>();
	(*owner)[scpp::string_t("x")] = scpp::mixed_t(scpp::int_t<>(7));

	scpp::mixed_t weak_value{std::weak_ptr<scpp::hash_t<scpp::mixed_t>>(owner)};
	owner.reset();

	scpp::mixed_t safe_read = weak_value.get("x");
	assert(safe_read.is_null().native_value() == true);
	assert(weak_value.isset("x").native_value() == false);

	runtime_test::expect_throw<std::runtime_error>([&]() {
		(void) weak_value.at(scpp::string_t("x"));
	});
	runtime_test::expect_throw<std::runtime_error>([&]() {
		weak_value["x"] = scpp::mixed_t(scpp::int_t<>(8));
	});

	std::cout << "weak_table_expired_paths_ok\n";
	return 0;
}
