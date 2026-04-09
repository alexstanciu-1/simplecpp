#include "test_common.hpp"

namespace {

static void assert_mixed_int(const scpp::mixed_t &value, std::int64_t expected) {
	assert(value.kind() == scpp::mixed_t::kind_t::int_v);
	assert(value.int_value().native_value() == expected);
}

static void assert_mixed_false(const scpp::mixed_t &value) {
	assert(value.kind() == scpp::mixed_t::kind_t::bool_v);
	assert(value.bool_value().native_value() == false);
}

static void test_substr_without_length() {
	assert(scpp::php::substr(scpp::string_t("hello"), scpp::int_t(0)).native_value() == "hello");
	assert(scpp::php::substr(scpp::string_t("hello"), scpp::int_t(1)).native_value() == "ello");
	assert(scpp::php::substr(scpp::string_t("hello"), scpp::int_t(5)).native_value() == "");
	assert(scpp::php::substr(scpp::string_t("hello"), scpp::int_t(99)).native_value() == "");
}

static void test_substr_with_positive_length() {
	assert(scpp::php::substr(scpp::string_t("hello"), scpp::int_t(1), scpp::int_t(3)).native_value() == "ell");
	assert(scpp::php::substr(scpp::string_t("hello"), scpp::int_t(1), scpp::int_t(99)).native_value() == "ello");
	assert(scpp::php::substr(scpp::string_t("hello"), scpp::int_t(2), scpp::int_t(0)).native_value() == "");
}

static void test_substr_with_negative_offset() {
	assert(scpp::php::substr(scpp::string_t("hello"), scpp::int_t(-1)).native_value() == "o");
	assert(scpp::php::substr(scpp::string_t("hello"), scpp::int_t(-2)).native_value() == "lo");
	assert(scpp::php::substr(scpp::string_t("hello"), scpp::int_t(-99)).native_value() == "hello");
}

static void test_substr_with_negative_length() {
	assert(scpp::php::substr(scpp::string_t("hello"), scpp::int_t(0), scpp::int_t(-1)).native_value() == "hell");
	assert(scpp::php::substr(scpp::string_t("hello"), scpp::int_t(1), scpp::int_t(-1)).native_value() == "ell");
	assert(scpp::php::substr(scpp::string_t("hello"), scpp::int_t(0), scpp::int_t(-99)).native_value() == "");
}

static void test_substr_with_combined_negative_inputs() {
	assert(scpp::php::substr(scpp::string_t("hello"), scpp::int_t(-4), scpp::int_t(2)).native_value() == "el");
	assert(scpp::php::substr(scpp::string_t("hello"), scpp::int_t(-4), scpp::int_t(-1)).native_value() == "ell");
}

static void test_strlen() {
	assert(scpp::php::strlen(scpp::string_t("")).native_value() == 0);
	assert(scpp::php::strlen(scpp::string_t("hello")).native_value() == 5);
}

static void test_strpos() {
	assert_mixed_int(scpp::php::strpos(scpp::string_t("abcabc"), scpp::string_t("a")), 0);
	assert_mixed_int(scpp::php::strpos(scpp::string_t("abcabc"), scpp::string_t("a"), scpp::int_t(1)), 3);
	assert_mixed_int(scpp::php::strpos(scpp::string_t("abcabc"), scpp::string_t("a"), scpp::int_t(-3)), 3);
	assert_mixed_int(scpp::php::strpos(scpp::string_t("abc"), scpp::string_t("")), 0);
	assert_mixed_false(scpp::php::strpos(scpp::string_t("abcabc"), scpp::string_t("z")));
	scpp_test::expect_throw<scpp::php::ValueError>([]() {
		static_cast<void>(scpp::php::strpos(scpp::string_t("abcabc"), scpp::string_t("a"), scpp::int_t(99)));
	});
}

static void test_strrpos() {
	assert_mixed_int(scpp::php::strrpos(scpp::string_t("abcabc"), scpp::string_t("a")), 3);
	assert_mixed_int(scpp::php::strrpos(scpp::string_t("abcabc"), scpp::string_t("a"), scpp::int_t(1)), 3);
	assert_mixed_int(scpp::php::strrpos(scpp::string_t("abcabc"), scpp::string_t("b"), scpp::int_t(-3)), 1);
	assert_mixed_int(scpp::php::strrpos(scpp::string_t("abc"), scpp::string_t("")), 3);
	assert_mixed_false(scpp::php::strrpos(scpp::string_t("abcabc"), scpp::string_t("a"), scpp::int_t(4)));
	scpp_test::expect_throw<scpp::php::ValueError>([]() {
		static_cast<void>(scpp::php::strrpos(scpp::string_t("abcabc"), scpp::string_t("a"), scpp::int_t(-99)));
	});
}

static void test_case_helpers() {
	assert(scpp::php::strtolower(scpp::string_t("AbC123")).native_value() == "abc123");
	assert(scpp::php::strtoupper(scpp::string_t("AbC123")).native_value() == "ABC123");
	assert(scpp::php::lcfirst(scpp::string_t("Hello")).native_value() == "hello");
	assert(scpp::php::lcfirst(scpp::string_t("")).native_value() == "");
	assert(scpp::php::ucfirst(scpp::string_t("hello")).native_value() == "Hello");
	assert(scpp::php::ucfirst(scpp::string_t("")).native_value() == "");
}

static void test_prefix_suffix_helpers() {
	assert(scpp::php::str_starts_with(scpp::string_t("hello"), scpp::string_t("he")).native_value() == true);
	assert(scpp::php::str_starts_with(scpp::string_t("hello"), scpp::string_t("" )).native_value() == true);
	assert(scpp::php::str_starts_with(scpp::string_t("hello"), scpp::string_t("lo")).native_value() == false);
	assert(scpp::php::str_ends_with(scpp::string_t("hello"), scpp::string_t("lo")).native_value() == true);
	assert(scpp::php::str_ends_with(scpp::string_t("hello"), scpp::string_t("" )).native_value() == true);
	assert(scpp::php::str_ends_with(scpp::string_t("hello"), scpp::string_t("he")).native_value() == false);
}


static void test_str_replace() {
	assert(scpp::php::str_replace(scpp::string_t("a"), scpp::string_t("X"), scpp::string_t("banana")).native_value() == "bXnXnX");
	assert(scpp::php::str_replace(scpp::string_t("na"), scpp::string_t("_"), scpp::string_t("banana")).native_value() == "ba__");
	assert(scpp::php::str_replace(scpp::string_t(""), scpp::string_t("X"), scpp::string_t("banana")).native_value() == "banana");
	assert(scpp::php::str_replace(scpp::string_t("zz"), scpp::string_t("X"), scpp::string_t("banana")).native_value() == "banana");
	assert(scpp::php::str_replace(scpp::string_t("aa"), scpp::string_t("b"), scpp::string_t("aaaa")).native_value() == "bb");
}

static void test_str_pad() {
	assert(scpp::php::str_pad(scpp::string_t("abc"), scpp::int_t(5)).native_value() == "abc  ");
	assert(scpp::php::str_pad(scpp::string_t("abc"), scpp::int_t(5), scpp::string_t("_")).native_value() == "abc__");
	assert(scpp::php::str_pad(scpp::string_t("abc"), scpp::int_t(5), scpp::string_t("_"), scpp::php::STR_PAD_LEFT).native_value() == "__abc");
	assert(scpp::php::str_pad(scpp::string_t("abc"), scpp::int_t(8), scpp::string_t("_"), scpp::php::STR_PAD_BOTH).native_value() == "__abc___");
	assert(scpp::php::str_pad(scpp::string_t("abc"), scpp::int_t(10), scpp::string_t("01"), scpp::php::STR_PAD_BOTH).native_value() == "010abc0101");
	assert(scpp::php::str_pad(scpp::string_t("abc"), scpp::int_t(2), scpp::string_t("_")).native_value() == "abc");
	scpp_test::expect_throw<scpp::php::ValueError>([]() {
		static_cast<void>(scpp::php::str_pad(scpp::string_t("abc"), scpp::int_t(5), scpp::string_t("")));
	});
	scpp_test::expect_throw<scpp::php::ValueError>([]() {
		static_cast<void>(scpp::php::str_pad(scpp::string_t("abc"), scpp::int_t(5), scpp::string_t("_"), scpp::int_t(99)));
	});
}

static void test_trim_family_default_mask() {
	assert(scpp::php::trim(scpp::string_t(" \n\thello\r\n")).native_value() == "hello");
	assert(scpp::php::ltrim(scpp::string_t(" \n\thello")).native_value() == "hello");
	assert(scpp::php::rtrim(scpp::string_t("hello\r\n\t ")).native_value() == "hello");
}

static void test_trim_family_custom_mask() {
	assert(scpp::php::trim(scpp::string_t("--hello--"), scpp::string_t("-")).native_value() == "hello");
	assert(scpp::php::trim(scpp::string_t("xyzabczyx"), scpp::string_t("xyz")).native_value() == "abc");
	assert(scpp::php::ltrim(scpp::string_t("==value"), scpp::string_t("=")).native_value() == "value");
	assert(scpp::php::rtrim(scpp::string_t("value=="), scpp::string_t("=")).native_value() == "value");
	assert(scpp::php::trim(scpp::string_t("abc"), scpp::string_t("" )).native_value() == "abc");
}

static void test_substr_compare() {
	assert(scpp::php::substr_compare(scpp::string_t("abcdef"), scpp::string_t("cde"), scpp::int_t(2)).native_value() > 0);
	assert(scpp::php::substr_compare(scpp::string_t("abcdef"), scpp::string_t("CdE"), scpp::int_t(2), scpp::int_t(3), scpp::bool_t(true)).native_value() == 0);
	assert(scpp::php::substr_compare(scpp::string_t("abcdef"), scpp::string_t("de"), scpp::int_t(-3), scpp::int_t(2)).native_value() == 0);
	assert(scpp::php::substr_compare(scpp::string_t("abcdef"), scpp::string_t("xyz"), scpp::int_t(99)).native_value() < 0);
	assert(scpp::php::substr_compare(scpp::string_t("abcdef"), scpp::string_t("xyz"), scpp::int_t(2), scpp::int_t(-99)).native_value() == 0);
	assert(scpp::php::substr_compare(scpp::string_t("abcdef"), scpp::string_t("dEf"), scpp::int_t(-3), scpp::int_t(3), scpp::bool_t(false)).native_value() != 0);
}

static void test_substr_replace() {
	assert(scpp::php::substr_replace(scpp::string_t("abcdef"), scpp::string_t("XYZ"), scpp::int_t(2)).native_value() == "abXYZ");
	assert(scpp::php::substr_replace(scpp::string_t("abcdef"), scpp::string_t("XYZ"), scpp::int_t(2), scpp::int_t(3)).native_value() == "abXYZf");
	assert(scpp::php::substr_replace(scpp::string_t("abcdef"), scpp::string_t("XYZ"), scpp::int_t(-3), scpp::int_t(2)).native_value() == "abcXYZf");
	assert(scpp::php::substr_replace(scpp::string_t("abcdef"), scpp::string_t("XYZ"), scpp::int_t(99), scpp::int_t(2)).native_value() == "abcdefXYZ");
	assert(scpp::php::substr_replace(scpp::string_t("abcdef"), scpp::string_t("XYZ"), scpp::int_t(-99), scpp::int_t(2)).native_value() == "XYZcdef");
	assert(scpp::php::substr_replace(scpp::string_t("abcdef"), scpp::string_t("XYZ"), scpp::int_t(2), scpp::int_t(-1)).native_value() == "abXYZf");
	assert(scpp::php::substr_replace(scpp::string_t("abcdef"), scpp::string_t("XYZ"), scpp::int_t(4), scpp::int_t(-1)).native_value() == "abcdXYZf");
	assert(scpp::php::substr_replace(scpp::string_t("abcdef"), scpp::string_t("XYZ"), scpp::int_t(2), scpp::int_t(-99)).native_value() == "abXYZcdef");
	assert(scpp::php::substr_replace(scpp::string_t("abcdef"), scpp::string_t("XYZ"), scpp::int_t(6)).native_value() == "abcdefXYZ");
	assert(scpp::php::substr_replace(scpp::string_t("abcdef"), scpp::string_t("XYZ"), scpp::int_t(6), scpp::int_t(0)).native_value() == "abcdefXYZ");
	assert(scpp::php::substr_replace(scpp::string_t("abcdef"), scpp::string_t("XYZ"), scpp::int_t(0), scpp::int_t(0)).native_value() == "XYZabcdef");
	assert(scpp::php::substr_replace(scpp::string_t("abcdef"), scpp::string_t("XYZ"), scpp::int_t(0), scpp::int_t(-6)).native_value() == "XYZabcdef");
	assert(scpp::php::substr_replace(scpp::string_t("abcdef"), scpp::string_t("XYZ"), scpp::int_t(0), scpp::int_t(-7)).native_value() == "XYZabcdef");
	assert(scpp::php::substr_replace(scpp::string_t("abcdef"), scpp::string_t(""), scpp::int_t(2), scpp::int_t(3)).native_value() == "abf");
}

} // namespace

int main() {
	test_substr_without_length();
	test_substr_with_positive_length();
	test_substr_with_negative_offset();
	test_substr_with_negative_length();
	test_substr_with_combined_negative_inputs();
	test_strlen();
	test_strpos();
	test_strrpos();
	test_case_helpers();
	test_prefix_suffix_helpers();
	test_str_replace();
	test_str_pad();
	test_trim_family_default_mask();
	test_trim_family_custom_mask();
	test_substr_compare();
	test_substr_replace();
	return 0;
}
