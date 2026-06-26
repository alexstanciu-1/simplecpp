#include "modules/datetime/datetime.hpp"

#include "scpp/error_t.hpp"

#include <chrono>
#include <ctime>
#include <iomanip>
#include <limits>
#include <sstream>
#include <stdexcept>
#include <string>
#include <thread>

namespace scpp::dt {
namespace {

using seconds_t = std::chrono::seconds;
using millis_t = std::chrono::milliseconds;

[[nodiscard]] std::int64_t checked_count_to_int64(const auto duration) {
	const auto count = duration.count();
	if constexpr (sizeof(count) > sizeof(std::int64_t)) {
		if (count > static_cast<decltype(count)>(std::numeric_limits<std::int64_t>::max()) ||
			count < static_cast<decltype(count)>(std::numeric_limits<std::int64_t>::min())) {
			throw std::runtime_error("datetime value is outside int64 range");
		}
	}
	return static_cast<std::int64_t>(count);
}

[[nodiscard]] bool ascii_digit(const char ch) noexcept {
	return ch >= '0' && ch <= '9';
}

[[nodiscard]] bool parse_fixed_uint(const std::string &text, const std::size_t offset, const std::size_t width, int &out) {
	if (offset + width > text.size()) {
		return false;
	}
	int value = 0;
	for (std::size_t i = 0; i < width; ++i) {
		const char ch = text[offset + i];
		if (!ascii_digit(ch)) {
			return false;
		}
		value = (value * 10) + (ch - '0');
	}
	out = value;
	return true;
}

[[nodiscard]] bool is_leap_year(const int year) noexcept {
	if (year % 400 == 0) {
		return true;
	}
	if (year % 100 == 0) {
		return false;
	}
	return year % 4 == 0;
}

[[nodiscard]] int days_in_month(const int year, const int month) noexcept {
	switch (month) {
		case 1: return 31;
		case 2: return is_leap_year(year) ? 29 : 28;
		case 3: return 31;
		case 4: return 30;
		case 5: return 31;
		case 6: return 30;
		case 7: return 31;
		case 8: return 31;
		case 9: return 30;
		case 10: return 31;
		case 11: return 30;
		case 12: return 31;
		default: return 0;
	}
}

[[nodiscard]] error_t parse_error(const char *message) {
	return error_t(string_t(message));
}

[[nodiscard]] std::tm utc_tm_from_unix_seconds(const int_t<> &unix_seconds) {
	const auto time_value = static_cast<std::time_t>(unix_seconds.native_value());
	std::tm tm_value{};
#if defined(_WIN32)
	if (gmtime_s(&tm_value, &time_value) != 0) {
		throw std::runtime_error("datetime value cannot be represented as UTC time");
	}
#else
	if (gmtime_r(&time_value, &tm_value) == nullptr) {
		throw std::runtime_error("datetime value cannot be represented as UTC time");
	}
#endif
	return tm_value;
}

[[nodiscard]] std::tm local_tm_from_unix_seconds(const int_t<> &unix_seconds) {
	const auto time_value = static_cast<std::time_t>(unix_seconds.native_value());
	std::tm tm_value{};
#if defined(_WIN32)
	if (localtime_s(&tm_value, &time_value) != 0) {
		throw std::runtime_error("datetime value cannot be represented as local time");
	}
#else
	if (localtime_r(&time_value, &tm_value) == nullptr) {
		throw std::runtime_error("datetime value cannot be represented as local time");
	}
#endif
	return tm_value;
}

void append_padded_number(std::string &out, const int value, const int width) {
	std::ostringstream buffer;
	buffer << std::setw(width) << std::setfill('0') << value;
	out += buffer.str();
}

[[nodiscard]] string_t format_php_common(const string_t &format, const std::tm &tm_value, const int_t<> &unix_seconds) {
	const std::string pattern = format.native_value();
	std::string out;
	out.reserve(pattern.size() + 16);

	bool escaped = false;
	for (const char token : pattern) {
		if (escaped) {
			out.push_back(token);
			escaped = false;
			continue;
		}
		if (token == '\\') {
			escaped = true;
			continue;
		}

		switch (token) {
			case 'Y': append_padded_number(out, tm_value.tm_year + 1900, 4); break;
			case 'y': append_padded_number(out, (tm_value.tm_year + 1900) % 100, 2); break;
			case 'm': append_padded_number(out, tm_value.tm_mon + 1, 2); break;
			case 'n': out += std::to_string(tm_value.tm_mon + 1); break;
			case 'd': append_padded_number(out, tm_value.tm_mday, 2); break;
			case 'j': out += std::to_string(tm_value.tm_mday); break;
			case 'H': append_padded_number(out, tm_value.tm_hour, 2); break;
			case 'G': out += std::to_string(tm_value.tm_hour); break;
			case 'i': append_padded_number(out, tm_value.tm_min, 2); break;
			case 's': append_padded_number(out, tm_value.tm_sec, 2); break;
			case 'U': out += std::to_string(unix_seconds.native_value()); break;
			default: out.push_back(token); break;
		}
	}
	if (escaped) {
		out.push_back('\\');
	}
	return string_t(out);
}

[[nodiscard]] result<int_t<>> parse_local_components(
	const int year,
	const int month,
	const int day,
	const int hour,
	const int minute,
	const int second
) {
	if (month < 1 || month > 12) {
		return parse_error("datetime month is out of range");
	}
	if (day < 1 || day > days_in_month(year, month)) {
		return parse_error("datetime day is out of range");
	}
	if (hour < 0 || hour > 23 || minute < 0 || minute > 59 || second < 0 || second > 59) {
		return parse_error("datetime time component is out of range");
	}

	std::tm tm_value{};
	tm_value.tm_year = year - 1900;
	tm_value.tm_mon = month - 1;
	tm_value.tm_mday = day;
	tm_value.tm_hour = hour;
	tm_value.tm_min = minute;
	tm_value.tm_sec = second;
	tm_value.tm_isdst = -1;

	const auto timestamp = std::mktime(&tm_value);
	if (timestamp == static_cast<std::time_t>(-1)) {
		return parse_error("datetime value cannot be represented as local time");
	}
	return int_t<>(static_cast<std::int64_t>(timestamp));
}

} // namespace

int_t<> now_unix_seconds() {
	const auto now = std::chrono::system_clock::now().time_since_epoch();
	return int_t<>(checked_count_to_int64(std::chrono::duration_cast<seconds_t>(now)));
}

int_t<> now_unix_millis() {
	const auto now = std::chrono::system_clock::now().time_since_epoch();
	return int_t<>(checked_count_to_int64(std::chrono::duration_cast<millis_t>(now)));
}

int_t<> monotonic_millis() {
	const auto now = std::chrono::steady_clock::now().time_since_epoch();
	return int_t<>(checked_count_to_int64(std::chrono::duration_cast<millis_t>(now)));
}

void sleep_millis(const int_t<> &millis) {
	const auto value = millis.native_value();
	if (value <= 0) {
		return;
	}
	std::this_thread::sleep_for(millis_t(value));
}

string_t format_iso_utc(const int_t<> &unix_seconds) {
	const auto tm_value = utc_tm_from_unix_seconds(unix_seconds);
	std::ostringstream out;
	out << std::put_time(&tm_value, "%Y-%m-%dT%H:%M:%SZ");
	return string_t(out.str());
}

result<int_t<>> parse_iso_utc(const string_t &value) {
	const std::string text = value.native_value();
	if (text.size() != 20 ||
		text[4] != '-' ||
		text[7] != '-' ||
		text[10] != 'T' ||
		text[13] != ':' ||
		text[16] != ':' ||
		text[19] != 'Z') {
		return parse_error("expected ISO-8601 UTC value in YYYY-MM-DDTHH:MM:SSZ form");
	}

	int year = 0;
	int month = 0;
	int day = 0;
	int hour = 0;
	int minute = 0;
	int second = 0;
	if (!parse_fixed_uint(text, 0, 4, year) ||
		!parse_fixed_uint(text, 5, 2, month) ||
		!parse_fixed_uint(text, 8, 2, day) ||
		!parse_fixed_uint(text, 11, 2, hour) ||
		!parse_fixed_uint(text, 14, 2, minute) ||
		!parse_fixed_uint(text, 17, 2, second)) {
		return parse_error("ISO-8601 UTC value contains a non-numeric component");
	}
	if (month < 1 || month > 12) {
		return parse_error("ISO-8601 UTC month is out of range");
	}
	if (day < 1 || day > days_in_month(year, month)) {
		return parse_error("ISO-8601 UTC day is out of range");
	}
	if (hour < 0 || hour > 23 || minute < 0 || minute > 59 || second < 0 || second > 59) {
		return parse_error("ISO-8601 UTC time component is out of range");
	}

	const std::chrono::year_month_day ymd{
		std::chrono::year{year},
		std::chrono::month{static_cast<unsigned>(month)},
		std::chrono::day{static_cast<unsigned>(day)}
	};
	if (!ymd.ok()) {
		return parse_error("ISO-8601 UTC date is out of range");
	}

	const auto days = std::chrono::sys_days{ymd};
	const auto timestamp = std::chrono::time_point_cast<seconds_t>(
		days + std::chrono::hours{hour} + std::chrono::minutes{minute} + seconds_t{second});
	return int_t<>(checked_count_to_int64(timestamp.time_since_epoch()));
}

string_t format_local(const string_t &format, const int_t<> &unix_seconds) {
	return format_php_common(format, local_tm_from_unix_seconds(unix_seconds), unix_seconds);
}

string_t format_local_now(const string_t &format) {
	return format_local(format, now_unix_seconds());
}

result<int_t<>> parse_common_local(const string_t &value) {
	const std::string text = value.native_value();
	if (text.empty()) {
		return parse_error("datetime value is empty");
	}

	if (text.size() == 10 && text[4] == '-' && text[7] == '-') {
		int year = 0;
		int month = 0;
		int day = 0;
		if (!parse_fixed_uint(text, 0, 4, year) ||
			!parse_fixed_uint(text, 5, 2, month) ||
			!parse_fixed_uint(text, 8, 2, day)) {
			return parse_error("datetime date contains a non-numeric component");
		}
		return parse_local_components(year, month, day, 0, 0, 0);
	}

	if (text.size() == 19 && text[4] == '-' && text[7] == '-' &&
		(text[10] == ' ' || text[10] == 'T') && text[13] == ':' && text[16] == ':') {
		int year = 0;
		int month = 0;
		int day = 0;
		int hour = 0;
		int minute = 0;
		int second = 0;
		if (!parse_fixed_uint(text, 0, 4, year) ||
			!parse_fixed_uint(text, 5, 2, month) ||
			!parse_fixed_uint(text, 8, 2, day) ||
			!parse_fixed_uint(text, 11, 2, hour) ||
			!parse_fixed_uint(text, 14, 2, minute) ||
			!parse_fixed_uint(text, 17, 2, second)) {
			return parse_error("datetime value contains a non-numeric component");
		}
		return parse_local_components(year, month, day, hour, minute, second);
	}

	if (text.size() == 20 && text[19] == 'Z') {
		return parse_iso_utc(value);
	}

	return parse_error("unsupported datetime format");
}

} // namespace scpp::dt
