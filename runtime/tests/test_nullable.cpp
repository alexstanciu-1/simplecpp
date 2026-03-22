#include "test_common.hpp"

// Verifies empty construction and sentinel normalization.
static void test_empty_state() {
	const scpp::nullable<scpp::int_t> empty_default;
	const scpp::nullable<scpp::int_t> empty_null(scpp::null);
	const scpp::nullable<scpp::int_t> empty_nullopt(scpp::nullopt);

	assert(empty_default.has_value().native_value() == false);
	assert((empty_default == scpp::null).native_value() == true);
	assert((empty_default == scpp::nullopt).native_value() == true);
	assert((empty_null == scpp::null).native_value() == true);
	assert((empty_nullopt == scpp::nullopt).native_value() == true);
	assert((empty_null == empty_nullopt).native_value() == true);
}

// Verifies present-value storage and wrapped value access.
static void test_present_value() {
	scpp::nullable<scpp::int_t> value(scpp::int_t(42));

	assert(value.has_value().native_value() == true);
	assert((value != scpp::null).native_value() == true);
	assert((value != scpp::nullopt).native_value() == true);
	assert(value.value().native_value() == 42);
	assert(value.native_value().has_value() == true);
}

// Verifies reset and value_or() behavior using the current implementation contract.
static void test_reset_and_value_or() {
	scpp::nullable<scpp::int_t> value(scpp::int_t(7));
	assert(value.value_or(scpp::int_t(99)).native_value() == 7);

	value.reset();
	assert((value == scpp::null).native_value() == true);
	assert(value.value_or(scpp::int_t(99)).native_value() == 99);

	value = scpp::nullable<scpp::int_t>(scpp::int_t(11));
	value.reset(scpp::nullopt);
	assert((value == scpp::nullopt).native_value() == true);
}

// Verifies nullable equality delegates to wrapped-value comparisons.
static void test_nullable_equality() {
	const scpp::nullable<scpp::int_t> left(scpp::int_t(5));
	const scpp::nullable<scpp::int_t> same(scpp::int_t(5));
	const scpp::nullable<scpp::int_t> different(scpp::int_t(6));
	const scpp::nullable<scpp::int_t> empty(scpp::null);

	assert((left == same).native_value() == true);
	assert((left != same).native_value() == false);
	assert((left == different).native_value() == false);
	assert((left != different).native_value() == true);
	assert((left == empty).native_value() == false);
	assert((empty != left).native_value() == true);
}

int main() {
	test_empty_state();
	test_present_value();
	test_reset_and_value_or();
	test_nullable_equality();
	return 0;
}
