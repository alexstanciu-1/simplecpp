#include "test_common.hpp"

#include "lang/php/php_regex.hpp"
#include "modules/regex/regex.hpp"

namespace {

static void test_quote_without_delimiter() {
	assert(scpp::regex::jit_available().native_value() == true || scpp::regex::jit_available().native_value() == false);
	assert(scpp::php::preg_jit_available().native_value() == scpp::regex::jit_available().native_value());

	const auto quoted = scpp::regex::quote(scpp::string_t("a+b?.(test)[x]"));
	assert(quoted.native_value() == "a\\+b\\?\\.\\(test\\)\\[x\\]");
}

static void test_quote_with_delimiter() {
	const auto quoted = scpp::php::preg_quote(scpp::string_t("a/b#c"), scpp::string_t("/"));
	assert(quoted.native_value() == "a\\/b\\#c");
}

static void test_strict_match_core() {
	const auto matched = scpp::regex::match(
		scpp::string_t("/(ab+)-(cd+)/i"),
		scpp::string_t("xxAbb-cDDyy"));
	assert(matched.has_value().native_value());
	assert(matched.value().size() == 3u);
	assert(matched.value()[0].native_value() == "Abb-cDD");
	assert(matched.value()[1].native_value() == "Abb");
	assert(matched.value()[2].native_value() == "cDD");

	const auto no_match = scpp::regex::match(scpp::string_t("/z+/"), scpp::string_t("abc"));
	assert(no_match.has_value().native_value());
	assert(no_match.value().empty().native_value());

	const auto invalid = scpp::regex::match(scpp::string_t("/unterminated"), scpp::string_t("abc"));
	assert(!invalid.has_value().native_value());

	const auto named = scpp::regex::match_named(
		scpp::string_t("/(?<left>ab+)-(?<right>cd+)/i"),
		scpp::string_t("xxAbb-cDDyy"));
	assert(named.has_value().native_value());
	assert(named.value()[scpp::string_t("0")].native_value() == "Abb-cDD");
	assert(named.value()[scpp::string_t("1")].native_value() == "Abb");
	assert(named.value()[scpp::string_t("2")].native_value() == "cDD");
	assert(named.value()[scpp::string_t("left")].native_value() == "Abb");
	assert(named.value()[scpp::string_t("right")].native_value() == "cDD");
}

static void test_legacy_preg_match_wrapper() {
	scpp::mixed_t matches;
	const auto matched = scpp::php::preg_match(
		scpp::string_t("/(?<left>ab+)-(?<right>cd+)/i"),
		scpp::string_t("xxAbb-cDDyy"),
		matches);
	assert(matched.has_value().native_value());
	assert(matched.value().native_value() == 1);
	assert(matches.get_hash().size() == 5u);
	assert(matches.get_hash()[scpp::int_t(0)].get_string().native_value() == "Abb-cDD");
	assert(matches.get_hash()[scpp::string_t("left")].get_string().native_value() == "Abb");
	assert(matches.get_hash()[scpp::string_t("right")].get_string().native_value() == "cDD");

	const auto no_match = scpp::php::preg_match(scpp::string_t("/z+/"), scpp::string_t("abc"));
	assert(no_match.has_value().native_value());
	assert(no_match.value().native_value() == 0);

	const auto invalid = scpp::php::preg_match(scpp::string_t("/unterminated"), scpp::string_t("abc"));
	assert(!invalid.has_value().native_value());
}

static void test_strict_match_all_core() {
	const auto counted = scpp::regex::match_all(
		scpp::string_t("/(ab)(c?)/i"),
		scpp::string_t("ab ABC xxab"));
	assert(counted.has_value().native_value());
	assert(counted.value().size() == 3u);
	assert(counted.value()[0].size() == 3u);
	assert(counted.value()[0][0].native_value() == "ab");
	assert(counted.value()[1][0].native_value() == "ABC");

	const auto named = scpp::regex::match_all_named(
		scpp::string_t("/(?<head>ab)(?<tail>c?)/i"),
		scpp::string_t("ab ABC xxab"));
	assert(named.has_value().native_value());
	assert(named.value().size() == 3u);
	assert(named.value()[0][scpp::string_t("head")].native_value() == "ab");
	assert(named.value()[1][scpp::string_t("head")].native_value() == "AB");
	assert(named.value()[1][scpp::string_t("tail")].native_value() == "C");
}

static void test_legacy_preg_match_all_wrapper() {
	scpp::mixed_t matches;
	const auto counted = scpp::php::preg_match_all(
		scpp::string_t("/(?<head>ab)(?<tail>c?)/i"),
		scpp::string_t("ab ABC xxab"),
		matches);
	assert(counted.has_value().native_value());
	assert(counted.value().native_value() == 3);
	assert(matches.get_hash().size() == 3u);
	assert(matches.get_hash()[scpp::int_t(0)].get_hash()[scpp::int_t(0)].get_string().native_value() == "ab");
	assert(matches.get_hash()[scpp::int_t(0)].get_hash()[scpp::string_t("head")].get_string().native_value() == "ab");
	assert(matches.get_hash()[scpp::int_t(1)].get_hash()[scpp::string_t("tail")].get_string().native_value() == "C");
}

static void test_replace_variants() {
	const auto replaced = scpp::regex::replace(
		scpp::string_t("/ab+/i"),
		scpp::string_t("X"),
		scpp::string_t("ab xx ABB yy"));
	assert(replaced.has_value().native_value());
	assert(replaced.value().native_value() == "X xx X yy");

	const auto limited = scpp::regex::replace(
		scpp::string_t("/,/"),
		scpp::string_t("|"),
		scpp::string_t("a,b,c"),
		scpp::int_t(2));
	assert(limited.has_value().native_value());
	assert(limited.value().native_value() == "a|b|c");

	const auto noop = scpp::regex::replace(
		scpp::string_t("/z+/"),
		scpp::string_t("X"),
		scpp::string_t("abc"));
	assert(noop.has_value().native_value());
	assert(noop.value().native_value() == "abc");

	const auto capture_replaced = scpp::regex::replace(
		scpp::string_t("/(ab+)-(cd+)/i"),
		scpp::string_t("<$2:$1:${0}:\\2>"),
		scpp::string_t("xxAbb-cDDyy"));
	assert(capture_replaced.has_value().native_value());
	assert(capture_replaced.value().native_value() == "xx<cDD:Abb:Abb-cDD:cDD>yy");

	scpp::int_t replace_count(0);
	const auto counted = scpp::regex::replace(
		scpp::string_t("/ab+/i"),
		scpp::string_t("X"),
		scpp::string_t("ab xx ABB yy"),
		replace_count);
	assert(counted.has_value().native_value());
	assert(counted.value().native_value() == "X xx X yy");
	assert(replace_count.native_value() == 2);
}

static void test_replace_callback_strict_and_legacy() {
	const std::function<scpp::string_t(scpp::vector_t<scpp::string_t>)> strict_callback =
		[](scpp::vector_t<scpp::string_t> values) -> scpp::string_t {
			return scpp::string_t("[" + values[1].native_value() + ":" + values[2].native_value() + "]");
		};
	const auto strict = scpp::regex::replace_callback(
		scpp::string_t("/(ab+)-(cd+)/i"),
		strict_callback,
		scpp::string_t("xxAbb-cDDyy"));
	assert(strict.has_value().native_value());
	assert(strict.value().native_value() == "xx[Abb:cDD]yy");

	const std::function<scpp::string_t(scpp::mixed_t)> legacy_callback =
		[](scpp::mixed_t values) -> scpp::string_t {
			return scpp::string_t("<" + values.get_hash()[scpp::int_t(1)].get_string().native_value() + ">");
		};
	const auto legacy = scpp::php::preg_replace_callback(
		scpp::string_t("/(ab+)/i"),
		legacy_callback,
		scpp::string_t("ab xx ABB"));
	assert(legacy.has_value().native_value());
	assert(legacy.value().native_value() == "<ab> xx <ABB>");

	scpp::int_t callback_count(0);
	const auto counted_legacy = scpp::php::preg_replace_callback(
		scpp::string_t("/(ab+)/i"),
		legacy_callback,
		scpp::string_t("ab xx ABB"),
		callback_count);
	assert(counted_legacy.has_value().native_value());
	assert(counted_legacy.value().native_value() == "<ab> xx <ABB>");
	assert(callback_count.native_value() == 2);
}

static void test_replace_callback_array_strict_and_legacy() {
	scpp::hash_t<std::function<scpp::string_t(scpp::vector_t<scpp::string_t>)>, scpp::string_t> strict_callbacks;
	strict_callbacks.set(
		scpp::string_t("/ab+/i"),
		std::function<scpp::string_t(scpp::vector_t<scpp::string_t>)>(
			[](scpp::vector_t<scpp::string_t> values) -> scpp::string_t {
				return scpp::string_t("[" + values[0].native_value() + "]");
			}));
	strict_callbacks.set(
		scpp::string_t("/\\[(ab+)\\]/i"),
		std::function<scpp::string_t(scpp::vector_t<scpp::string_t>)>(
			[](scpp::vector_t<scpp::string_t> values) -> scpp::string_t {
				return scpp::string_t("<" + values[1].native_value() + ">");
			}));

	const auto strict = scpp::regex::replace_callback_array(
		strict_callbacks,
		scpp::string_t("ab xx ABB"));
	assert(strict.has_value().native_value());
	assert(strict.value().native_value() == "<ab> xx <ABB>");

	scpp::hash_t<std::function<scpp::string_t(scpp::mixed_t)>, scpp::string_t> legacy_callbacks;
	legacy_callbacks.set(
		scpp::string_t("/(ab+)/i"),
		std::function<scpp::string_t(scpp::mixed_t)>(
			[](scpp::mixed_t values) -> scpp::string_t {
				return scpp::string_t("[" + values.get_hash()[scpp::int_t(1)].get_string().native_value() + "]");
			}));
	legacy_callbacks.set(
		scpp::string_t("/\\[([^\\]]+)\\]/"),
		std::function<scpp::string_t(scpp::mixed_t)>(
			[](scpp::mixed_t values) -> scpp::string_t {
				return scpp::string_t("<" + values.get_hash()[scpp::int_t(1)].get_string().native_value() + ">");
			}));

	const auto legacy = scpp::php::preg_replace_callback_array(
		legacy_callbacks,
		scpp::string_t("ab xx ABB"));
	assert(legacy.has_value().native_value());
	assert(legacy.value().native_value() == "<ab> xx <ABB>");

	scpp::int_t callback_array_count(0);
	const auto counted_legacy = scpp::php::preg_replace_callback_array(
		legacy_callbacks,
		scpp::string_t("ab xx ABB"),
		callback_array_count);
	assert(counted_legacy.has_value().native_value());
	assert(counted_legacy.value().native_value() == "<ab> xx <ABB>");
	assert(callback_array_count.native_value() == 4);
}

static void test_grep_and_filter_strict_core() {
	scpp::vector_t<scpp::string_t> input;
	input.append(scpp::string_t("ab"));
	input.append(scpp::string_t("xx"));
	input.append(scpp::string_t("ABc"));

	const auto grepped = scpp::regex::grep(scpp::string_t("/ab/i"), input);
	assert(grepped.has_value().native_value());
	assert(grepped.value().size() == 2u);
	assert(grepped.value()[0].native_value() == "ab");
	assert(grepped.value()[1].native_value() == "ABc");

	const auto filtered = scpp::regex::filter(
		scpp::string_t("/ab/i"),
		scpp::string_t("X"),
		input);
	assert(filtered.has_value().native_value());
	assert(filtered.value().size() == 2u);
	assert(filtered.value()[0].native_value() == "X");
	assert(filtered.value()[1].native_value() == "Xc");
}

static void test_grep_and_filter_legacy_wrapper() {
	auto grep_input = scpp::unique<scpp::hash_t<scpp::mixed_t>>();
	static_cast<void>(grep_input->append(scpp::mixed_t(scpp::string_t("ab"))));
	static_cast<void>(grep_input->append(scpp::mixed_t(scpp::string_t("xx"))));
	static_cast<void>(grep_input->append(scpp::mixed_t(scpp::string_t("ABc"))));

	const auto grepped = scpp::php::preg_grep(
		scpp::string_t("/ab/i"),
		scpp::mixed_t(std::move(grep_input)));
	assert(grepped.has_value().native_value());
	assert(grepped.value().get_hash().size() == 2u);
	assert(grepped.value().get_hash()[scpp::int_t(0)].get_string().native_value() == "ab");
	assert(grepped.value().get_hash()[scpp::int_t(2)].get_string().native_value() == "ABc");

	auto filter_input = scpp::unique<scpp::hash_t<scpp::mixed_t>>();
	filter_input->set(scpp::string_t("first"), scpp::mixed_t(scpp::string_t("ab")));
	filter_input->set(scpp::string_t("skip"), scpp::mixed_t(scpp::string_t("xx")));
	filter_input->set(scpp::string_t("third"), scpp::mixed_t(scpp::string_t("ABc")));

	const auto filtered = scpp::php::preg_filter(
		scpp::string_t("/ab/i"),
		scpp::string_t("X"),
		scpp::mixed_t(std::move(filter_input)));
	assert(filtered.has_value().native_value());
	assert(filtered.value().get_hash().size() == 2u);
	assert(filtered.value().get_hash()[scpp::string_t("first")].get_string().native_value() == "X");
	assert(filtered.value().get_hash()[scpp::string_t("third")].get_string().native_value() == "Xc");

	auto counted_filter_input = scpp::unique<scpp::hash_t<scpp::mixed_t>>();
	static_cast<void>(counted_filter_input->append(scpp::mixed_t(scpp::string_t("ab"))));
	static_cast<void>(counted_filter_input->append(scpp::mixed_t(scpp::string_t("ABab"))));
	scpp::int_t filter_count(0);
	const auto counted_filtered = scpp::php::preg_filter(
		scpp::string_t("/ab/i"),
		scpp::string_t("X"),
		scpp::mixed_t(std::move(counted_filter_input)),
		filter_count);
	assert(counted_filtered.has_value().native_value());
	assert(filter_count.native_value() == 3);
}

static void test_split_strict_and_legacy() {
	const auto split = scpp::regex::split(scpp::string_t("/,/"), scpp::string_t("a,b,"));
	assert(split.has_value().native_value());
	assert(split.value().size() == 3u);
	assert(split.value()[0].native_value() == "a");
	assert(split.value()[2].native_value() == "");

	const auto no_empty = scpp::regex::split(
		scpp::string_t("/,+/"),
		scpp::string_t(",a,,b,"),
		scpp::int_t(-1),
		scpp::int_t(1));
	assert(no_empty.has_value().native_value());
	assert(no_empty.value().size() == 2u);
	assert(no_empty.value()[0].native_value() == "a");
	assert(no_empty.value()[1].native_value() == "b");

	const auto with_delims = scpp::regex::split(
		scpp::string_t("/([,:])/"),
		scpp::string_t("a,b:c"),
		scpp::int_t(-1),
		scpp::int_t(2));
	assert(with_delims.has_value().native_value());
	assert(with_delims.value().size() == 5u);
	assert(with_delims.value()[0].native_value() == "a");
	assert(with_delims.value()[1].native_value() == ",");
	assert(with_delims.value()[2].native_value() == "b");
	assert(with_delims.value()[3].native_value() == ":");
	assert(with_delims.value()[4].native_value() == "c");

	const auto legacy = scpp::php::preg_split(scpp::string_t("/,/"), scpp::string_t("a,b,c"), scpp::int_t(2));
	assert(legacy.has_value().native_value());
	assert(legacy.value().get_hash().size() == 2u);
	assert(legacy.value().get_hash()[scpp::int_t(1)].get_string().native_value() == "b,c");

	const auto legacy_flags = scpp::php::preg_split(
		scpp::string_t("/([,:])/"),
		scpp::string_t("a,b:c"),
		scpp::int_t(-1),
		scpp::int_t(2));
	assert(legacy_flags.has_value().native_value());
	assert(legacy_flags.value().get_hash().size() == 5u);
	assert(legacy_flags.value().get_hash()[scpp::int_t(1)].get_string().native_value() == ",");
}

} // namespace

int main() {
	test_quote_without_delimiter();
	test_quote_with_delimiter();
	test_strict_match_core();
	test_legacy_preg_match_wrapper();
	test_strict_match_all_core();
	test_legacy_preg_match_all_wrapper();
	test_replace_variants();
	test_replace_callback_strict_and_legacy();
	test_replace_callback_array_strict_and_legacy();
	test_grep_and_filter_strict_core();
	test_grep_and_filter_legacy_wrapper();
	test_split_strict_and_legacy();
	return 0;
}
