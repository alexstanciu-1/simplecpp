#include "test_common.hpp"

// Verifies wrapped boolean operations stay inside the scpp domain.
static void test_bool_operations() {
	const scpp::bool_t t(true);
	const scpp::bool_t f(false);

	assert((!f).native_value() == true);
	assert((t && !f).native_value() == true);
	assert((f || t).native_value() == true);
	assert((t == scpp::bool_t(true)).native_value() == true);
	assert((t != f).native_value() == true);
}

// Verifies integer arithmetic and comparison operators.
static void test_int_operations() {
	const scpp::int_t a(7);
	const scpp::int_t b(3);

	assert((+a).native_value() == 7);
	assert((-b).native_value() == -3);
	assert((a + b).native_value() == 10);
	assert((a - b).native_value() == 4);
	assert((a * b).native_value() == 21);
	assert((a / b).native_value() == 2);
	assert((a > b).native_value() == true);
	assert((a >= b).native_value() == true);
	assert((b < a).native_value() == true);
	assert((b <= a).native_value() == true);
	assert((a == scpp::int_t(7)).native_value() == true);
	assert((a != b).native_value() == true);
}

// Verifies floating-point arithmetic plus configured mixed int_t/float_t behavior.
static void test_float_operations() {
	const scpp::float_t x(2.5);
	const scpp::float_t y(0.5);
	const scpp::int_t n(2);

	assert((+x).native_value() == 2.5);
	assert((-y).native_value() == -0.5);
	assert((x + y).native_value() == 3.0);
	assert((x - y).native_value() == 2.0);
	assert((x * y).native_value() == 1.25);
	assert((x / y).native_value() == 5.0);

	assert((n + x).native_value() == 4.5);
	assert((x + n).native_value() == 4.5);
	assert((n * x).native_value() == 5.0);
	assert((x * n).native_value() == 5.0);
	assert((n < x).native_value() == true);
	assert((x > n).native_value() == true);
	assert((x == scpp::float_t(2.5)).native_value() == true);
	assert((x != y).native_value() == true);
}

// Verifies the currently configured named cast surface.
static void test_named_casts() {
	assert(scpp::cast<scpp::bool_t>(scpp::int_t(0)).native_value() == false);
	assert(scpp::cast<scpp::bool_t>(scpp::int_t(9)).native_value() == true);
	assert(scpp::cast<scpp::bool_t>(scpp::float_t(0.0)).native_value() == false);
	assert(scpp::cast<scpp::bool_t>(scpp::float_t(-1.25)).native_value() == true);
	assert(scpp::cast<scpp::int_t>(scpp::float_t(3.75)).native_value() == 3);
	assert(scpp::cast<scpp::string_t>(scpp::int_t(42)).native_value() == "42");
	assert(scpp::cast<scpp::string_t>(scpp::float_t(3.5)).native_value().starts_with("3.500000"));
}

// Verifies the PHP coercion layer used by php::echo.
static void test_php_to_string_coercions() {
	assert(scpp::php::to_string(scpp::string_t("abc")).native_value() == "abc");
	assert(scpp::php::to_string(scpp::int_t(42)).native_value() == "42");
	assert(scpp::php::to_string(scpp::bool_t(true)).native_value() == "1");
	assert(scpp::php::to_string(scpp::bool_t(false)).native_value() == "");
	assert(scpp::php::to_string(scpp::null).native_value() == "");
	assert(scpp::php::to_string(scpp::nullopt).native_value() == "");
	assert(scpp::php::to_string(scpp::null_ptr).native_value() == "");

	const scpp::nullable<scpp::int_t> present_int(scpp::int_t(7));
	const scpp::nullable<scpp::int_t> empty_int(scpp::null);
	const scpp::nullable<scpp::bool_t> present_bool(scpp::bool_t(true));
	const scpp::nullable<scpp::string_t> present_string(scpp::string_t("ok"));

	assert(scpp::php::to_string(present_int).native_value() == "7");
	assert(scpp::php::to_string(empty_int).native_value() == "");
	assert(scpp::php::to_string(present_bool).native_value() == "1");
	assert(scpp::php::to_string(present_string).native_value() == "ok");
}

// Verifies basic wrapped string and vector behavior needed by generated code.
static void test_containers_and_strings() {
	scpp::string_t left("Hello");
	const scpp::string_t right(", world");
	left.append(right);
	assert(left.native_value() == "Hello, world");
	assert((left == scpp::string_t("Hello, world")).native_value() == true);
	assert(left.empty().native_value() == false);

	scpp::vector_t<scpp::int_t> values;
	assert(values.empty().native_value() == true);
	values.append(scpp::int_t(4));
	values.append(scpp::int_t(9));
	assert(values.size() == 2);
	assert(values.at(0).native_value() == 4);
	assert(values.index(1).native_value() == 9);
}

int main() {
	test_bool_operations();
	test_int_operations();
	test_float_operations();
	test_named_casts();
	test_php_to_string_coercions();
	test_containers_and_strings();
	return 0;
}
