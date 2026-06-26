#pragma once

#include "core/string_support.hpp"
#include "scpp/result.hpp"
#include "scpp/vector_t.hpp"

#include <algorithm>
#include <cstddef>
#include <cstdint>
#include <limits>
#include <sstream>
#include <string>
#include <string_view>
#include <vector>

namespace scpp::str {

inline int_t<> length(const string_t &value) {
	return int_t<>(static_cast<std::int64_t>(value.length_cp()));
}

inline int_t<> length(const nullable<string_t> &value) {
	if (!value.has_value().native_value()) {
		throw std::runtime_error("strlen(): nullable string is null");
	}
	return length(value.value());
}

inline nullable<int_t<>> find(const string_t &haystack, const string_t &needle) {
	const auto position = haystack.native_value().find(needle.native_value());
	if (position == std::string::npos) {
		return null;
	}
	return int_t<>(static_cast<std::int64_t>(utf8::byte_to_cp_index(haystack.native_value(), position)));
}

inline nullable<int_t<>> find(const string_t &haystack, const string_t &needle, const int_t<> &offset) {
	const auto start = scpp::normalize_forward_search_offset(haystack.length_cp(), offset.native_value(), "strpos");
	const auto start_byte = utf8::cp_to_byte_index(haystack.native_value(), start);
	const auto position = haystack.native_value().find(needle.native_value(), static_cast<std::size_t>(start_byte));
	if (position == std::string::npos) {
		return null;
	}
	return int_t<>(static_cast<std::int64_t>(utf8::byte_to_cp_index(haystack.native_value(), position)));
}

inline nullable<int_t<>> rfind(const string_t &haystack, const string_t &needle) {
	const auto position = haystack.native_value().rfind(needle.native_value());
	if (position == std::string::npos) {
		return null;
	}
	return int_t<>(static_cast<std::int64_t>(utf8::byte_to_cp_index(haystack.native_value(), position)));
}

inline nullable<int_t<>> rfind(const string_t &haystack, const string_t &needle, const int_t<> &offset) {
	const auto limit = scpp::normalize_reverse_search_limit(haystack.length_cp(), offset.native_value(), "strrpos");
	const auto limit_byte = utf8::cp_to_byte_index(haystack.native_value(), limit);
	const auto &native = haystack.native_value();
	const auto &needle_native = needle.native_value();
	if (offset.native_value() >= 0) {
		const auto position = native.rfind(needle_native);
		if (position == std::string::npos || position < limit_byte) {
			return null;
		}
		return int_t<>(static_cast<std::int64_t>(utf8::byte_to_cp_index(haystack.native_value(), position)));
	}
	const auto position = native.rfind(needle_native, limit_byte);
	if (position == std::string::npos) {
		return null;
	}
	return int_t<>(static_cast<std::int64_t>(utf8::byte_to_cp_index(haystack.native_value(), position)));
}

inline string_t lower(const string_t &value) {
	std::string out = value.native_value();
	std::transform(out.begin(), out.end(), out.begin(), scpp::ascii_to_lower);
	return string_t(std::move(out));
}

inline string_t upper(const string_t &value) {
	std::string out = value.native_value();
	std::transform(out.begin(), out.end(), out.begin(), scpp::ascii_to_upper);
	return string_t(std::move(out));
}

inline string_t lcfirst(const string_t &value) {
	if (value.size() == 0) {
		return value;
	}
	std::string out = value.native_value();
	out[0] = scpp::ascii_to_lower(out[0]);
	return string_t(std::move(out));
}

inline string_t ucfirst(const string_t &value) {
	if (value.size() == 0) {
		return value;
	}
	std::string out = value.native_value();
	out[0] = scpp::ascii_to_upper(out[0]);
	return string_t(std::move(out));
}

inline bool_t starts_with(const string_t &haystack, const string_t &needle) {
	const auto &left = haystack.native_value();
	const auto &right = needle.native_value();
	if (right.size() > left.size()) {
		return bool_t(false);
	}
	return bool_t(left.compare(0, right.size(), right) == 0);
}

inline bool_t ends_with(const string_t &haystack, const string_t &needle) {
	const auto &left = haystack.native_value();
	const auto &right = needle.native_value();
	if (right.size() > left.size()) {
		return bool_t(false);
	}
	return bool_t(left.compare(left.size() - right.size(), right.size(), right) == 0);
}

inline bool_t contains(const string_t &haystack, const string_t &needle) {
	return bool_t(haystack.native_value().find(needle.native_value()) != std::string::npos);
}

inline string_t ltrim(const string_t &value) {
	const auto start = scpp::find_trim_left_index(value, nullptr);
	return scpp::trim_slice(value, start, value.size());
}

inline string_t ltrim(const string_t &value, const string_t &mask) {
	const auto start = scpp::find_trim_left_index(value, &mask);
	return scpp::trim_slice(value, start, value.size());
}

inline string_t rtrim(const string_t &value) {
	const auto end = scpp::find_trim_right_index(value, nullptr);
	return scpp::trim_slice(value, 0, end);
}

inline string_t rtrim(const string_t &value, const string_t &mask) {
	const auto end = scpp::find_trim_right_index(value, &mask);
	return scpp::trim_slice(value, 0, end);
}

inline string_t trim(const string_t &value) {
	const auto start = scpp::find_trim_left_index(value, nullptr);
	const auto end = scpp::find_trim_right_index(value, nullptr);
	return scpp::trim_slice(value, start, end);
}

inline string_t trim(const string_t &value, const string_t &mask) {
	const auto start = scpp::find_trim_left_index(value, &mask);
	const auto end = scpp::find_trim_right_index(value, &mask);
	return scpp::trim_slice(value, start, end);
}

inline string_t substr(const string_t &value, const int_t<> &offset, const int_t<> &length_value) {
	const auto size = value.length_cp();
	const auto start = scpp::normalize_substr_start(size, offset.native_value());
	const auto end = scpp::normalize_substr_end(size, start, length_value.native_value());
	return value.substr_cp(start, end - start);
}

inline string_t substr(const string_t &value, const int_t<> &offset) {
	const auto size = value.length_cp();
	const auto start = scpp::normalize_substr_start(size, offset.native_value());
	return value.substr_cp(start);
}

inline int_t<> substr_compare(const string_t &main_str, const string_t &str_value, const int_t<> &offset) {
	bool has_start = false;
	std::size_t start = 0;
	const auto has_valid_start = scpp::normalize_string_window(main_str.size(), offset.native_value(), has_start, start);
	const std::string_view left = has_valid_start ? std::string_view(main_str.native_value()).substr(start) : std::string_view();
	const std::string_view right = str_value.native_value();
	return int_t<>(static_cast<std::int64_t>(scpp::ascii_compare_sensitive(left, right)));
}

inline int_t<> substr_compare(const string_t &main_str, const string_t &str_value, const int_t<> &offset, const int_t<> &length_value) {
	bool has_start = false;
	std::size_t start = 0;
	const auto has_valid_start = scpp::normalize_string_window(main_str.size(), offset.native_value(), has_start, start);
	std::size_t end = start;
	const auto has_valid_window = has_valid_start && scpp::normalize_string_window_end(main_str.size(), start, length_value.native_value(), end);
	const std::string_view left = has_valid_window ? std::string_view(main_str.native_value()).substr(start, end - start) : std::string_view();
	const auto requested = length_value.native_value() >= 0 ? static_cast<std::size_t>(length_value.native_value()) : left.size();
	const std::string_view right = std::string_view(str_value.native_value()).substr(0, requested);
	return int_t<>(static_cast<std::int64_t>(scpp::ascii_compare_sensitive(left, right)));
}

inline int_t<> substr_compare(const string_t &main_str, const string_t &str_value, const int_t<> &offset, const int_t<> &length_value, const bool_t &case_insensitive) {
	bool has_start = false;
	std::size_t start = 0;
	const auto has_valid_start = scpp::normalize_string_window(main_str.size(), offset.native_value(), has_start, start);
	std::size_t end = start;
	const auto has_valid_window = has_valid_start && scpp::normalize_string_window_end(main_str.size(), start, length_value.native_value(), end);
	const std::string_view left = has_valid_window ? std::string_view(main_str.native_value()).substr(start, end - start) : std::string_view();
	const auto requested = length_value.native_value() >= 0 ? static_cast<std::size_t>(length_value.native_value()) : left.size();
	const std::string_view right = std::string_view(str_value.native_value()).substr(0, requested);
	const auto compare_result = case_insensitive.native_value() ? scpp::ascii_compare_insensitive(left, right) : scpp::ascii_compare_sensitive(left, right);
	return int_t<>(static_cast<std::int64_t>(compare_result));
}

inline string_t substr_replace(const string_t &subject, const string_t &replacement, const int_t<> &offset) {
	const auto size = subject.size();
	const auto start = scpp::normalize_substr_start(size, offset.native_value());
	std::string out;
	out.reserve(start + replacement.size());
	out.append(subject.native_value(), 0, start);
	out += replacement.native_value();
	return string_t(std::move(out));
}

inline string_t substr_replace(const string_t &subject, const string_t &replacement, const int_t<> &offset, const int_t<> &length_value) {
	const auto size = subject.size();
	const auto start = scpp::normalize_substr_start(size, offset.native_value());
	std::size_t end = start;
	if (length_value.native_value() < 0) {
		const auto distance_from_end = static_cast<std::uint64_t>(-(length_value.native_value() + 1)) + 1;
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
		const auto requested = static_cast<std::uint64_t>(length_value.native_value());
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

inline string_t replace(const string_t &search, const string_t &replacement, const string_t &subject) {
	if (search.size() == 0) {
		return subject;
	}
	const auto &source = subject.native_value();
	const auto &needle = search.native_value();
	const auto &replacement_native = replacement.native_value();
	std::string out;
	std::size_t cursor = 0;
	while (cursor < source.size()) {
		const auto found = source.find(needle, cursor);
		if (found == std::string::npos) {
			out.append(source, cursor, source.size() - cursor);
			break;
		}
		out.append(source, cursor, found - cursor);
		out += replacement_native;
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

inline string_t pad(const string_t &input, const int_t<> &pad_length, const string_t &pad_string, const int_t<> &pad_type) {
	if (pad_string.size() == 0) {
		throw scpp::ValueError("str_pad(): Argument #3 ($pad_string) must not be empty");
	}
	const auto pad_type_value = pad_type.native_value();
	if (pad_type_value != STR_PAD_LEFT.native_value() && pad_type_value != STR_PAD_RIGHT.native_value() && pad_type_value != STR_PAD_BOTH.native_value()) {
		throw scpp::ValueError("str_pad(): Argument #4 ($pad_type) must be STR_PAD_LEFT, STR_PAD_RIGHT, or STR_PAD_BOTH");
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

inline string_t pad(const string_t &input, const int_t<> &pad_length, const string_t &pad_string) {
	return pad(input, pad_length, pad_string, STR_PAD_RIGHT);
}

inline string_t pad(const string_t &input, const int_t<> &pad_length) {
	return pad(input, pad_length, string_t(" "), STR_PAD_RIGHT);
}

inline vector_t<string_t> split(const string_t &separator, const string_t &value, const int_t<> &limit) {
	if (separator.size() == 0) {
		throw scpp::ValueError("explode(): Argument #1 ($separator) must not be empty");
	}

	vector_t<string_t> parts;
	const auto &source = value.native_value();
	const auto &needle = separator.native_value();
	const auto limit_value = limit.native_value();

	if (limit_value == 0) {
		parts.append(value);
		return parts;
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
			parts.append(string_t(source.substr(cursor, found - cursor)));
			cursor = found + needle.size();
			++produced;
		}
		parts.append(string_t(source.substr(cursor)));
		return parts;
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
		return parts;
	}
	for (std::size_t index = 0; index + drop_count < tokens.size(); ++index) {
		parts.append(tokens[index]);
	}
	return parts;
}

inline vector_t<string_t> split(const string_t &separator, const string_t &value) {
	return split(separator, value, PHP_INT_MAX);
}

template <typename K>
inline string_t join(const string_t &separator, const hash_t<string_t, K> &pieces) {
	std::string out;
	bool first = true;
	pieces.debug_visit_entries([&](const auto &, const string_t &entry) {
		if (!first) {
			out += separator.native_value();
		}
		out += entry.native_value();
		first = false;
	});
	return string_t(std::move(out));
}

inline string_t join(const string_t &separator, const vector_t<string_t> &pieces) {
	std::string out;
	for (std::size_t index = 0; index < pieces.size(); ++index) {
		if (index != 0) {
			out += separator.native_value();
		}
		out += pieces.native_value()[index].native_value();
	}
	return string_t(std::move(out));
}

inline result<string_t> hex_decode(const string_t &value) {
	const auto &source = value.native_value();
	if ((source.size() % 2) != 0) {
		return error_t(string_t("hex_decode(): odd-length input"));
	}
	std::string out;
	out.reserve(source.size() / 2);
	for (std::size_t index = 0; index < source.size(); index += 2) {
		const auto high = scpp::hex_nibble_value(static_cast<unsigned char>(source[index]));
		const auto low = scpp::hex_nibble_value(static_cast<unsigned char>(source[index + 1]));
		if (high < 0 || low < 0) {
			return error_t(string_t("hex_decode(): invalid hexadecimal input"));
		}
		out.push_back(static_cast<char>((high << 4) | low));
	}
	return string_t(std::move(out));
}

inline string_t hex_encode(const string_t &value) {
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

inline string_t number_format(const int_t<> &value, const int_t<> &decimals, const string_t &decimal_separator, const string_t &thousands_separator) {
	return scpp::number_format_from_double(static_cast<double>(value.native_value()), decimals.native_value(), decimal_separator, thousands_separator);
}

inline string_t number_format(const int_t<> &value, const int_t<> &decimals) {
	return number_format(value, decimals, string_t("."), string_t(","));
}

inline string_t number_format(const int_t<> &value) {
	return number_format(value, int_t<>(0));
}

inline string_t number_format(const float_t &value, const int_t<> &decimals, const string_t &decimal_separator, const string_t &thousands_separator) {
	return scpp::number_format_from_double(value.native_value(), decimals.native_value(), decimal_separator, thousands_separator);
}

inline string_t number_format(const float_t &value, const int_t<> &decimals) {
	return number_format(value, decimals, string_t("."), string_t(","));
}

inline string_t number_format(const float_t &value) {
	return number_format(value, int_t<>(0));
}

inline string_t number_format(const string_t &, const int_t<> &, const string_t &, const string_t &) {
	throw scpp::TypeError("number_format(): Argument #1 ($num) must be of type int|float, string given");
}

inline string_t number_format(const string_t &value, const int_t<> &decimals) {
	return number_format(value, decimals, string_t("."), string_t(","));
}

inline string_t number_format(const string_t &value) {
	return number_format(value, int_t<>(0));
}

inline string_t number_format(const bool_t &value, const int_t<> &decimals, const string_t &decimal_separator, const string_t &thousands_separator) {
	return scpp::number_format_from_double(value.native_value() ? 1.0 : 0.0, decimals.native_value(), decimal_separator, thousands_separator);
}

inline string_t number_format(const bool_t &value, const int_t<> &decimals) {
	return number_format(value, decimals, string_t("."), string_t(","));
}

inline string_t number_format(const bool_t &value) {
	return number_format(value, int_t<>(0));
}

inline string_t number_format(const mixed_t &value, const int_t<> &decimals, const string_t &decimal_separator, const string_t &thousands_separator) {
	switch (value.kind()) {
		case mixed_t::kind_t::null_v:
			return scpp::number_format_from_double(0.0, decimals.native_value(), decimal_separator, thousands_separator);
		case mixed_t::kind_t::bool_v:
			return number_format(value.bool_value(), decimals, decimal_separator, thousands_separator);
		case mixed_t::kind_t::int_v:
			return number_format(value.int_value(), decimals, decimal_separator, thousands_separator);
		case mixed_t::kind_t::float_v:
			return number_format(value.float_value(), decimals, decimal_separator, thousands_separator);
		case mixed_t::kind_t::string_v:
			throw scpp::TypeError("number_format(): Argument #1 ($num) must be of type int|float, string given");
		default:
			return scpp::number_format_from_double(0.0, decimals.native_value(), decimal_separator, thousands_separator);
	}
}

inline string_t number_format(const mixed_t &value, const int_t<> &decimals) {
	return number_format(value, decimals, string_t("."), string_t(","));
}

inline string_t number_format(const mixed_t &value) {
	return number_format(value, int_t<>(0));
}

} // namespace scpp::str
