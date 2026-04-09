#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/weak_p.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/support/var_dump.hpp"

#include <algorithm>
#include <array>
#include <chrono>
#include <cctype>
#include <cstdint>
#include <iomanip>
#include <fstream>
#include <iostream>
#include <limits>
#include <sstream>
#include <string>
#include <string_view>
#include <type_traits>
#include <tuple>
#include <utility>
#if defined(__unix__) || defined(__APPLE__)
#include <sys/resource.h>
#endif

namespace scpp {
inline const int_t PHP_INT_MAX{static_cast<std::int64_t>(std::numeric_limits<std::int64_t>::max())};
inline const int_t STR_PAD_LEFT{0};
inline const int_t STR_PAD_RIGHT{1};
inline const int_t STR_PAD_BOTH{2};
}

namespace scpp::php {

class ValueError : public std::runtime_error {
public:
	explicit ValueError(const std::string &message) : std::runtime_error(message) {}
};

// PHP compatibility constants consumed by generated code.
using ::scpp::PHP_INT_MAX;
using ::scpp::STR_PAD_LEFT;
using ::scpp::STR_PAD_RIGHT;
using ::scpp::STR_PAD_BOTH;

// Validates a PHP array / ?array argument that has been lowered to mixed_t.
// How: reject invalid kinds before executing any user code inside the callee.
inline void expect_array_argument(const mixed_t &value, bool nullable, const char *name) {
	const auto kind = value.kind();
	if (kind == mixed_t::kind_t::table_v || kind == mixed_t::kind_t::shared_table_v || kind == mixed_t::kind_t::weak_table_v) {
		return;
	}
	if (nullable && kind == mixed_t::kind_t::null_v) {
		return;
	}
	throw ValueError(std::string("Argument $") + name + (nullable ? " must be of type ?array" : " must be of type array"));
}

inline std::size_t normalize_substr_start(std::size_t size, std::int64_t offset) {
	// Normalizes a PHP-like offset into a bounded zero-based start index.
	// How: negative offsets count from the end; all outcomes are clamped into [0, size].
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
	// Normalizes a PHP-like length into a bounded half-open end index.
	// How: negative lengths trim from the end; any underflowing result collapses to the start index.
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
	// Normalizes a PHP-like offset into a string window start.
	// How: negative offsets count from the end; positive offsets beyond the end yield an empty window marker.
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
	// Normalizes a PHP-like length into a string window end.
	// How: positive lengths clamp to the available range; negative lengths trim from the end and may invalidate the window.
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
		const auto should_trim = mask == nullptr
			? ascii_is_in_default_trim_mask(byte)
			: ascii_is_in_trim_mask(byte, *mask);
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
		const auto should_trim = mask == nullptr
			? ascii_is_in_default_trim_mask(byte)
			: ascii_is_in_trim_mask(byte, *mask);
		if (!should_trim) {
			break;
		}
		--index;
	}
	return index;
}

inline std::size_t normalize_php_forward_search_offset(std::size_t size, std::int64_t offset, const char *function_name) {
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

inline std::size_t normalize_php_reverse_search_limit(std::size_t size, std::int64_t offset, const char *function_name) {
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

inline int_t strlen(const string_t &value) {
	return int_t(static_cast<std::int64_t>(value.size()));
}

inline mixed_t strpos(const string_t &haystack, const string_t &needle) {
	const auto position = haystack.native_value().find(needle.native_value());
	if (position == std::string::npos) {
		return mixed_t(bool_t(false));
	}
	return mixed_t(int_t(static_cast<std::int64_t>(position)));
}

inline mixed_t strpos(const string_t &haystack, const string_t &needle, const int_t &offset) {
	const auto start = normalize_php_forward_search_offset(haystack.size(), offset.native_value(), "strpos");
	const auto position = haystack.native_value().find(needle.native_value(), static_cast<std::size_t>(start));
	if (position == std::string::npos) {
		return mixed_t(bool_t(false));
	}
	return mixed_t(int_t(static_cast<std::int64_t>(position)));
}

inline mixed_t strrpos(const string_t &haystack, const string_t &needle) {
	const auto position = haystack.native_value().rfind(needle.native_value());
	if (position == std::string::npos) {
		return mixed_t(bool_t(false));
	}
	return mixed_t(int_t(static_cast<std::int64_t>(position)));
}

inline mixed_t strrpos(const string_t &haystack, const string_t &needle, const int_t &offset) {
	const auto limit = normalize_php_reverse_search_limit(haystack.size(), offset.native_value(), "strrpos");
	const auto &native = haystack.native_value();
	const auto &needle_native = needle.native_value();
	if (offset.native_value() >= 0) {
		const auto position = native.rfind(needle_native);
		if (position == std::string::npos || position < limit) {
			return mixed_t(bool_t(false));
		}
		return mixed_t(int_t(static_cast<std::int64_t>(position)));
	}
	const auto position = native.rfind(needle_native, limit);
	if (position == std::string::npos) {
		return mixed_t(bool_t(false));
	}
	return mixed_t(int_t(static_cast<std::int64_t>(position)));
}

inline string_t strtolower(const string_t &value) {
	std::string out = value.native_value();
	std::transform(out.begin(), out.end(), out.begin(), ascii_to_lower);
	return string_t(std::move(out));
}

inline string_t strtoupper(const string_t &value) {
	std::string out = value.native_value();
	std::transform(out.begin(), out.end(), out.begin(), ascii_to_upper);
	return string_t(std::move(out));
}

inline string_t lcfirst(const string_t &value) {
	if (value.size() == 0) {
		return value;
	}
	std::string out = value.native_value();
	out[0] = ascii_to_lower(out[0]);
	return string_t(std::move(out));
}

inline string_t ucfirst(const string_t &value) {
	if (value.size() == 0) {
		return value;
	}
	std::string out = value.native_value();
	out[0] = ascii_to_upper(out[0]);
	return string_t(std::move(out));
}

inline bool_t str_starts_with(const string_t &haystack, const string_t &needle) {
	const auto &left = haystack.native_value();
	const auto &right = needle.native_value();
	if (right.size() > left.size()) {
		return bool_t(false);
	}
	return bool_t(left.compare(0, right.size(), right) == 0);
}

inline bool_t str_ends_with(const string_t &haystack, const string_t &needle) {
	const auto &left = haystack.native_value();
	const auto &right = needle.native_value();
	if (right.size() > left.size()) {
		return bool_t(false);
	}
	return bool_t(left.compare(left.size() - right.size(), right.size(), right) == 0);
}

inline string_t ltrim(const string_t &value) {
	const auto start = find_trim_left_index(value, nullptr);
	return trim_slice(value, start, value.size());
}

inline string_t ltrim(const string_t &value, const string_t &mask) {
	const auto start = find_trim_left_index(value, &mask);
	return trim_slice(value, start, value.size());
}

inline string_t rtrim(const string_t &value) {
	const auto end = find_trim_right_index(value, nullptr);
	return trim_slice(value, 0, end);
}

inline string_t rtrim(const string_t &value, const string_t &mask) {
	const auto end = find_trim_right_index(value, &mask);
	return trim_slice(value, 0, end);
}

inline string_t trim(const string_t &value) {
	const auto start = find_trim_left_index(value, nullptr);
	const auto end = find_trim_right_index(value, nullptr);
	return trim_slice(value, start, end);
}

inline string_t trim(const string_t &value, const string_t &mask) {
	const auto start = find_trim_left_index(value, &mask);
	const auto end = find_trim_right_index(value, &mask);
	return trim_slice(value, start, end);
}

inline string_t substr(const string_t &value, const int_t &offset, const int_t &length) {
	// Implements the practical PHP-like substr(string, offset, length) wrapper.
	// How: the wrapper translates PHP offset/length rules into a bounded half-open slice on the runtime string type.
	const auto size = value.size();
	const auto start = normalize_substr_start(size, offset.native_value());
	const auto end = normalize_substr_end(size, start, length.native_value());
	return value.slice(start, end);
}

inline string_t substr(const string_t &value, const int_t &offset) {
	// Implements the practical PHP-like substr(string, offset) wrapper.
	// How: the wrapper reuses the same normalized start handling and slices through the end of the string.
	const auto start = normalize_substr_start(value.size(), offset.native_value());
	return value.slice(start, value.size());
}

inline int_t substr_compare(const string_t &main_str, const string_t &str, const int_t &offset) {
	bool has_start = false;
	std::size_t start = 0;
	const auto has_valid_start = normalize_string_window(main_str.size(), offset.native_value(), has_start, start);
	const std::string_view left = has_valid_start
		? std::string_view(main_str.native_value()).substr(start)
		: std::string_view();
	const std::string_view right = str.native_value();
	return int_t(static_cast<std::int64_t>(ascii_compare_sensitive(left, right)));
}

inline int_t substr_compare(const string_t &main_str, const string_t &str, const int_t &offset, const int_t &length) {
	bool has_start = false;
	std::size_t start = 0;
	const auto has_valid_start = normalize_string_window(main_str.size(), offset.native_value(), has_start, start);
	std::size_t end = start;
	const auto has_valid_window = has_valid_start && normalize_string_window_end(main_str.size(), start, length.native_value(), end);
	const std::string_view left = has_valid_window
		? std::string_view(main_str.native_value()).substr(start, end - start)
		: std::string_view();
	const auto requested = length.native_value() >= 0 ? static_cast<std::size_t>(length.native_value()) : left.size();
	const std::string_view right = std::string_view(str.native_value()).substr(0, requested);
	return int_t(static_cast<std::int64_t>(ascii_compare_sensitive(left, right)));
}

inline int_t substr_compare(const string_t &main_str, const string_t &str, const int_t &offset, const int_t &length, const bool_t &case_insensitive) {
	bool has_start = false;
	std::size_t start = 0;
	const auto has_valid_start = normalize_string_window(main_str.size(), offset.native_value(), has_start, start);
	std::size_t end = start;
	const auto has_valid_window = has_valid_start && normalize_string_window_end(main_str.size(), start, length.native_value(), end);
	const std::string_view left = has_valid_window
		? std::string_view(main_str.native_value()).substr(start, end - start)
		: std::string_view();
	const auto requested = length.native_value() >= 0 ? static_cast<std::size_t>(length.native_value()) : left.size();
	const std::string_view right = std::string_view(str.native_value()).substr(0, requested);
	const auto compare_result = case_insensitive.native_value()
		? ascii_compare_insensitive(left, right)
		: ascii_compare_sensitive(left, right);
	return int_t(static_cast<std::int64_t>(compare_result));
}

inline string_t substr_replace(const string_t &subject, const string_t &replacement, const int_t &offset) {
	const auto size = subject.size();
	const auto start = normalize_substr_start(size, offset.native_value());
	std::string out;
	out.reserve(start + replacement.size());
	out.append(subject.native_value(), 0, start);
	out += replacement.native_value();
	return string_t(std::move(out));
}

inline string_t substr_replace(const string_t &subject, const string_t &replacement, const int_t &offset, const int_t &length) {
	const auto size = subject.size();
	const auto start = normalize_substr_start(size, offset.native_value());
	std::size_t end = start;
	if (length.native_value() < 0) {
		const auto distance_from_end = static_cast<std::uint64_t>(-(length.native_value() + 1)) + 1;
		if (distance_from_end >= size) {
			end = start;
		} else {
			end = size - static_cast<std::size_t>(distance_from_end);
			if (end < start) {
				end = start;
			}
		}
	} else {
		const auto remaining = size - start;
		const auto requested = static_cast<std::uint64_t>(length.native_value());
		const auto used = requested < remaining ? requested : remaining;
		end = start + static_cast<std::size_t>(used);
	}
	std::string out;
	out.reserve(start + replacement.size() + (size - end));
	out.append(subject.native_value(), 0, start);
	out += replacement.native_value();
	out.append(subject.native_value(), end, size - end);
	return string_t(std::move(out));
}

inline string_t str_replace(const string_t &search, const string_t &replace, const string_t &subject) {
	if (search.size() == 0) {
		return subject;
	}
	const auto &source = subject.native_value();
	const auto &needle = search.native_value();
	const auto &replacement = replace.native_value();
	std::string out;
	std::size_t cursor = 0;
	while (cursor < source.size()) {
		const auto found = source.find(needle, cursor);
		if (found == std::string::npos) {
			out.append(source, cursor, source.size() - cursor);
			break;
		}
		out.append(source, cursor, found - cursor);
		out += replacement;
		cursor = found + needle.size();
	}
	return string_t(std::move(out));
}

inline std::string make_pad_bytes(const std::string &pad_string, std::size_t size) {
	std::string out;
	out.reserve(size);
	while (out.size() < size) {
		const auto remaining = size - out.size();
		if (pad_string.size() <= remaining) {
			out += pad_string;
		} else {
			out.append(pad_string, 0, remaining);
		}
	}
	return out;
}


inline string_t str_pad(const string_t &input, const int_t &pad_length, const string_t &pad_string, const int_t &pad_type) {
	if (pad_string.size() == 0) {
		throw ValueError("str_pad(): Argument #3 ($pad_string) must not be empty");
	}
	const auto pad_type_value = pad_type.native_value();
	if (pad_type_value != STR_PAD_LEFT.native_value() && pad_type_value != STR_PAD_RIGHT.native_value() && pad_type_value != STR_PAD_BOTH.native_value()) {
		throw ValueError("str_pad(): Argument #4 ($pad_type) must be STR_PAD_LEFT, STR_PAD_RIGHT, or STR_PAD_BOTH");
	}
	const auto target = pad_length.native_value();
	if (target <= 0) {
		return input;
	}
	const auto input_size = input.size();
	const auto target_size = static_cast<std::size_t>(target);
	if (target_size <= input_size) {
		return input;
	}
	const auto total_pad = target_size - input_size;
	std::size_t left_pad = 0;
	std::size_t right_pad = 0;
	if (pad_type_value == STR_PAD_LEFT.native_value()) {
		left_pad = total_pad;
	} else if (pad_type_value == STR_PAD_RIGHT.native_value()) {
		right_pad = total_pad;
	} else {
		left_pad = total_pad / 2;
		right_pad = total_pad - left_pad;
	}
	std::string out;
	out.reserve(target_size);
	out += make_pad_bytes(pad_string.native_value(), left_pad);
	out += input.native_value();
	out += make_pad_bytes(pad_string.native_value(), right_pad);
	return string_t(std::move(out));
}

inline string_t str_pad(const string_t &input, const int_t &pad_length, const string_t &pad_string) {
	return str_pad(input, pad_length, pad_string, STR_PAD_RIGHT);
}

inline string_t str_pad(const string_t &input, const int_t &pad_length) {
	return str_pad(input, pad_length, string_t(" "), STR_PAD_RIGHT);
}

// Implements PHP microtime() string mode.
// How: system_clock is sampled once, then formatted as "0.xxxxxxxx seconds" to mirror PHP's default contract.
inline string_t microtime() {
	const auto now = std::chrono::system_clock::now();
	const auto since_epoch = now.time_since_epoch();
	const auto micros_total = std::chrono::duration_cast<std::chrono::microseconds>(since_epoch).count();
	const auto seconds = micros_total / static_cast<std::int64_t>(1000000);
	const auto micros_part = micros_total % static_cast<std::int64_t>(1000000);

	std::ostringstream stream;
	stream << std::fixed << std::setprecision(8)
		<< (static_cast<double>(micros_part) / 1000000.0)
		<< ' ' << seconds;
	return string_t(stream.str());
}

// Implements the numeric branch of PHP microtime(true).
// How: the helper is split out explicitly because the current runtime does not model a string|float union return type for one overload.
inline float_t microtime(bool_t as_float) {
	if (!as_float.native_value()) {
		throw std::logic_error("microtime(false) is not supported; use microtime() for string form");
	}

	const auto now = std::chrono::system_clock::now();
	const auto since_epoch = now.time_since_epoch();
	const auto micros_total = std::chrono::duration_cast<std::chrono::microseconds>(since_epoch).count();
	return float_t(static_cast<double>(micros_total) / 1000000.0);
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(const string_t &value) {
	return value;
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(const int_t &value) {
	return string_t(std::to_string(value.native_value()));
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(const float_t &value) {
	std::ostringstream stream;
	stream << value.native_value();
	return string_t(stream.str());
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(const bool_t &value) {
	return string_t(value.native_value() ? "1" : "");
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(null_t) {
	return string_t("");
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(nullopt_t) {
	return string_t("");
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(nullptr_t) {
	return string_t("");
}

// Converts one dynamic table value into its PHP echo/string representation.
// How: scalar branches delegate to the existing overloads; missing-like branches stringify to empty string.
inline string_t to_string(const mixed_t &value) {
	switch (value.kind()) {
		case mixed_t::kind_t::null_v:
			return string_t("");
		case mixed_t::kind_t::bool_v:
			return to_string(value.bool_value());
		case mixed_t::kind_t::int_v:
			return to_string(value.int_value());
		case mixed_t::kind_t::float_v:
			return to_string(value.float_value());
		case mixed_t::kind_t::string_v: {
			const auto *s = value.string_if();
			return s == nullptr ? string_t("") : to_string(*s);
		}
		case mixed_t::kind_t::table_v:
		case mixed_t::kind_t::shared_table_v:
		case mixed_t::kind_t::weak_table_v:
			return string_t("Array");
		case mixed_t::kind_t::dynamic_v:
			return string_t("Object");
	}
	return string_t("");
}


template <typename T>
// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(const nullable<T> &value) {
	if (!value.has_value().native_value()) {
		return string_t("");
	}
	return to_string(value.value());
}

// Prints one runtime value according to the PHP echo contract implemented by the prototype.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void echo_one(const string_t &value) {
	std::cout << value.native_value();
}

template <typename T>
requires requires (const std::remove_cvref_t<T> &value) {
	{ to_string(value) } -> std::same_as<string_t>;
}
// Prints one runtime value according to the PHP echo contract implemented by the prototype.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void echo_one(T &&value) {
	std::cout << to_string(std::forward<T>(value)).native_value();
}

// Prints one or more values using the runtime echo helpers.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void echo() {
}

template <typename... Args>
// Prints one or more values using the runtime echo helpers.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void echo(Args &&...args) {
	(echo_one(std::forward<Args>(args)), ...);
}

template <typename Fn>
requires requires (Fn &&fn) {
	std::forward<Fn>(fn)();
}
// Evaluates one deferred echo operand and prints it.
// How: the thunk form preserves PHP left-to-right operand evaluation when the generator wants one logical echo call.
inline void echo_eval_one(Fn &&fn) {
	echo_one(std::forward<Fn>(fn)());
}

template <typename... Fns>
// Evaluates deferred echo operands left-to-right and prints them.
// How: a comma-fold over thunk invocations preserves sequencing while still allowing the generator to emit one runtime call.
inline void echo_eval(Fns &&...fns) {
	(echo_eval_one(std::forward<Fns>(fns)), ...);
}

// Implements PHP strict identity for two null sentinels.
// How: strict identity treats identical null sentinels as equal without consulting wrapper operator overloads.
inline bool_t identical(null_t, null_t) {
	return bool_t(true);
}

// Implements PHP strict identity between null and nullable<T> when the nullable is empty.
// How: this is the one cross-type exception to the exact-type identity rule currently adopted by the runtime.
template <typename T>
inline bool_t identical(null_t, const nullable<T> &right) {
	return bool_t(!right.has_value().native_value());
}

// Implements PHP strict identity between nullable<T> and null when the nullable is empty.
// How: this is the symmetric form of the null-vs-nullable exception.
template <typename T>
inline bool_t identical(const nullable<T> &left, null_t) {
	return bool_t(!left.has_value().native_value());
}

// Implements PHP strict identity for two nullable values of the same exact type.
// How: empty state matches empty state; present values recurse into the same identity helper for the contained exact type.
template <typename T>
inline bool_t identical(const nullable<T> &left, const nullable<T> &right) {
	if (!left.has_value().native_value() && !right.has_value().native_value()) {
		return bool_t(true);
	}
	if (left.has_value().native_value() != right.has_value().native_value()) {
		return bool_t(false);
	}
	return identical(left.value(), right.value());
}

// Implements PHP strict identity between null and shared ownership wrappers.
// How: an empty shared handle represents PHP null at the runtime comparison layer.
template <typename T>
inline bool_t identical(null_t, const shared_p<T> &right) {
	return bool_t(!right.has_value().native_value());
}

// Implements PHP strict identity between shared ownership wrappers and null.
// How: an empty shared handle represents PHP null at the runtime comparison layer.
template <typename T>
inline bool_t identical(const shared_p<T> &left, null_t) {
	return bool_t(!left.has_value().native_value());
}

// Implements PHP strict identity between null and unique ownership wrappers.
// How: an empty unique handle represents PHP null at the runtime comparison layer.
template <typename T>
inline bool_t identical(null_t, const unique_p<T> &right) {
	return bool_t(!right.has_value().native_value());
}

// Implements PHP strict identity between unique ownership wrappers and null.
// How: an empty unique handle represents PHP null at the runtime comparison layer.
template <typename T>
inline bool_t identical(const unique_p<T> &left, null_t) {
	return bool_t(!left.has_value().native_value());
}

// Implements PHP strict identity for shared ownership wrappers using object identity.
// How: aliases are identical only when they point at the exact same managed object.
template <typename T>
inline bool_t identical(const shared_p<T> &left, const shared_p<T> &right) {
	return bool_t(left.get() == right.get());
}

// Implements PHP strict identity between a raw object pointer and a shared wrapper.
// How: enum instance methods compare $this against canonical shared case handles by underlying address.
template <typename T>
inline bool_t identical(const T *left, const shared_p<T> &right) {
	return bool_t(left == right.get());
}

// Implements PHP strict identity between a shared wrapper and a raw object pointer.
// How: enum instance methods compare canonical shared case handles against $this by underlying address.
template <typename T>
inline bool_t identical(const shared_p<T> &left, const T *right) {
	return bool_t(left.get() == right);
}

// Implements PHP strict identity for unique ownership wrappers using object identity.
// How: the comparison observes the managed object address rather than any pointed-to value.
template <typename T>
inline bool_t identical(const unique_p<T> &left, const unique_p<T> &right) {
	return bool_t(left.get() == right.get());
}

// Implements PHP strict identity between a dynamic mixed_t and null.
// How: mixed_t is a tagged PHP value container, so strict identity must inspect the active kind rather than reject the comparison as a generic cross-type mismatch.
inline bool_t identical(const mixed_t &left, null_t) {
	return bool_t(left.kind() == mixed_t::kind_t::null_v);
}

// Implements PHP strict identity between null and a dynamic mixed_t.
// How: this is the symmetric form of the mixed_t-null identity rule so overload resolution never falls through to the generic cross-type false path.
inline bool_t identical(null_t, const mixed_t &right) {
	return bool_t(right.kind() == mixed_t::kind_t::null_v);
}

// Implements PHP strict identity for same-type runtime values not needing special object/null handling.
// How: the helper keeps strict comparison in the PHP helper layer and delegates exact-type value equality to the runtime operator surface.
template <typename T>
inline bool_t identical(const T &left, const T &right) {
	return bool_t(left == right);
}

// Implements PHP strict identity for differing runtime value categories.
// How: the helper returns false because strict identity currently requires exact type equality except for null vs nullable<T>.
template <typename Left, typename Right>
requires (!std::is_same_v<std::remove_cvref_t<Left>, std::remove_cvref_t<Right>>)
inline bool_t identical(const Left &, const Right &) {
	return bool_t(false);
}

// Implements PHP strict non-identity as the inverse of the strict identity helper.
// How: one source of truth avoids drift between special-case identical overloads and their negated form.
template <typename Left, typename Right>
inline bool_t not_identical(const Left &left, const Right &right) {
	return !identical(left, right);
}

// Implements PHP-style concatenation assignment for wrapped strings.
// How: the helper mutates the left-hand side in place through string_t::append and returns the updated wrapper by reference.
inline string_t &concat_assign(string_t &left, const string_t &right) {
	left.append(right);
	return left;
}

namespace detail {

enum class probe_state : std::uint8_t {
	invalid,
	missing,
	present_null,
	present_value,
};

template <typename T>
struct probe_result final {
	probe_state state;
	const T *value;
};

// Formalizes the runtime countable contract for hash-compatible mixed_t carriers.
// How: only one unwrap step is performed, so nested mixed_t values that themselves hold hashes are handled by the caller explicitly without accidental recursive unwrapping.
inline const hash_t<mixed_t> &countable_hash_or_throw(const mixed_t &value, const char *operation) {
	if (const auto *table = value.table_if()) {
		return *table;
	}
	if (const auto *shared_table = value.shared_table_if()) {
		return *shared_table->get();
	}
	if (const auto *weak_table = value.weak_table_if()) {
		auto locked = weak_table->lock();
		if (static_cast<bool>(locked)) {
			return *locked;
		}
		throw std::runtime_error(std::string("php::") + operation + "(mixed_t) expects live hash-compatible mixed_t");
	}
	throw std::runtime_error(std::string("php::") + operation + "(mixed_t) expects hash-compatible mixed_t");
}

// Centralizes the narrowed Prism++ emptiness contract for plain one-value checks.
// How: this stays intentionally smaller than PHP falsiness; only null, empty string, and empty countables are empty.
inline bool_t empty_scalar(null_t) {
	return bool_t(true);
}

inline bool_t empty_scalar(nullopt_t) {
	return bool_t(true);
}

inline bool_t empty_scalar(nullptr_t) {
	return bool_t(true);
}

inline bool_t empty_scalar(const string_t &value) {
	return value.empty();
}

inline bool_t empty_scalar(const bool_t &) {
	return bool_t(false);
}

inline bool_t empty_scalar(const int_t &) {
	return bool_t(false);
}

inline bool_t empty_scalar(const float_t &) {
	return bool_t(false);
}

template <typename T>
inline bool_t empty_scalar(const nullable<T> &value) {
	if (!value.has_value().native_value()) {
		return bool_t(true);
	}
	return empty_scalar(value.value());
}

template <typename T>
inline bool_t empty_scalar(const shared_p<T> &value) {
	return bool_t(!value.has_value().native_value());
}

template <typename T>
inline bool_t empty_scalar(const unique_p<T> &value) {
	return bool_t(!value.has_value().native_value());
}

template <typename T>
inline bool_t empty_scalar(const weak_p<T> &value) {
	return bool_t(value.expired().native_value());
}

template <typename T>
requires (
	!std::is_same_v<std::remove_cvref_t<T>, null_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, nullopt_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, nullptr_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, mixed_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, string_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, bool_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, int_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, float_t>
	&& !requires (const std::remove_cvref_t<T> &value) {
		value.has_value();
	}
	&& !requires (const std::remove_cvref_t<T> &value) {
		value.expired();
	}
)
inline bool_t empty_scalar(T &&) {
	return bool_t(false);
}

// Centralizes the narrowed Prism++ probe contract for mixed_t values.
// How: missing and invalid are only possible for keyed probes; one-value probes classify null vs non-null without exposing storage internals.
inline probe_result<mixed_t> probe_value(const mixed_t &value) {
	if (value.kind() == mixed_t::kind_t::null_v) {
		return {probe_state::present_null, &value};
	}
	return {probe_state::present_value, &value};
}

template <typename T>
inline probe_result<T> probe_value(const T &value) {
	return {probe_state::present_value, &value};
}

inline probe_result<null_t> probe_value(null_t) {
	return {probe_state::present_null, nullptr};
}

inline probe_result<nullopt_t> probe_value(nullopt_t) {
	return {probe_state::present_null, nullptr};
}

inline probe_result<nullptr_t> probe_value(nullptr_t) {
	return {probe_state::present_null, nullptr};
}

template <typename T>
inline probe_result<nullable<T>> probe_value(const nullable<T> &value) {
	if (!value.has_value().native_value()) {
		return {probe_state::present_null, &value};
	}
	return {probe_state::present_value, &value};
}

template <typename T>
inline probe_result<shared_p<T>> probe_value(const shared_p<T> &value) {
	if (!value.has_value().native_value()) {
		return {probe_state::present_null, &value};
	}
	return {probe_state::present_value, &value};
}

template <typename T>
inline probe_result<unique_p<T>> probe_value(const unique_p<T> &value) {
	if (!value.has_value().native_value()) {
		return {probe_state::present_null, &value};
	}
	return {probe_state::present_value, &value};
}

template <typename T>
inline probe_result<weak_p<T>> probe_value(const weak_p<T> &value) {
	if (value.expired().native_value()) {
		return {probe_state::present_null, &value};
	}
	return {probe_state::present_value, &value};
}

inline bool_t isset_from_probe(probe_state state) {
	return bool_t(state == probe_state::present_value);
}

inline bool_t empty_from_probe(const probe_state state, const mixed_t *value) {
	if (state == probe_state::invalid || state == probe_state::missing || state == probe_state::present_null) {
		return bool_t(true);
	}
	if (value == nullptr) {
		return bool_t(false);
	}
	if (const auto *string_value = value->string_if()) {
		return string_value->empty();
	}
	if (const auto *table_value = value->table_if()) {
		return table_value->empty();
	}
	if (const auto *shared_table_value = value->shared_table_if()) {
		return shared_table_value->get()->empty();
	}
	if (const auto *weak_table_value = value->weak_table_if()) {
		auto locked = weak_table_value->lock();
		if (static_cast<bool>(locked)) {
			return locked.get()->empty();
		}
		return bool_t(true);
	}
	return bool_t(false);
}

// Centralizes integer-key normalization for countable helper overloads.
// How: callers can accept either native ints or int_t without duplicating negative-index handling logic.
inline bool vector_has_index(const std::size_t size, const int_t &key) {
	const auto native = key.native_value();
	if (native < 0) {
		return false;
	}
	return static_cast<std::size_t>(native) < size;
}

inline bool vector_has_index(const std::size_t size, const int key) {
	return key >= 0 && static_cast<std::size_t>(key) < size;
}

template <typename T>
struct is_countable_lookup_target : std::false_type {};

template <typename T>
struct is_countable_lookup_target<vector_t<T>> : std::true_type {};

template <typename T>
struct is_countable_lookup_target<hash_t<T>> : std::true_type {};

template <>
struct is_countable_lookup_target<mixed_t> : std::true_type {};

} // namespace detail

// Implements PHP count() for the currently supported vector wrapper subset.
// How: returns the runtime vector size widened into the standard int_t wrapper used by generated code.
template <typename T>
inline int_t count(const vector_t<T> &value) {
	return int_t(static_cast<std::int64_t>(value.size()));
}

// Implements PHP count() for any concrete hash_t payload.
// How: count() is a cardinality query on the wrapper itself, so the element payload type does not affect the logical size.
template <typename T>
inline int_t count(const hash_t<T> &value) {
	return int_t(static_cast<std::int64_t>(value.size()));
}

// Implements PHP count() for dynamic values that currently hold an array/hash payload.
// How: generated code may still keep arrays inside mixed_t, so count() unwraps exactly one dynamic layer and rejects non-countable payloads explicitly.
inline int_t count(const mixed_t &value) {
	return count(detail::countable_hash_or_throw(value, "count"));
}

// Implements PHP empty() for the current vector wrapper subset.
// How: emptiness is derived from the stable wrapper cardinality instead of exposing STL semantics directly to generated code.
template <typename T>
inline bool_t empty(const vector_t<T> &value) {
	return value.empty();
}

// Implements PHP empty() for any concrete hash_t payload.
// How: emptiness is derived from the wrapper cardinality, independent of payload type.
template <typename T>
inline bool_t empty(const hash_t<T> &value) {
	return value.empty();
}

// Implements the narrowed Prism++ empty() contract for one-value scalar and wrapper inputs.
// How: only null, empty string, and empty countables are empty; numeric zero, false, and "0" are intentionally not empty.
inline bool_t empty(null_t value) {
	return detail::empty_scalar(value);
}

inline bool_t empty(nullopt_t value) {
	return detail::empty_scalar(value);
}

inline bool_t empty(nullptr_t value) {
	return detail::empty_scalar(value);
}

inline bool_t empty(const bool_t &value) {
	return detail::empty_scalar(value);
}

inline bool_t empty(const int_t &value) {
	return detail::empty_scalar(value);
}

inline bool_t empty(const float_t &value) {
	return detail::empty_scalar(value);
}

inline bool_t empty(const string_t &value) {
	return detail::empty_scalar(value);
}

template <typename T>
inline bool_t empty(const nullable<T> &value) {
	return detail::empty_scalar(value);
}

template <typename T>
inline bool_t empty(const shared_p<T> &value) {
	return detail::empty_scalar(value);
}

template <typename T>
inline bool_t empty(const unique_p<T> &value) {
	return detail::empty_scalar(value);
}

template <typename T>
inline bool_t empty(const weak_p<T> &value) {
	return detail::empty_scalar(value);
}

// Implements PHP empty() for dynamic values under the narrowed Prism++ contract.
// How: mixed_t no longer reuses the strict countable contract; it treats missing/null/string-empty/empty-countable as empty and everything else as non-empty.
inline bool_t empty(const mixed_t &value) {
	const auto probe = detail::probe_value(value);
	return detail::empty_from_probe(probe.state, probe.value);
}

// Implements container-key isset() for vector wrappers.
// How: index existence is reduced to a bounds check, then mixed_t payloads get the extra null-sensitive check required by Prism++.
template <typename T>
inline bool_t isset(const vector_t<T> &value, const int_t &key) {
	if (!detail::vector_has_index(value.size(), key)) {
		return bool_t(false);
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		return detail::isset_from_probe(detail::probe_value(value.at(key)).state);
	}
	return bool_t(true);
}

template <typename T>
inline bool_t isset(const vector_t<T> &value, const int key) {
	return isset(value, int_t{static_cast<std::int64_t>(key)});
}

// Implements container-key isset() for hash wrappers.
// How: mixed_t payloads stay null-sensitive, while typed payloads treat key presence as value presence because they cannot represent PHP null by default construction.
template <typename T>
inline bool_t isset(const hash_t<T> &value, const int_t &key) {
	if (!value.has(key).native_value()) {
		return bool_t(false);
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		return detail::isset_from_probe(detail::probe_value(value.at(key)).state);
	}
	return bool_t(true);
}

template <typename T>
inline bool_t isset(const hash_t<T> &value, const string_t &key) {
	if (!value.has(key).native_value()) {
		return bool_t(false);
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		return detail::isset_from_probe(detail::probe_value(value.at(key)).state);
	}
	return bool_t(true);
}

template <typename T>
inline bool_t isset(const hash_t<T> &value, const char *key) {
	return isset(value, string_t{key});
}

template <typename T>
inline bool_t isset(const hash_t<T> &value, const int key) {
	return isset(value, int_t{static_cast<std::int64_t>(key)});
}

// Implements container-key empty() for vector wrappers.
// How: missing or invalid indices are empty, and mixed_t payloads reuse the narrowed one-value empty contract without mutating the container.
template <typename T>
inline bool_t empty(const vector_t<T> &value, const int_t &key) {
	if (!detail::vector_has_index(value.size(), key)) {
		return bool_t(true);
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		const auto probe = detail::probe_value(value.at(key));
		return detail::empty_from_probe(probe.state, probe.value);
	}
	return empty(value.at(key));
}

template <typename T>
inline bool_t empty(const vector_t<T> &value, const int key) {
	return empty(value, int_t{static_cast<std::int64_t>(key)});
}

// Implements container-key empty() for hash wrappers.
// How: missing keys are empty, mixed_t payloads keep null-sensitive and narrowed-string/countable behavior, and typed payloads defer to one-value empty() only when the key exists.
template <typename T>
inline bool_t empty(const hash_t<T> &value, const int_t &key) {
	if (!value.has(key).native_value()) {
		return bool_t(true);
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		const auto probe = detail::probe_value(value.at(key));
		return detail::empty_from_probe(probe.state, probe.value);
	}
	return empty(value.at(key));
}

template <typename T>
inline bool_t empty(const hash_t<T> &value, const string_t &key) {
	if (!value.has(key).native_value()) {
		return bool_t(true);
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		const auto probe = detail::probe_value(value.at(key));
		return detail::empty_from_probe(probe.state, probe.value);
	}
	return empty(value.at(key));
}

template <typename T>
inline bool_t empty(const hash_t<T> &value, const char *key) {
	return empty(value, string_t{key});
}

template <typename T>
inline bool_t empty(const hash_t<T> &value, const int key) {
	return empty(value, int_t{static_cast<std::int64_t>(key)});
}

// Implements container-key isset() for hash-compatible mixed_t carriers.
// How: invalid key kinds and non-countable bases are non-throwing here by policy, while present-null still resolves to false through the shared probe rules.
inline bool_t isset(const mixed_t &value, const mixed_t &key) {
	if (key.kind() == mixed_t::kind_t::int_v) {
		return isset(value, key.int_value());
	}
	if (key.kind() == mixed_t::kind_t::string_v) {
		return isset(value, *key.string_if());
	}
	return bool_t(false);
}

inline bool_t isset(const mixed_t &value, const int_t &key) {
	const auto *table = value.table_if();
	if (table == nullptr) {
		if (const auto *shared_table = value.shared_table_if()) {
			return isset(*shared_table->get(), key);
		}
		if (const auto *weak_table = value.weak_table_if()) {
			auto locked = weak_table->lock();
			if (static_cast<bool>(locked)) {
				return isset(*locked.get(), key);
			}
		}
		return bool_t(false);
	}
	return isset(*table, key);
}

inline bool_t isset(const mixed_t &value, const string_t &key) {
	const auto *table = value.table_if();
	if (table == nullptr) {
		if (const auto *shared_table = value.shared_table_if()) {
			return isset(*shared_table->get(), key);
		}
		if (const auto *weak_table = value.weak_table_if()) {
			auto locked = weak_table->lock();
			if (static_cast<bool>(locked)) {
				return isset(*locked.get(), key);
			}
		}
		return bool_t(false);
	}
	return isset(*table, key);
}

inline bool_t isset(const mixed_t &value, const char *key) {
	return isset(value, string_t{key});
}

inline bool_t isset(const mixed_t &value, const int key) {
	return isset(value, int_t{static_cast<std::int64_t>(key)});
}

// Implements container-key empty() for hash-compatible mixed_t carriers.
// How: invalid key kinds and non-countable bases are empty by policy, while valid lookups stay non-mutating and reuse the narrowed one-value empty contract.
inline bool_t empty(const mixed_t &value, const mixed_t &key) {
	if (key.kind() == mixed_t::kind_t::int_v) {
		return empty(value, key.int_value());
	}
	if (key.kind() == mixed_t::kind_t::string_v) {
		return empty(value, *key.string_if());
	}
	return bool_t(true);
}

inline bool_t empty(const mixed_t &value, const int_t &key) {
	const auto *table = value.table_if();
	if (table == nullptr) {
		if (const auto *shared_table = value.shared_table_if()) {
			return empty(*shared_table->get(), key);
		}
		if (const auto *weak_table = value.weak_table_if()) {
			auto locked = weak_table->lock();
			if (static_cast<bool>(locked)) {
				return empty(*locked.get(), key);
			}
		}
		return bool_t(true);
	}
	return empty(*table, key);
}

inline bool_t empty(const mixed_t &value, const string_t &key) {
	const auto *table = value.table_if();
	if (table == nullptr) {
		if (const auto *shared_table = value.shared_table_if()) {
			return empty(*shared_table->get(), key);
		}
		if (const auto *weak_table = value.weak_table_if()) {
			auto locked = weak_table->lock();
			if (static_cast<bool>(locked)) {
				return empty(*locked.get(), key);
			}
		}
		return bool_t(true);
	}
	return empty(*table, key);
}

inline bool_t empty(const mixed_t &value, const char *key) {
	return empty(value, string_t{key});
}

inline bool_t empty(const mixed_t &value, const int key) {
	return empty(value, int_t{static_cast<std::int64_t>(key)});
}

// Creates a shared dynamic-object carrier by copying one hash payload into shared storage.
// How: dynamic_t stays semantically distinct even though v1 storage is backed by hash_t<mixed_t>.
inline dynamic_t to_dynamic(const hash_t<mixed_t> &value) {
	return dynamic_t(std::make_shared<hash_t<mixed_t>>(value));
}

// Materializes one dynamic-object payload into a plain hash copy.
// How: the copy is explicit so array/hash semantics are not implied by shared dynamic storage.
inline hash_t<mixed_t> to_hash(const dynamic_t &value) {
	if (!static_cast<bool>(value)) {
		return hash_t<mixed_t>{};
	}
	return *value;
}

// Implements PHP by-value copy semantics for mixed runtime values.
// How: scalars and strings already copy by value through mixed_t::clone, while nested arrays detach by copying the underlying table into a fresh unique-owned mixed_t.
inline mixed_t value_copy(const mixed_t &value) {
	if (value.table_if() != nullptr) {
		return mixed_t{unique<hash_t<mixed_t>>(*value.table_if())};
	}
	if (value.shared_table_if() != nullptr) {
		return mixed_t{unique<hash_t<mixed_t>>(*value.shared_table_if()->get())};
	}
	if (value.weak_table_if() != nullptr) {
		auto locked = value.weak_table_if()->lock();
		if (static_cast<bool>(locked)) {
			return mixed_t{unique<hash_t<mixed_t>>(*locked.get())};
		}
		return mixed_t{null_t{}};
	}
	return value.clone();
}


// Implements the lowered isset contract across the currently supported runtime value categories.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset() {
	return bool_t(true);
}

// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(null_t) {
	return bool_t(false);
}

// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(nullopt_t) {
	return bool_t(false);
}

// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(nullptr_t) {
	return bool_t(false);
}

// Implements one-value isset semantics used by the variadic isset helper.
// How: mixed_t must preserve the null-sensitive contract for lowered array/property reads that return a dynamic value.
inline bool_t isset_one(const mixed_t &value) {
	return detail::isset_from_probe(detail::probe_value(value).state);
}

template <typename T>
// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(const nullable<T> &value) {
	return value.has_value();
}

template <typename T>
// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(const shared_p<T> &value) {
	return value.has_value();
}

template <typename T>
// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(const unique_p<T> &value) {
	return value.has_value();
}

template <typename T>
// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(const weak_p<T> &value) {
	return bool_t(!value.expired().native_value());
}

template <typename T>
requires (
	!std::is_same_v<std::remove_cvref_t<T>, null_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, nullopt_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, nullptr_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, mixed_t>
	&& !requires (const std::remove_cvref_t<T> &value) {
		value.has_value();
	}
	&& !requires (const std::remove_cvref_t<T> &value) {
		value.expired();
	}
)
// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(T &&) {
	return bool_t(true);
}

template <typename... Args>
requires (
	!(
		sizeof...(Args) == 2
		&& detail::is_countable_lookup_target<std::remove_cvref_t<std::tuple_element_t<0, std::tuple<Args...>>>>::value
	)
)
// Implements the lowered isset contract across the currently supported runtime value categories.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset(Args &&...args) {
	bool result = true;
	((result = result && isset_one(std::forward<Args>(args)).native_value()), ...);
	return bool_t(result);
}

// Implements the lowered unset helper for the currently supported mutable wrappers.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void unset() {
}

namespace detail {

// Deleted fallback used to keep unset semantics explicit at the runtime boundary.
// How: unsupported/custom types fail at compile time instead of silently inventing semantics.
template <typename T>
inline void apply_unset(T &) = delete;

// Implements one-value unset semantics used by the variadic unset helper.
// How: nullable wrappers drop back to the empty state immediately.
template <typename T>
inline void apply_unset(nullable<T> &value) {
	value.reset();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: shared ownership wrappers release the current managed object immediately.
template <typename T>
inline void apply_unset(shared_p<T> &value) {
	value.reset();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: unique ownership wrappers release the current managed object immediately.
template <typename T>
inline void apply_unset(unique_p<T> &value) {
	value.reset();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: weak wrappers forget the current observation target immediately.
template <typename T>
inline void apply_unset(weak_p<T> &value) {
	value.reset();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: the wrapped string owns its storage and clears it through the dedicated runtime hook.
inline void apply_unset(string_t &value) {
	value._unset_();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: the wrapped vector owns its storage and clears it through the dedicated runtime hook.
template <typename T>
inline void apply_unset(vector_t<T> &value) {
	value._unset_();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: integer wrappers reset to the runtime zero state.
inline void apply_unset(int_t &value) {
	value = int_t();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: floating-point wrappers reset to the runtime zero state.
inline void apply_unset(float_t &value) {
	value = float_t();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: boolean wrappers reset to false.
inline void apply_unset(bool_t &value) {
	value = bool_t();
}


// Reads one numeric memory field from /proc/self/status when available.
// How: Linux exposes resident and peak resident process memory in kilobytes through VmRSS and VmHWM.
[[nodiscard]] inline std::int64_t read_proc_status_kb(const char *field_name) {
	std::ifstream input("/proc/self/status");
	if (!input.is_open()) {
		return static_cast<std::int64_t>(-1);
	}

	std::string line;
	while (std::getline(input, line)) {
		if (line.rfind(field_name, 0) != 0) {
			continue;
		}

		std::istringstream stream(line.substr(std::char_traits<char>::length(field_name)));
		std::int64_t value_kb = 0;
		std::string unit;
		if (stream >> value_kb >> unit) {
			return value_kb;
		}
		return static_cast<std::int64_t>(-1);
	}

	return static_cast<std::int64_t>(-1);
}

// Returns the current resident process memory in bytes when the platform exposes it.
// How: Linux uses VmRSS; unsupported platforms fall back to zero because the runtime does not track allocator-internal usage yet.
[[nodiscard]] inline std::int64_t process_memory_usage_bytes() {
#if defined(__linux__)
	const std::int64_t value_kb = read_proc_status_kb("VmRSS:");
	if (value_kb >= 0) {
		return value_kb * static_cast<std::int64_t>(1024);
	}
#endif
	return static_cast<std::int64_t>(0);
}

// Returns the peak resident process memory in bytes when the platform exposes it.
// How: Linux prefers VmHWM; Unix-like fallbacks use getrusage where ru_maxrss is defined in kilobytes on Linux and bytes on macOS/BSD.
[[nodiscard]] inline std::int64_t process_peak_memory_usage_bytes() {
#if defined(__linux__)
	const std::int64_t value_kb = read_proc_status_kb("VmHWM:");
	if (value_kb >= 0) {
		return value_kb * static_cast<std::int64_t>(1024);
	}
#endif
#if defined(__unix__) || defined(__APPLE__)
	struct rusage usage {};
	if (getrusage(RUSAGE_SELF, &usage) == 0) {
		#if defined(__APPLE__)
		return static_cast<std::int64_t>(usage.ru_maxrss);
		#else
		return static_cast<std::int64_t>(usage.ru_maxrss) * static_cast<std::int64_t>(1024);
		#endif
	}
#endif
	return static_cast<std::int64_t>(0);
}

} // namespace detail

// Implements the lowered unset helper for the currently supported mutable wrappers.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
template <typename... Args>
inline void unset(Args &...args) {
	(detail::apply_unset(args), ...);
}

// Implements PHP memory_get_usage() in a process-level, benchmark-oriented form.
// How: the runtime currently reports resident process memory in bytes rather than Zend allocator internals.
[[nodiscard]] inline int_t memory_get_usage() {
	return int_t(detail::process_memory_usage_bytes());
}

// Implements PHP memory_get_usage(true|false) with the current prototype semantics.
// How: the bool parameter is accepted for PHP surface compatibility, but both branches currently return the same process-level byte count.
[[nodiscard]] inline int_t memory_get_usage(bool_t) {
	return int_t(detail::process_memory_usage_bytes());
}

// Implements PHP memory_get_peak_usage() in a process-level, benchmark-oriented form.
// How: the runtime currently reports peak resident process memory in bytes rather than Zend allocator internals.
[[nodiscard]] inline int_t memory_get_peak_usage() {
	return int_t(detail::process_peak_memory_usage_bytes());
}

// Implements PHP memory_get_peak_usage(true|false) with the current prototype semantics.
// How: the bool parameter is accepted for PHP surface compatibility, but both branches currently return the same process-level byte count.
[[nodiscard]] inline int_t memory_get_peak_usage(bool_t) {
	return int_t(detail::process_peak_memory_usage_bytes());
}

// Temporary lifetime-audit helper.
// How: exposes the visible strong-owner count for shared/weak wrappers so tests can prove whether a hidden strong alias still exists.
template <typename T>
[[nodiscard]] inline long debug_use_count(const shared_p<T> &value) {
	return value.debug_use_count();
}

template <typename T>
[[nodiscard]] inline long debug_use_count(const weak_p<T> &value) {
	return value.debug_use_count();
}

// Implements PHP-style weak reference creation for shared-owned objects.
// How: weak observers are modeled directly with weak_p so generated code does not need a second wrapper family.
template <typename T>
inline weak_p<T> weakref(const shared_p<T> &value) {
	return weak_p<T>(value);
}

// Implements PHP-style weak reference readback.
// How: locking a weak observer yields a shared handle, and empty state is represented by a null shared_p sentinel.
template <typename T>
inline shared_p<T> weakref_get(const weak_p<T> &value) {
	return value.lock();
}

} // namespace scpp::php

namespace scpp::php {

template <typename T>
// Implements PHP strict identity between an enum instance pointer and a compact enum value.
// How: enum instance methods compare `$this` against canonical case values by dereferencing the current instance.
inline ::scpp::bool_t identical(const T *left, const T &right) {
	return left == nullptr ? ::scpp::bool_t(false) : ::scpp::bool_t((*left) == right);
}

template <typename T>
// Implements PHP strict identity between a compact enum value and an enum instance pointer.
// How: the helper supports comparisons where the canonical case value appears on the left side.
inline ::scpp::bool_t identical(const T &left, const T *right) {
	return right == nullptr ? ::scpp::bool_t(false) : ::scpp::bool_t(left == (*right));
}

} // namespace scpp::php
