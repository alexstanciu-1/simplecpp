#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::int_t value(10);
	scpp::int_t &first = value;
	scpp::int_t &second = first;
	second = scpp::int_t(19);
	assert(value.native_value() == 19);
	assert(first.native_value() == 19);

	scpp::string_t text("abc");
	auto &text_alias = text;
	text_alias.append(scpp::string_t("def"));
	assert(text.native_value() == "abcdef");

	auto object = scpp::value<runtime_test::sample_object>(scpp::int_t(5));
	auto &native = object.get();
	native.value = scpp::int_t(15);
	assert(object->value.native_value() == 15);
	return 0;
}
