#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::nullable<scpp::string_t> first(scpp::string_t("abc"));
	auto copy = first;
	assert(copy.has_value().native_value() == true);
	assert(copy.value().native_value() == "abc");

	auto moved = std::move(copy);
	assert(moved.has_value().native_value() == true);
	assert(moved.value().native_value() == "abc");

	first.value().append(scpp::string_t("def"));
	assert(first.value().native_value() == "abcdef");
	assert(moved.value().native_value() == "abc");
	return 0;
}
