#include "test_common.hpp"

#include "operators/identity/strict_identity.hpp"

namespace {

static void test_mixed_from_null_dynamic_is_identical_to_null() {
	scpp::dynamic_t<> dynamic_null = scpp::dynamic_t<>(scpp::null_t{});
	scpp::mixed_t value(dynamic_null);

	assert(scpp::identical(value, scpp::null_t{}).native_value());
	assert(scpp::identical(scpp::null_t{}, value).native_value());
}

static void test_mixed_from_non_null_dynamic_is_not_identical_to_null() {
	scpp::hash_t<scpp::mixed_t> row;
	row.set(scpp::string_t("v"), scpp::mixed_t(scpp::int_t<>(1)));
	scpp::mixed_t value(scpp::dynamic_t<>(std::make_shared<scpp::hash_t<scpp::mixed_t>>(std::move(row))));

	assert(!scpp::identical(value, scpp::null_t{}).native_value());
	assert(!scpp::identical(scpp::null_t{}, value).native_value());
}

} // namespace

int main() {
	test_mixed_from_null_dynamic_is_identical_to_null();
	test_mixed_from_non_null_dynamic_is_not_identical_to_null();
	return 0;
}
