#include "test_common.hpp"

#include "modules/datetime/datetime.hpp"
#include "scpp/lang/php.hpp"

#include <cassert>
#include <string>

namespace {

static void test_epoch_format_and_parse() {
	const auto text = scpp::dt::format_iso_utc(scpp::int_t(0));
	assert(text.native_value() == "1970-01-01T00:00:00Z");

	const auto parsed = scpp::dt::parse_iso_utc(text);
	assert(parsed.has_value());
	assert(parsed.value().native_value() == 0);
}

static void test_known_utc_roundtrip() {
	const auto parsed = scpp::dt::parse_iso_utc(scpp::string_t("2024-02-29T12:34:56Z"));
	assert(parsed.has_value());
	assert(scpp::dt::format_iso_utc(parsed.value()).native_value() == "2024-02-29T12:34:56Z");
}

static void test_parse_rejects_invalid_values() {
	assert(scpp::dt::parse_iso_utc(scpp::string_t("2024-02-30T00:00:00Z")).has_error());
	assert(scpp::dt::parse_iso_utc(scpp::string_t("2024-01-01T24:00:00Z")).has_error());
	assert(scpp::dt::parse_iso_utc(scpp::string_t("2024-01-01 00:00:00")).has_error());
}

static void test_clock_shapes() {
	const auto seconds = scpp::dt::now_unix_seconds().native_value();
	const auto millis = scpp::dt::now_unix_millis().native_value();
	assert(seconds > 0);
	assert(millis >= seconds * 1000);

	const auto before = scpp::dt::monotonic_millis().native_value();
	scpp::dt::sleep_millis(scpp::int_t(1));
	const auto after = scpp::dt::monotonic_millis().native_value();
	assert(after >= before);
}

static void test_php_wrapper_surface() {
	const auto seconds = scpp::php::time().native_value();
	assert(seconds > 0);
	assert(scpp::php::dt_format_iso_utc(scpp::int_t(0)).native_value() == "1970-01-01T00:00:00Z");

	const auto parsed = scpp::php::dt_parse_iso_utc(scpp::string_t("1970-01-01T00:00:01Z"));
	assert(parsed.has_value());
	assert(parsed.value().native_value() == 1);
}

static void test_common_local_format_parse_roundtrip() {
	const auto parsed = scpp::dt::parse_common_local(scpp::string_t("2024-02-29 12:34:56"));
	assert(parsed.has_value());
	assert(scpp::dt::format_local(scpp::string_t("Y-m-d H:i:s"), parsed.value()).native_value() == "2024-02-29 12:34:56");
	assert(scpp::dt::format_local(scpp::string_t("Y/n/j G:i:s U"), parsed.value()).native_value().find("2024/2/29 12:34:56 ") == 0);

	const auto date_only = scpp::dt::parse_common_local(scpp::string_t("2024-02-29"));
	assert(date_only.has_value());
	assert(scpp::dt::format_local(scpp::string_t("Y-m-d H:i:s"), date_only.value()).native_value() == "2024-02-29 00:00:00");

	assert(scpp::dt::parse_common_local(scpp::string_t("2024-02-30 12:34:56")).has_error());
	assert(scpp::dt::parse_common_local(scpp::string_t("next Tuesday")).has_error());
}

static void test_php_date_and_strtotime_wrappers() {
	const auto parsed = scpp::php::strtotime(scpp::string_t("2024-02-29 12:34:56"));
	assert(parsed.has_value().native_value());
	assert(scpp::php::date(scpp::string_t("Y-m-d H:i:s"), parsed.value()).native_value() == "2024-02-29 12:34:56");
	assert(scpp::php::strtotime(scpp::string_t("not a date")).has_value().native_value() == false);
}

} // namespace

int main() {
	test_epoch_format_and_parse();
	test_known_utc_roundtrip();
	test_parse_rejects_invalid_values();
	test_clock_shapes();
	test_php_wrapper_surface();
	test_common_local_format_parse_roundtrip();
	test_php_date_and_strtotime_wrappers();
	return 0;
}
