#pragma once

#include "modules/datetime/datetime.hpp"
#include "scpp/result_or_false.hpp"

namespace scpp::php {

[[nodiscard]] inline int_t<> time() {
	return scpp::dt::now_unix_seconds();
}

[[nodiscard]] inline int_t<> dt_now() {
	return scpp::dt::now_unix_seconds();
}

[[nodiscard]] inline int_t<> dt_now_ms() {
	return scpp::dt::now_unix_millis();
}

[[nodiscard]] inline int_t<> dt_monotonic_ms() {
	return scpp::dt::monotonic_millis();
}

inline void dt_sleep_ms(const int_t<> &millis) {
	scpp::dt::sleep_millis(millis);
}

[[nodiscard]] inline string_t dt_format_iso_utc(const int_t<> &unix_seconds) {
	return scpp::dt::format_iso_utc(unix_seconds);
}

[[nodiscard]] inline result<int_t<>> dt_parse_iso_utc(const string_t &value) {
	return scpp::dt::parse_iso_utc(value);
}

[[nodiscard]] inline string_t dt_format(const string_t &format, const int_t<> &timestamp) {
	return scpp::dt::format_local(format, timestamp);
}

[[nodiscard]] inline string_t dt_format_now(const string_t &format) {
	return scpp::dt::format_local_now(format);
}

[[nodiscard]] inline result<int_t<>> dt_parse(const string_t &value) {
	return scpp::dt::parse_common_local(value);
}

[[nodiscard]] inline string_t date(const string_t &format) {
	return scpp::dt::format_local_now(format);
}

[[nodiscard]] inline string_t date(const string_t &format, const int_t<> &timestamp) {
	return scpp::dt::format_local(format, timestamp);
}

[[nodiscard]] inline result_or_false<int_t<>> strtotime(const string_t &value) {
	const auto parsed = scpp::dt::parse_common_local(value);
	if (parsed.has_error()) {
		return false_sentinel_t{};
	}
	return parsed.value();
}

} // namespace scpp::php
