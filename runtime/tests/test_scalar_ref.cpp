#include "test_common.hpp"

namespace {

void add_one(scpp::int_ref value) {
	value++;
}

void append_mark(scpp::string_ref value) {
	value += scpp::string_t("!");
}

static void test_scalar_ref_accepts_native_and_value_slots() {
	scpp::int_t native(10);
	add_one(native);
	assert(native.native_value() == 11);

	scpp::mixed_t arr(scpp::shared<scpp::hash_t<scpp::mixed_t>>());
	arr[scpp::int_t(0)] = scpp::mixed_t(scpp::int_t(5));
	add_one(arr[scpp::int_t(0)]);
	assert(arr[scpp::int_t(0)].int_value().native_value() == 6);
}

static void test_string_ref_accepts_value_slots() {
	scpp::mixed_t arr(scpp::shared<scpp::hash_t<scpp::mixed_t>>());
	arr[scpp::string_t("name")] = scpp::mixed_t(scpp::string_t("Alex"));
	append_mark(arr[scpp::string_t("name")]);
	assert(arr[scpp::string_t("name")].string_if()->native_value() == "Alex!");
}

} // namespace

int main() {
	test_scalar_ref_accepts_native_and_value_slots();
	test_string_ref_accepts_value_slots();
	return 0;
}
