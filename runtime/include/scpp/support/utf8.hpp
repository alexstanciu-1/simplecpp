#pragma once

#include <cstddef>
#include <cstdint>
#include <string>
#include <string_view>

#include <utfcpp/utf8.h>

namespace scpp::utf8 {

// Centralizes the UTF-8 helper layer behind a project-owned API.
// How: keep utf8cpp isolated here so the rest of the runtime depends only on stable scpp semantics.
struct decoded_range_t final {
	bool valid_utf8 = false;
	std::size_t start_byte = 0;
	std::size_t end_byte = 0;
};

[[nodiscard]] inline bool is_valid(std::string_view value) {
	const auto *first = value.data();
	const auto *last = value.data() + value.size();
	return ::utf8::is_valid(first, last);
}

[[nodiscard]] inline std::size_t byte_size(std::string_view value) noexcept {
	return value.size();
}

[[nodiscard]] inline std::size_t length_cp(std::string_view value) {
	if (!is_valid(value)) {
		// Falls back to byte count when the source bytes are not valid UTF-8.
		// How: preserve data and keep the operation total until explicit validation is threaded through boundaries.
		return value.size();
	}
	return static_cast<std::size_t>(::utf8::distance(value.begin(), value.end()));
}

[[nodiscard]] inline std::size_t cp_to_byte_index(std::string_view value, std::size_t cp_index) {
	if (!is_valid(value)) {
		return cp_index < value.size() ? cp_index : value.size();
	}

	const auto *first = value.data();
	const auto *last = value.data() + value.size();
	const auto *it = first;
	std::size_t current_cp = 0;
	while (it != last && current_cp < cp_index) {
		::utf8::unchecked::next(it);
		++current_cp;
	}
	return static_cast<std::size_t>(it - first);
}

[[nodiscard]] inline std::size_t byte_to_cp_index(std::string_view value, std::size_t byte_index) {
	const auto bounded = byte_index < value.size() ? byte_index : value.size();
	if (!is_valid(value)) {
		return bounded;
	}
	return static_cast<std::size_t>(::utf8::distance(value.begin(), value.begin() + static_cast<std::ptrdiff_t>(bounded)));
}

[[nodiscard]] inline decoded_range_t cp_window_to_byte_range(std::string_view value, std::size_t start_cp, std::size_t end_cp) {
	if (!is_valid(value)) {
		const auto size = value.size();
		const auto bounded_start = start_cp < size ? start_cp : size;
		auto bounded_end = end_cp < size ? end_cp : size;
		if (bounded_end < bounded_start) {
			bounded_end = bounded_start;
		}
		return decoded_range_t{ false, bounded_start, bounded_end };
	}

	const auto *first = value.data();
	const auto *last = value.data() + value.size();
	const auto *it = first;
	std::size_t current_cp = 0;
	std::size_t start_byte = value.size();
	std::size_t end_byte = value.size();
	while (it != last) {
		if (current_cp == start_cp) {
			start_byte = static_cast<std::size_t>(it - first);
		}
		if (current_cp == end_cp) {
			end_byte = static_cast<std::size_t>(it - first);
			break;
		}
		::utf8::unchecked::next(it);
		++current_cp;
	}
	if (start_byte == value.size() && start_cp >= current_cp) {
		start_byte = value.size();
	}
	if (end_byte == value.size() && end_cp >= current_cp) {
		end_byte = value.size();
	}
	if (end_byte < start_byte) {
		end_byte = start_byte;
	}
	return decoded_range_t{ true, start_byte, end_byte };
}

[[nodiscard]] inline std::string substr_cp(std::string_view value, std::size_t start_cp, std::size_t length_cp_value) {
	const auto range = cp_window_to_byte_range(value, start_cp, start_cp + length_cp_value);
	return std::string(value.substr(range.start_byte, range.end_byte - range.start_byte));
}

[[nodiscard]] inline std::string substr_cp_to_end(std::string_view value, std::size_t start_cp) {
	const auto range = cp_window_to_byte_range(value, start_cp, length_cp(value));
	return std::string(value.substr(range.start_byte, range.end_byte - range.start_byte));
}

} // namespace scpp::utf8
