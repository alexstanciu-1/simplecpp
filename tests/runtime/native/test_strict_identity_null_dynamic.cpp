#include "operators/identity/strict_identity.hpp"
#include "test_common.hpp"

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

static void check_empty_carrier(const scpp::mixed_t &empty, const scpp::mixed_t &present) {
	assert(scpp::identical(empty, empty).native_value());
	assert(!scpp::not_identical(empty, empty).native_value());
	assert(!scpp::identical(empty, present).native_value());
	assert(!scpp::identical(present, empty).native_value());
	assert(scpp::not_identical(empty, present).native_value());
}

static void test_empty_table_carriers_and_existing_contents() {
	using table_type = scpp::hash_t<scpp::mixed_t>;
	scpp::dynamic_t<> absent{scpp::null_t{}};
	scpp::dynamic_t<> first{std::make_shared<table_type>()};
	scpp::dynamic_t<> second{std::make_shared<table_type>()};
	scpp::mixed_t empty_shared{absent}, present_shared{first};
	scpp::mixed_t empty_dynamic{scpp::dynamic_box(absent)};
	scpp::mixed_t present_dynamic{scpp::dynamic_box(first)};
	scpp::mixed_t empty_owned{scpp::unique_p<table_type>{scpp::null_t{}}};
	scpp::mixed_t present_owned{scpp::unique_p<table_type>{std::make_unique<table_type>()}};
	check_empty_carrier(empty_shared, present_shared);
	check_empty_carrier(empty_dynamic, present_dynamic);
	check_empty_carrier(empty_owned, present_owned);
	assert(scpp::identical(empty_dynamic, scpp::null_t{}).native_value());
	assert(!scpp::identical(empty_dynamic, scpp::mixed_t{scpp::null_t{}}).native_value());
	assert(!scpp::identical(empty_dynamic, empty_shared).native_value());
	// Same-kind boxed tables retain content comparison, not pointer identity.
	scpp::mixed_t other_dynamic{scpp::dynamic_box(second)};
	assert(first.get() != second.get());
	assert(scpp::identical(present_dynamic, other_dynamic).native_value());
	second->set(scpp::string_t{"v"}, scpp::mixed_t{scpp::int_t<>{1LL}});
	assert(!scpp::identical(present_dynamic, other_dynamic).native_value());
	assert(scpp::identical(scpp::mixed_t{scpp::int_t<>{1LL}}, scpp::int_t<>{1LL}).native_value());
	assert(scpp::identical(scpp::mixed_t{scpp::float_t{1.0}}, scpp::float_t{1.0}).native_value());
	assert(scpp::identical(scpp::mixed_t{true}, scpp::bool_t{true}).native_value());
	assert(scpp::identical(scpp::mixed_t{scpp::string_t{"x"}}, scpp::string_t{"x"}).native_value());
}

} // namespace

int main() {
	test_mixed_from_null_dynamic_is_identical_to_null();
	test_mixed_from_non_null_dynamic_is_not_identical_to_null();
	test_empty_table_carriers_and_existing_contents();
	return 0;
}
