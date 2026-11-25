#pragma once

#include "scpp/int_t.hpp"
#include "scpp/string_t.hpp"

#include <array>
#include <cctype>
#include <cstdint>
#include <cmath>
#include <iomanip>
#include <limits>
#include <sstream>
#include <stdexcept>
#include <string>
#include <string_view>

namespace scpp {

inline const int_t<> PHP_INT_MAX{static_cast<std::int64_t>(std::numeric_limits<std::int64_t>::max())};
inline const int_t<> STR_PAD_LEFT{0};
inline const int_t<> STR_PAD_RIGHT{1};
inline const int_t<> STR_PAD_BOTH{2};

class ValueError : public std::runtime_error {
public:
	explicit ValueError(const std::string &message) : std::runtime_error(message) {}
};

class TypeError : public std::runtime_error {
public:
	explicit TypeError(const std::string &message) : std::runtime_error(message) {}
};

inline std::size_t normalize_substr_start(std::size_t size, std::int64_t offset) {
	if (offset >= 0) {
		const auto start = static_cast<std::uint64_t>(offset);
		return start < size ? static_cast<std::size_t>(start) : size;
	}

	const auto distance_from_end = static_cast<std::uint64_t>(-(offset + 1)) + 1;
	if (distance_from_end >= size) {
		return 0;
	}
	return size - static_cast<std::size_t>(distance_from_end);
}

inline std::size_t normalize_substr_end(std::size_t size, std::size_t start, std::int64_t length) {
	if (length >= 0) {
		const auto remaining = size - start;
		const auto requested = static_cast<std::uint64_t>(length);
		const auto used = requested < remaining ? requested : remaining;
		return start + static_cast<std::size_t>(used);
	}

	const auto distance_from_end = static_cast<std::uint64_t>(-(length + 1)) + 1;
	if (distance_from_end >= size) {
		return start;
	}
	const auto end = size - static_cast<std::size_t>(distance_from_end);
	return end > start ? end : start;
}

inline bool normalize_string_window(std::size_t size, std::int64_t offset, bool &has_start, std::size_t &start) {
	has_start = true;
	if (offset >= 0) {
		const auto unsigned_offset = static_cast<std::uint64_t>(offset);
		if (unsigned_offset > size) {
			has_start = false;
			start = size;
			return false;
		}
		start = static_cast<std::size_t>(unsigned_offset);
		return true;
	}

	const auto distance_from_end = static_cast<std::uint64_t>(-(offset + 1)) + 1;
	if (distance_from_end > size) {
		has_start = false;
		start = size;
		return false;
	}
	start = size - static_cast<std::size_t>(distance_from_end);
	return true;
}

inline bool normalize_string_window_end(std::size_t size, std::size_t start, std::int64_t length, std::size_t &end) {
	if (length >= 0) {
		const auto remaining = size - start;
		const auto requested = static_cast<std::uint64_t>(length);
		const auto used = requested < remaining ? requested : remaining;
		end = start + static_cast<std::size_t>(used);
		return true;
	}

	const auto distance_from_end = static_cast<std::uint64_t>(-(length + 1)) + 1;
	if (distance_from_end > size) {
		end = start;
		return false;
	}
	end = size - static_cast<std::size_t>(distance_from_end);
	return end >= start;
}

inline unsigned char ascii_tolower_byte(unsigned char value) {
	return static_cast<unsigned char>(std::tolower(value));
}

inline int ascii_compare_sensitive(std::string_view left, std::string_view right) {
	const auto shared = left.size() < right.size() ? left.size() : right.size();
	for (std::size_t index = 0; index < shared; ++index) {
		const auto left_byte = static_cast<unsigned char>(left[index]);
		const auto right_byte = static_cast<unsigned char>(right[index]);
		if (left_byte != right_byte) {
			return static_cast<int>(left_byte) - static_cast<int>(right_byte);
		}
	}
	if (left.size() == right.size()) {
		return 0;
	}
	return left.size() < right.size() ? -1 : 1;
}

inline int ascii_compare_insensitive(std::string_view left, std::string_view right) {
	const auto shared = left.size() < right.size() ? left.size() : right.size();
	for (std::size_t index = 0; index < shared; ++index) {
		const auto left_byte = ascii_tolower_byte(static_cast<unsigned char>(left[index]));
		const auto right_byte = ascii_tolower_byte(static_cast<unsigned char>(right[index]));
		if (left_byte != right_byte) {
			return static_cast<int>(left_byte) - static_cast<int>(right_byte);
		}
	}
	if (left.size() == right.size()) {
		return 0;
	}
	return left.size() < right.size() ? -1 : 1;
}

inline constexpr std::array<unsigned char, 6> default_trim_mask = {
	static_cast<unsigned char>(' '),
	static_cast<unsigned char>('\n'),
	static_cast<unsigned char>('\r'),
	static_cast<unsigned char>('\t'),
	static_cast<unsigned char>('\v'),
	static_cast<unsigned char>('\0')
};

inline bool ascii_is_in_default_trim_mask(unsigned char value) {
	for (const auto candidate : default_trim_mask) {
		if (candidate == value) {
			return true;
		}
	}
	return false;
}

inline bool ascii_is_in_trim_mask(unsigned char value, const string_t &mask) {
	if (mask.size() == 0) {
		return false;
	}
	for (const unsigned char candidate : mask.native_value()) {
		if (candidate == value) {
			return true;
		}
	}
	return false;
}

inline std::size_t find_trim_left_index(const string_t &value, const string_t *mask) {
	const auto &native = value.native_value();
	std::size_t index = 0;
	while (index < native.size()) {
		const auto byte = static_cast<unsigned char>(native[index]);
		const auto should_trim = mask == nullptr ? ascii_is_in_default_trim_mask(byte) : ascii_is_in_trim_mask(byte, *mask);
		if (!should_trim) {
			break;
		}
		++index;
	}
	return index;
}

inline std::size_t find_trim_right_index(const string_t &value, const string_t *mask) {
	const auto &native = value.native_value();
	std::size_t index = native.size();
	while (index > 0) {
		const auto byte = static_cast<unsigned char>(native[index - 1]);
		const auto should_trim = mask == nullptr ? ascii_is_in_default_trim_mask(byte) : ascii_is_in_trim_mask(byte, *mask);
		if (!should_trim) {
			break;
		}
		--index;
	}
	return index;
}

inline std::size_t normalize_forward_search_offset(std::size_t size, std::int64_t offset, const char *function_name) {
	if (offset >= 0) {
		const auto unsigned_offset = static_cast<std::uint64_t>(offset);
		if (unsigned_offset > size) {
			throw ValueError(std::string(function_name) + "(): Argument #3 ($offset) must be contained in argument #1 ($haystack)");
		}
		return static_cast<std::size_t>(unsigned_offset);
	}

	const auto distance_from_end = static_cast<std::uint64_t>(-(offset + 1)) + 1;
	if (distance_from_end > size) {
		throw ValueError(std::string(function_name) + "(): Argument #3 ($offset) must be contained in argument #1 ($haystack)");
	}
	return size - static_cast<std::size_t>(distance_from_end);
}

inline std::size_t normalize_reverse_search_limit(std::size_t size, std::int64_t offset, const char *function_name) {
	if (offset >= 0) {
		const auto unsigned_offset = static_cast<std::uint64_t>(offset);
		if (unsigned_offset > size) {
			throw ValueError(std::string(function_name) + "(): Argument #3 ($offset) must be contained in argument #1 ($haystack)");
		}
		return static_cast<std::size_t>(unsigned_offset);
	}

	const auto distance_from_end = static_cast<std::uint64_t>(-(offset + 1)) + 1;
	if (distance_from_end > size) {
		throw ValueError(std::string(function_name) + "(): Argument #3 ($offset) must be contained in argument #1 ($haystack)");
	}
	return size - static_cast<std::size_t>(distance_from_end);
}

inline char ascii_to_lower(char value) {
	return static_cast<char>(std::tolower(static_cast<unsigned char>(value)));
}

inline char ascii_to_upper(char value) {
	return static_cast<char>(std::toupper(static_cast<unsigned char>(value)));
}

inline string_t trim_slice(const string_t &value, std::size_t start, std::size_t end) {
	if (end < start) {
		end = start;
	}
	return value.slice(start, end);
}

inline std::string insert_thousands_grouping(std::string digits, const std::string &separator) {
	if (digits.size() <= 3 || separator.empty()) {
		return digits;
	}
	std::string out;
	out.reserve(digits.size() + ((digits.size() - 1) / 3) * separator.size());
	const auto first_group = digits.size() % 3;
	std::size_t index = 0;
	if (first_group != 0) {
		out.append(digits, 0, first_group);
		index = first_group;
		if (index < digits.size()) {
			out += separator;
		}
	}
	while (index < digits.size()) {
		out.append(digits, index, 3);
		index += 3;
		if (index < digits.size()) {
			out += separator;
		}
	}
	return out;
}

inline string_t number_format_from_double(double value, std::int64_t decimals, const string_t &decimal_separator, const string_t &thousands_separator) {
	const auto negative = value < 0.0;
	const auto abs_value = std::fabs(value);
	double rounded = abs_value;
	int precision = 0;
	if (decimals >= 0) {
		precision = static_cast<int>(decimals);
		const auto scale = std::pow(10.0, static_cast<double>(precision));
		rounded = scale > 0.0 ? std::round(abs_value * scale) / scale : abs_value;
	} else {
		const auto left_scale = std::pow(10.0, static_cast<double>(-decimals));
		rounded = left_scale > 0.0 ? std::round(abs_value / left_scale) * left_scale : abs_value;
	}
	std::ostringstream stream;
	stream << std::fixed << std::setprecision(precision) << rounded;
	std::string rendered = stream.str();
	std::string integer_part;
	std::string fraction_part;
	const auto dot = rendered.find('.');
	if (dot == std::string::npos) {
		integer_part = rendered;
	} else {
		integer_part = rendered.substr(0, dot);
		fraction_part = rendered.substr(dot + 1);
	}
	std::string out;
	if (negative && (rounded != 0.0 || value < 0.0)) {
		out.push_back('-');
	}
	out += insert_thousands_grouping(integer_part, thousands_separator.native_value());
	if (precision > 0) {
		out += decimal_separator.native_value();
		out += fraction_part;
	}
	return string_t(std::move(out));
}

inline int hex_nibble_value(unsigned char value) {
	if (value >= static_cast<unsigned char>('0') && value <= static_cast<unsigned char>('9')) {
		return static_cast<int>(value - static_cast<unsigned char>('0'));
	}
	const auto lower = ascii_tolower_byte(value);
	if (lower >= static_cast<unsigned char>('a') && lower <= static_cast<unsigned char>('f')) {
		return 10 + static_cast<int>(lower - static_cast<unsigned char>('a'));
	}
	return -1;
}

} // namespace scpp
