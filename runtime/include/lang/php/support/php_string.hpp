#pragma once

#include "lang/php/support/php_common.hpp"

namespace scpp::php {

inline int_t strlen(const string_t &value) {
	return int_t(static_cast<std::int64_t>(value.length_cp()));
}

inline int_t strlen(const nullable<string_t> &value) {
	if (!value.has_value().native_value()) {
		throw std::runtime_error("strlen(): nullable string is null");
	}
	return php::strlen(value.value());
}

inline mixed_t strpos(const string_t &haystack, const string_t &needle) {
	const auto position = haystack.native_value().find(needle.native_value());
	if (position == std::string::npos) {
		return mixed_t(bool_t(false));
	}
	return mixed_t(int_t(static_cast<std::int64_t>(utf8::byte_to_cp_index(haystack.native_value(), position))));
}

inline mixed_t strpos(const string_t &haystack, const string_t &needle, const int_t &offset) {
	const auto start = normalize_php_forward_search_offset(haystack.length_cp(), offset.native_value(), "strpos");
	const auto start_byte = utf8::cp_to_byte_index(haystack.native_value(), start);
	const auto position = haystack.native_value().find(needle.native_value(), static_cast<std::size_t>(start_byte));
	if (position == std::string::npos) {
		return mixed_t(bool_t(false));
	}
	return mixed_t(int_t(static_cast<std::int64_t>(utf8::byte_to_cp_index(haystack.native_value(), position))));
}

inline mixed_t strrpos(const string_t &haystack, const string_t &needle) {
	const auto position = haystack.native_value().rfind(needle.native_value());
	if (position == std::string::npos) {
		return mixed_t(bool_t(false));
	}
	return mixed_t(int_t(static_cast<std::int64_t>(utf8::byte_to_cp_index(haystack.native_value(), position))));
}

inline mixed_t strrpos(const string_t &haystack, const string_t &needle, const int_t &offset) {
	const auto limit = normalize_php_reverse_search_limit(haystack.length_cp(), offset.native_value(), "strrpos");
	const auto limit_byte = utf8::cp_to_byte_index(haystack.native_value(), limit);
	const auto &native = haystack.native_value();
	const auto &needle_native = needle.native_value();
	if (offset.native_value() >= 0) {
		const auto position = native.rfind(needle_native);
		if (position == std::string::npos || position < limit_byte) {
			return mixed_t(bool_t(false));
		}
		return mixed_t(int_t(static_cast<std::int64_t>(utf8::byte_to_cp_index(haystack.native_value(), position))));
	}
	const auto position = native.rfind(needle_native, limit_byte);
	if (position == std::string::npos) {
		return mixed_t(bool_t(false));
	}
	return mixed_t(int_t(static_cast<std::int64_t>(utf8::byte_to_cp_index(haystack.native_value(), position))));
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
	const auto size = value.length_cp();
	const auto start = normalize_substr_start(size, offset.native_value());
	const auto end = normalize_substr_end(size, start, length.native_value());
	return value.substr_cp(start, end - start);
}

inline string_t substr(const string_t &value, const int_t &offset) {
	// Implements the practical PHP-like substr(string, offset) wrapper.
	// How: the wrapper reuses the same normalized start handling and slices through the end of the string.
	const auto size = value.length_cp();
	const auto start = normalize_substr_start(size, offset.native_value());
	return value.substr_cp(start);
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


inline mixed_t explode(const string_t &separator, const string_t &string, const int_t &limit) {
	if (separator.size() == 0) {
		throw ValueError("explode(): Argument #1 ($separator) must not be empty");
	}

	auto parts = hash_t<mixed_t>{};
	const auto &source = string.native_value();
	const auto &needle = separator.native_value();
	const auto limit_value = limit.native_value();

	if (limit_value == 0) {
		static_cast<void>(parts.append(mixed_t(string)));
		return mixed_t(unique<hash_t<mixed_t>>(std::move(parts)));
	}

	if (limit_value > 0) {
		const auto max_parts = static_cast<std::size_t>(limit_value);
		std::size_t cursor = 0;
		std::size_t produced = 0;
		while (produced + 1 < max_parts) {
			const auto found = source.find(needle, cursor);
			if (found == std::string::npos) {
				break;
			}
			static_cast<void>(parts.append(mixed_t(string_t(source.substr(cursor, found - cursor)))));
			cursor = found + needle.size();
			++produced;
		}
		static_cast<void>(parts.append(mixed_t(string_t(source.substr(cursor)))));
		return mixed_t(unique<hash_t<mixed_t>>(std::move(parts)));
	}

	std::vector<string_t> tokens;
	std::size_t cursor = 0;
	while (true) {
		const auto found = source.find(needle, cursor);
		if (found == std::string::npos) {
			tokens.emplace_back(source.substr(cursor));
			break;
		}
		tokens.emplace_back(source.substr(cursor, found - cursor));
		cursor = found + needle.size();
	}

	const auto drop_count = static_cast<std::size_t>(-limit_value);
	if (drop_count >= tokens.size()) {
		return mixed_t(unique<hash_t<mixed_t>>(std::move(parts)));
	}
	for (std::size_t index = 0; index + drop_count < tokens.size(); ++index) {
		static_cast<void>(parts.append(mixed_t(tokens[index])));
	}
	return mixed_t(unique<hash_t<mixed_t>>(std::move(parts)));
}

inline mixed_t explode(const string_t &separator, const string_t &string) {
	return explode(separator, string, PHP_INT_MAX);
}

inline string_t implode(const string_t &separator, const hash_t<string_t> &pieces) {
	std::string out;
	bool first = true;
	pieces.debug_visit_entries([&](const auto &, const string_t &value) {
		if (!first) {
			out += separator.native_value();
		}
		out += value.native_value();
		first = false;
	});
	return string_t(std::move(out));
}

inline string_t implode(const string_t &separator, const vector_t<string_t> &pieces) {
	std::string out;
	for (std::size_t index = 0; index < pieces.size(); ++index) {
		if (index != 0) {
			out += separator.native_value();
		}
		out += pieces.native_value()[index].native_value();
	}
	return string_t(std::move(out));
}

inline mixed_t hex2bin(const string_t &value) {
	const auto &source = value.native_value();
	if ((source.size() % 2) != 0) {
		return mixed_t(bool_t(false));
	}
	std::string out;
	out.reserve(source.size() / 2);
	for (std::size_t index = 0; index < source.size(); index += 2) {
		const auto high = hex_nibble_value(static_cast<unsigned char>(source[index]));
		const auto low = hex_nibble_value(static_cast<unsigned char>(source[index + 1]));
		if (high < 0 || low < 0) {
			return mixed_t(bool_t(false));
		}
		const auto byte = static_cast<char>((high << 4) | low);
		out.push_back(byte);
	}
	return mixed_t(string_t(std::move(out)));
}

inline string_t bin2hex(const string_t &value) {
	static constexpr char digits[] = "0123456789abcdef";
	const auto &source = value.native_value();
	std::string out;
	out.reserve(source.size() * 2);
	for (unsigned char byte : source) {
		out.push_back(digits[(byte >> 4) & 0x0f]);
		out.push_back(digits[byte & 0x0f]);
	}
	return string_t(std::move(out));
}


inline string_t number_format(const int_t &value, const int_t &decimals, const string_t &decimal_separator, const string_t &thousands_separator) {
	return number_format_from_double(static_cast<double>(value.native_value()), decimals.native_value(), decimal_separator, thousands_separator);
}

inline string_t number_format(const int_t &value, const int_t &decimals) {
	return number_format(value, decimals, string_t("."), string_t(","));
}

inline string_t number_format(const int_t &value) {
	return number_format(value, int_t(0));
}

inline string_t number_format(const float_t &value, const int_t &decimals, const string_t &decimal_separator, const string_t &thousands_separator) {
	return number_format_from_double(value.native_value(), decimals.native_value(), decimal_separator, thousands_separator);
}

inline string_t number_format(const float_t &value, const int_t &decimals) {
	return number_format(value, decimals, string_t("."), string_t(","));
}

inline string_t number_format(const float_t &value) {
	return number_format(value, int_t(0));
}

inline string_t number_format(const string_t &, const int_t &, const string_t &, const string_t &) {
	throw TypeError("number_format(): Argument #1 ($num) must be of type int|float, string given");
}

inline string_t number_format(const string_t &value, const int_t &decimals) {
	return number_format(value, decimals, string_t("."), string_t(","));
}

inline string_t number_format(const string_t &value) {
	return number_format(value, int_t(0));
}

inline string_t number_format(const bool_t &value, const int_t &decimals, const string_t &decimal_separator, const string_t &thousands_separator) {
	return number_format_from_double(value.native_value() ? 1.0 : 0.0, decimals.native_value(), decimal_separator, thousands_separator);
}

inline string_t number_format(const bool_t &value, const int_t &decimals) {
	return number_format(value, decimals, string_t("."), string_t(","));
}

inline string_t number_format(const bool_t &value) {
	return number_format(value, int_t(0));
}

inline string_t number_format(const mixed_t &value, const int_t &decimals, const string_t &decimal_separator, const string_t &thousands_separator) {
	switch (value.kind()) {
		case mixed_t::kind_t::null_v:
			return number_format_from_double(0.0, decimals.native_value(), decimal_separator, thousands_separator);
		case mixed_t::kind_t::bool_v:
			return number_format(value.bool_value(), decimals, decimal_separator, thousands_separator);
		case mixed_t::kind_t::int_v:
			return number_format(value.int_value(), decimals, decimal_separator, thousands_separator);
		case mixed_t::kind_t::float_v:
			return number_format(value.float_value(), decimals, decimal_separator, thousands_separator);
		case mixed_t::kind_t::string_v:
			throw TypeError("number_format(): Argument #1 ($num) must be of type int|float, string given");
		default:
			return number_format_from_double(0.0, decimals.native_value(), decimal_separator, thousands_separator);
	}
}

inline string_t number_format(const mixed_t &value, const int_t &decimals) {
	return number_format(value, decimals, string_t("."), string_t(","));
}

inline string_t number_format(const mixed_t &value) {
	return number_format(value, int_t(0));
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

template <typename T>
inline string_t to_string(const result_or_false<T> &value) {
	if (!value.has_value().native_value()) {
		return string_t("");
	}
	return to_string(value.value());
}

template <typename T>
inline string_t to_string(const result_or_bool<T> &value) {
	if (value.has_value().native_value()) {
		return to_string(value.value());
	}
	if (value.is_true().native_value()) {
		return to_string(bool_t(true));
	}
	return string_t("");
}

template <typename T>
inline string_t to_string(const result<T> &value) {
	if (!value.has_value().native_value()) {
		return value.error()->get_message();
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

} // namespace scpp::php
