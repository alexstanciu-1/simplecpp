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

[[nodiscard]] inline std::uint32_t codepoint_at(std::string_view value, std::size_t cp_index) {
	if (!is_valid(value)) {
		return cp_index < value.size() ? static_cast<std::uint32_t>(static_cast<unsigned char>(value[cp_index])) : static_cast<std::uint32_t>(0);
	}

	const auto *it = value.data();
	const auto *last = value.data() + value.size();
	std::size_t current_cp = 0;
	while (it != last) {
		const auto cp = ::utf8::unchecked::next(it);
		if (current_cp == cp_index) {
			return static_cast<std::uint32_t>(cp);
		}
		++current_cp;
	}
	return static_cast<std::uint32_t>(0);
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

[[nodiscard]] inline bool is_combining_mark(std::uint32_t cp) noexcept {
	return (cp >= 0x0300U && cp <= 0x036FU)
		|| (cp >= 0x1AB0U && cp <= 0x1AFFU)
		|| (cp >= 0x1DC0U && cp <= 0x1DFFU)
		|| (cp >= 0x20D0U && cp <= 0x20FFU)
		|| (cp >= 0xFE20U && cp <= 0xFE2FU);
}

[[nodiscard]] inline bool is_variation_selector(std::uint32_t cp) noexcept {
	return (cp >= 0xFE00U && cp <= 0xFE0FU) || (cp >= 0xE0100U && cp <= 0xE01EFU);
}

[[nodiscard]] inline bool is_emoji_modifier(std::uint32_t cp) noexcept {
	return cp >= 0x1F3FBU && cp <= 0x1F3FFU;
}

[[nodiscard]] inline bool joins_previous_grapheme(std::uint32_t cp) noexcept {
	return is_combining_mark(cp) || is_variation_selector(cp) || is_emoji_modifier(cp);
}

[[nodiscard]] inline std::size_t length_grapheme(std::string_view value) {
	if (!is_valid(value)) {
		return value.size();
	}
	const auto *it = value.data();
	const auto *last = value.data() + value.size();
	std::size_t count = 0;
	bool saw_any = false;
	bool join_next = false;
	std::uint32_t previous = 0;
	while (it != last) {
		const auto cp = static_cast<std::uint32_t>(::utf8::unchecked::next(it));
		const bool crlf_pair = previous == 0x000DU && cp == 0x000AU;
		const bool starts_new = !saw_any || (!join_next && !crlf_pair && !joins_previous_grapheme(cp) && cp != 0x200DU);
		if (starts_new) {
			++count;
		}
		join_next = cp == 0x200DU;
		previous = cp;
		saw_any = true;
	}
	return count;
}

[[nodiscard]] inline decoded_range_t grapheme_window_to_byte_range(std::string_view value, std::size_t start_grapheme, std::size_t end_grapheme) {
	if (!is_valid(value)) {
		const auto size = value.size();
		const auto bounded_start = start_grapheme < size ? start_grapheme : size;
		auto bounded_end = end_grapheme < size ? end_grapheme : size;
		if (bounded_end < bounded_start) {
			bounded_end = bounded_start;
		}
		return decoded_range_t{ false, bounded_start, bounded_end };
	}

	const auto *first = value.data();
	const auto *it = first;
	const auto *last = value.data() + value.size();
	std::size_t cluster_index = 0;
	std::size_t start_byte = value.size();
	std::size_t end_byte = value.size();
	bool saw_any = false;
	bool join_next = false;
	std::uint32_t previous = 0;
	while (it != last) {
		const auto cp_start = static_cast<std::size_t>(it - first);
		const auto cp = static_cast<std::uint32_t>(::utf8::unchecked::next(it));
		const bool crlf_pair = previous == 0x000DU && cp == 0x000AU;
		const bool starts_new = !saw_any || (!join_next && !crlf_pair && !joins_previous_grapheme(cp) && cp != 0x200DU);
		if (starts_new) {
			if (cluster_index == start_grapheme) {
				start_byte = cp_start;
			}
			if (cluster_index == end_grapheme) {
				end_byte = cp_start;
				break;
			}
			++cluster_index;
		}
		join_next = cp == 0x200DU;
		previous = cp;
		saw_any = true;
	}
	if (start_byte == value.size() && start_grapheme >= cluster_index) {
		start_byte = value.size();
	}
	if (end_byte == value.size() && end_grapheme >= cluster_index) {
		end_byte = value.size();
	}
	if (end_byte < start_byte) {
		end_byte = start_byte;
	}
	return decoded_range_t{ true, start_byte, end_byte };
}

[[nodiscard]] inline std::string substr_grapheme(std::string_view value, std::size_t start_grapheme, std::size_t length_grapheme_value) {
	const auto range = grapheme_window_to_byte_range(value, start_grapheme, start_grapheme + length_grapheme_value);
	return std::string(value.substr(range.start_byte, range.end_byte - range.start_byte));
}

} // namespace scpp::utf8
