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
#include <utility>
#include <vector>

namespace scpp::str {

class string_parts_builder final {
private:
	vector_t<string_t> parts_;
	std::size_t byte_length_ = 0;

public:
	string_parts_builder() = default;

	void reserve(const std::size_t capacity) {
		parts_.reserve(capacity);
	}

	[[nodiscard]] std::size_t count() const noexcept {
		return parts_.size();
	}

	[[nodiscard]] std::size_t capacity() const noexcept {
		return parts_.capacity();
	}

	[[nodiscard]] std::size_t byte_length() const noexcept {
		return byte_length_;
	}

	void append(const string_t &value) {
		byte_length_ += value.native_value().size();
		parts_.append(value);
	}

	void append(string_t &&value) {
		byte_length_ += value.native_value().size();
		parts_.append(std::move(value));
	}

	void clear() noexcept {
		parts_.clear();
		byte_length_ = 0;
	}

	[[nodiscard]] string_t to_string() const {
		std::string out;
		out.reserve(byte_length_);
		for (const auto &part : parts_.native_value()) {
			out += part.native_value();
		}
		return string_t(std::move(out));
	}
};

[[nodiscard]] inline string_parts_builder string_parts_builder_create() {
	return string_parts_builder();
}

inline void string_parts_builder_reserve(string_parts_builder &builder, const int_t<> &capacity) {
	const auto native = capacity.native_value();
	if (native < 0) {
		throw scpp::ValueError("string_parts_builder_reserve(): capacity must be non-negative");
	}
	builder.reserve(static_cast<std::size_t>(native));
}

[[nodiscard]] inline int_t<> string_parts_builder_count(const string_parts_builder &builder) {
	return int_t<>(static_cast<std::int64_t>(builder.count()));
}

[[nodiscard]] inline int_t<> string_parts_builder_capacity(const string_parts_builder &builder) {
	return int_t<>(static_cast<std::int64_t>(builder.capacity()));
}

[[nodiscard]] inline int_t<> string_parts_builder_byte_len(const string_parts_builder &builder) {
	return int_t<>(static_cast<std::int64_t>(builder.byte_length()));
}

inline void string_parts_builder_append_string(string_parts_builder &builder, const string_t &value) {
	builder.append(value);
}

inline void string_parts_builder_append_int(string_parts_builder &builder, const int_t<> &value) {
	builder.append(string_t(std::to_string(value.native_value())));
}

inline void string_parts_builder_append_bool(string_parts_builder &builder, const bool_t &value) {
	builder.append(string_t(value.native_value() ? "1" : ""));
}

[[nodiscard]] inline string_t string_parts_builder_to_string(const string_parts_builder &builder) {
	return builder.to_string();
}

inline void string_parts_builder_clear(string_parts_builder &builder) {
	builder.clear();
}

inline int_t<> length(const string_t &value) {
	return int_t<>(static_cast<std::int64_t>(value.length_cp()));
}

inline int_t<> length(const nullable<string_t> &value) {
	if (!value.has_value().native_value()) {
		throw std::runtime_error("strlen(): nullable string is null");
	}
	return length(value.value());
}

inline int_t<> byte_length(const string_t &value) {
	return int_t<>(static_cast<std::int64_t>(value.byte_size()));
}

inline int_t<> byte_at(const string_t &value, const int_t<> &offset) {
	const auto native_offset = offset.native_value();
	const auto &native = value.native_value();
	if (native_offset < 0 || static_cast<std::size_t>(native_offset) >= native.size()) {
		return int_t<>(-1);
	}
	return int_t<>(static_cast<std::int64_t>(static_cast<unsigned char>(native[static_cast<std::size_t>(native_offset)])));
}

inline nullable<int_t<>> byte_find(const string_t &haystack, const string_t &needle) {
	const auto position = haystack.native_value().find(needle.native_value());
	if (position == std::string::npos) {
		return null;
	}
	return int_t<>(static_cast<std::int64_t>(position));
}

inline nullable<int_t<>> byte_find(const string_t &haystack, const string_t &needle, const int_t<> &offset) {
	const auto native_offset = offset.native_value();
	const auto &native = haystack.native_value();
	if (native_offset < 0 || static_cast<std::size_t>(native_offset) > native.size()) {
		throw scpp::ValueError("string_byte_find(): Argument #3 ($offset) must be contained in argument #1 ($haystack)");
	}
	const auto position = native.find(needle.native_value(), static_cast<std::size_t>(native_offset));
	if (position == std::string::npos) {
		return null;
	}
	return int_t<>(static_cast<std::int64_t>(position));
}

inline string_t byte_slice(const string_t &value, const int_t<> &offset, const int_t<> &length_value) {
	const auto native_offset = offset.native_value();
	const auto native_length = length_value.native_value();
	const auto &native = value.native_value();
	if (native_offset < 0 || native_length < 0 || static_cast<std::size_t>(native_offset) > native.size()) {
		return string_t("");
	}
	const auto start = static_cast<std::size_t>(native_offset);
	const auto available = native.size() - start;
	const auto requested = static_cast<std::size_t>(native_length);
	const auto used = requested < available ? requested : available;
	return string_t(native.substr(start, used));
}

inline bool_t byte_slice_equals(const string_t &value, const int_t<> &offset, const int_t<> &length_value, const string_t &literal) {
	const auto native_offset = offset.native_value();
	const auto native_length = length_value.native_value();
	if (native_offset < 0 || native_length < 0) {
		return bool_t(false);
	}
	const auto start = static_cast<std::size_t>(native_offset);
	const auto length = static_cast<std::size_t>(native_length);
	const auto &native = value.native_value();
	const auto &expected = literal.native_value();
	if (length != expected.size() || start > native.size() || length > native.size() - start) {
		return bool_t(false);
	}
	return bool_t(std::string_view(native).substr(start, length) == std::string_view(expected));
}

inline int_t<> utf8_codepoint_count(const string_t &value) {
	return int_t<>(static_cast<std::int64_t>(value.length_cp()));
}

inline int_t<> utf8_codepoint_at(const string_t &value, const int_t<> &index) {
	const auto native_index = index.native_value();
	if (native_index < 0) {
		return int_t<>(-1);
	}
	if (static_cast<std::size_t>(native_index) >= value.length_cp()) {
		return int_t<>(-1);
	}
	const auto cp = utf8::codepoint_at(value.native_value(), static_cast<std::size_t>(native_index));
	return int_t<>(static_cast<std::int64_t>(cp));
}

inline string_t utf8_slice_codepoints(const string_t &value, const int_t<> &start, const int_t<> &length_value) {
	const auto native_start = start.native_value();
	const auto native_length = length_value.native_value();
	if (native_start < 0 || native_length < 0) {
		return string_t("");
	}
	return value.substr_cp(static_cast<std::size_t>(native_start), static_cast<std::size_t>(native_length));
}

inline int_t<> grapheme_count(const string_t &value) {
	return int_t<>(static_cast<std::int64_t>(utf8::length_grapheme(value.native_value())));
}

inline string_t grapheme_slice(const string_t &value, const int_t<> &start, const int_t<> &length_value) {
	const auto native_start = start.native_value();
	const auto native_length = length_value.native_value();
	if (native_start < 0 || native_length < 0) {
		return string_t("");
	}
	return string_t(utf8::substr_grapheme(value.native_value(), static_cast<std::size_t>(native_start), static_cast<std::size_t>(native_length)));
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
