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

static void assert_mixed_string(const scpp::mixed_t &value, const std::string &expected) {
	assert(value.kind() == scpp::mixed_t::kind_t::string_v);
	assert(value.get_string().native_value() == expected);
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


static void test_explode() {
	const auto split_default = scpp::php::explode(scpp::string_t(","), scpp::string_t("a,b,c"));
	const auto &split_default_table = split_default.get_hash();
	assert(split_default_table.size() == 3);
	assert(split_default_table.at(scpp::int_t(0)).get_string().native_value() == "a");
	assert(split_default_table.at(scpp::int_t(1)).get_string().native_value() == "b");
	assert(split_default_table.at(scpp::int_t(2)).get_string().native_value() == "c");

	const auto split_zero = scpp::php::explode(scpp::string_t(","), scpp::string_t("a,b,c"), scpp::int_t(0));
	assert(split_zero.get_hash().size() == 1);
	assert(split_zero.get_hash().at(scpp::int_t(0)).get_string().native_value() == "a,b,c");

	const auto split_two = scpp::php::explode(scpp::string_t(","), scpp::string_t("a,b,c"), scpp::int_t(2));
	assert(split_two.get_hash().size() == 2);
	assert(split_two.get_hash().at(scpp::int_t(0)).get_string().native_value() == "a");
	assert(split_two.get_hash().at(scpp::int_t(1)).get_string().native_value() == "b,c");

	const auto split_negative = scpp::php::explode(scpp::string_t(","), scpp::string_t("a,b,c"), scpp::int_t(-1));
	assert(split_negative.get_hash().size() == 2);
	assert(split_negative.get_hash().at(scpp::int_t(0)).get_string().native_value() == "a");
	assert(split_negative.get_hash().at(scpp::int_t(1)).get_string().native_value() == "b");

	const auto split_negative_empty = scpp::php::explode(scpp::string_t(","), scpp::string_t("abc"), scpp::int_t(-1));
	assert(split_negative_empty.get_hash().size() == 0);

	bool separator_error = false;
	try {
		static_cast<void>(scpp::php::explode(scpp::string_t(""), scpp::string_t("abc")));
	} catch (const scpp::php::ValueError &) {
		separator_error = true;
	}
	assert(separator_error);
}

static void test_hex_bin_helpers() {
	assert_mixed_string(scpp::php::hex2bin(scpp::string_t("")), "");
	assert_mixed_string(scpp::php::hex2bin(scpp::string_t("48656c6c6f")), "Hello");
	assert_mixed_string(scpp::php::hex2bin(scpp::string_t("48656C6C6F")), "Hello");
	assert_mixed_false(scpp::php::hex2bin(scpp::string_t("0")));
	assert_mixed_false(scpp::php::hex2bin(scpp::string_t("zz")));
	assert_mixed_false(scpp::php::hex2bin(scpp::string_t("0g")));

	assert(scpp::php::bin2hex(scpp::string_t("")).native_value() == "");
	assert(scpp::php::bin2hex(scpp::string_t("Hello")).native_value() == "48656c6c6f");
	std::string raw_bytes;
	raw_bytes.push_back(static_cast<char>(0x00));
	raw_bytes.push_back(static_cast<char>(0xff));
	raw_bytes.push_back('A');
	const auto raw = scpp::string_t(raw_bytes);
	assert(scpp::php::bin2hex(raw).native_value() == "00ff41");
	assert_mixed_string(scpp::php::hex2bin(scpp::string_t("00ff41")), raw_bytes);
}

static void test_implode() {
	scpp::hash_t<scpp::string_t> table;
	table.append(scpp::string_t("a"));
	table.append(scpp::string_t("b"));
	table.append(scpp::string_t("c"));
	assert(scpp::php::implode(scpp::string_t(","), table).native_value() == "a,b,c");

	scpp::hash_t<scpp::string_t> assoc;
	assoc.set(scpp::string_t("first"), scpp::string_t("red"));
	assoc.set(scpp::string_t("second"), scpp::string_t("green"));
	assoc.set(scpp::int_t(7), scpp::string_t("blue"));
	assert(scpp::php::implode(scpp::string_t("|"), assoc).native_value() == "red|green|blue");

	scpp::vector_t<scpp::string_t> pieces;
	pieces.append(scpp::string_t("x"));
	pieces.append(scpp::string_t("y"));
	pieces.append(scpp::string_t("z"));
	assert(scpp::php::implode(scpp::string_t("-"), pieces).native_value() == "x-y-z");

	scpp::hash_t<scpp::string_t> empty_table;
	assert(scpp::php::implode(scpp::string_t(","), empty_table).native_value() == "");
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


static void test_number_format() {
	assert(scpp::php::number_format(scpp::int_t(1234)).native_value() == "1,234");
	assert(scpp::php::number_format(scpp::int_t(1234), scpp::int_t(2)).native_value() == "1,234.00");
	assert(scpp::php::number_format(scpp::float_t(1234.56), scpp::int_t(2)).native_value() == "1,234.56");
	assert(scpp::php::number_format(scpp::float_t(-1234.56), scpp::int_t(1)).native_value() == "-1,234.6");
	assert(scpp::php::number_format(scpp::float_t(1234.5), scpp::int_t(0)).native_value() == "1,235");
	assert(scpp::php::number_format(scpp::float_t(12.0), scpp::int_t(3)).native_value() == "12.000");
	assert(scpp::php::number_format(scpp::int_t(1234), scpp::int_t(2), scpp::string_t(","), scpp::string_t(".")).native_value() == "1.234,00");
	assert(scpp::php::number_format(scpp::mixed_t(scpp::int_t(9876543)), scpp::int_t(0)).native_value() == "9,876,543");
	assert(scpp::php::number_format(scpp::int_t(1234), scpp::int_t(-3)).native_value() == "1,000");
	assert(scpp::php::number_format(scpp::float_t(1234.56), scpp::int_t(-2)).native_value() == "1,200");
	scpp_test::expect_throw<scpp::php::TypeError>([]() {
		static_cast<void>(scpp::php::number_format(scpp::string_t("1234.5"), scpp::int_t(2)));
	});
	scpp_test::expect_throw<scpp::php::TypeError>([]() {
		static_cast<void>(scpp::php::number_format(scpp::mixed_t(scpp::string_t("42.5")), scpp::int_t(1)));
	});
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
	test_explode();
	test_hex_bin_helpers();
	test_implode();
	test_str_replace();
	test_str_pad();
	test_trim_family_default_mask();
	test_trim_family_custom_mask();
	test_substr_compare();
	test_substr_replace();
	test_number_format();
	return 0;
}
