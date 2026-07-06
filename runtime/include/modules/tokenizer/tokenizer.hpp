#pragma once

#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/memory.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/string_t.hpp"

#include <cstddef>
#include <cstdint>
#include <limits>
#include <stdexcept>
#include <string>
#include <string_view>
#include <vector>

namespace scpp::tokenizer {

enum token_kind_id : std::uint16_t {
	token_eof = 0,
	token_identifier = 1,
	token_keyword = 2,
	token_number = 3,
	token_string = 4,
	token_symbol = 5,
	token_comment = 6,
	token_error = 7,
};

enum token_flag_id : std::uint16_t {
	token_flag_none = 0,
	token_flag_whitespace_before = 1U << 0U,
	token_flag_newline_before = 1U << 1U,
	token_flag_extended_length = 1U << 2U,
};

struct token_diagnostic final {
	const char *kind = "";
	std::uint32_t offset = 0;
	std::uint32_t line = 0;
	std::uint32_t column = 0;
};

struct token_extended_length final {
	std::uint32_t token_index = 0;
	std::uint32_t length = 0;
};

namespace detail {

template <typename Integer>
unique_p<hash_t<mixed_t>> make_int_column(const std::vector<Integer> &values) {
	auto column = table_();
	for (const auto value : values) {
		static_cast<void>(column->append(mixed_t(int_t(static_cast<std::int64_t>(value)))));
	}
	return column;
}

[[nodiscard]] inline std::uint32_t checked_u32(const std::size_t value, const char *field) {
	if (value > static_cast<std::size_t>(std::numeric_limits<std::uint32_t>::max())) {
		throw std::runtime_error(std::string("token_buffer ") + field + " is outside uint32 range");
	}
	return static_cast<std::uint32_t>(value);
}

[[nodiscard]] inline std::uint32_t checked_u32(const std::int64_t value, const char *field) {
	if (value < 0 || value > static_cast<std::int64_t>(std::numeric_limits<std::uint32_t>::max())) {
		throw std::runtime_error(std::string("token_buffer ") + field + " is outside uint32 range");
	}
	return static_cast<std::uint32_t>(value);
}

[[nodiscard]] inline std::uint16_t checked_u16(const std::int64_t value, const char *field) {
	if (value < 0 || value > static_cast<std::int64_t>(std::numeric_limits<std::uint16_t>::max())) {
		throw std::runtime_error(std::string("token_buffer ") + field + " is outside uint16 range");
	}
	return static_cast<std::uint16_t>(value);
}

} // namespace detail

struct token_buffer final {
	std::vector<std::uint16_t> kind_ids;
	std::vector<std::uint32_t> start_offsets;
	std::vector<std::uint16_t> lengths;
	std::vector<std::uint16_t> flags;
	std::vector<std::uint32_t> line_start_offsets;
	std::vector<token_extended_length> extended_lengths;
	std::vector<token_diagnostic> diagnostics;
	const char *language = "";
	std::uint32_t source_length = 0;

	explicit token_buffer(const char *language_value, const std::size_t source_length_value)
		: language(language_value), source_length(detail::checked_u32(source_length_value, "source_length")) {
		const auto estimated_tokens = (source_length / 2U) + 8U;
		kind_ids.reserve(estimated_tokens);
		start_offsets.reserve(estimated_tokens);
		lengths.reserve(estimated_tokens);
		flags.reserve(estimated_tokens);
		line_start_offsets.reserve(64U);
		extended_lengths.reserve(1U);
		diagnostics.reserve(4U);
	}

	void add_line_start(const std::size_t offset) {
		line_start_offsets.push_back(detail::checked_u32(offset, "line_start_offset"));
	}

	void add_token(
		const token_kind_id kind,
		const std::size_t start,
		const std::size_t length,
		const std::int64_t line,
		const std::int64_t column,
		const std::int64_t flag_value = 0
	) {
		(void)line;
		(void)column;
		auto stored_flags = detail::checked_u16(flag_value, "flags");
		if (length >= static_cast<std::size_t>(std::numeric_limits<std::uint16_t>::max())) {
			stored_flags = static_cast<std::uint16_t>(stored_flags | token_flag_extended_length);
		}
		kind_ids.push_back(static_cast<std::uint16_t>(kind));
		start_offsets.push_back(detail::checked_u32(start, "start_offset"));
		if (length >= static_cast<std::size_t>(std::numeric_limits<std::uint16_t>::max())) {
			extended_lengths.push_back(token_extended_length{
				.token_index = detail::checked_u32(lengths.size(), "extended_length_token_index"),
				.length = detail::checked_u32(length, "extended_length"),
			});
			lengths.push_back(std::numeric_limits<std::uint16_t>::max());
		} else {
			lengths.push_back(static_cast<std::uint16_t>(length));
		}
		flags.push_back(stored_flags);
	}

	void add_diagnostic(const char *kind, const std::size_t offset, const std::int64_t line, const std::int64_t column) {
		diagnostics.push_back(token_diagnostic{
			.kind = kind,
			.offset = detail::checked_u32(offset, "diagnostic_offset"),
			.line = detail::checked_u32(line, "diagnostic_line"),
			.column = detail::checked_u32(column, "diagnostic_column"),
		});
	}

	[[nodiscard]] std::int64_t token_count() const noexcept {
		return static_cast<std::int64_t>(kind_ids.size());
	}

	[[nodiscard]] std::uint32_t token_length(const std::size_t index) const {
		const auto length = lengths.at(index);
		if (length != std::numeric_limits<std::uint16_t>::max()) {
			return static_cast<std::uint32_t>(length);
		}
		for (const auto &row : extended_lengths) {
			if (row.token_index == index) {
				return row.length;
			}
		}
		throw std::runtime_error("token_buffer extended length row missing");
	}

	[[nodiscard]] std::uint32_t line_for_offset(const std::uint32_t offset) const noexcept {
		std::uint32_t line = 1;
		for (std::size_t i = 0; i < line_start_offsets.size(); ++i) {
			if (line_start_offsets[i] > offset) {
				break;
			}
			line = static_cast<std::uint32_t>(i + 1U);
		}
		return line;
	}

	[[nodiscard]] std::uint32_t column_for_offset(const std::uint32_t offset) const noexcept {
		std::uint32_t line_start = 0;
		for (const auto start : line_start_offsets) {
			if (start > offset) {
				break;
			}
			line_start = start;
		}
		return static_cast<std::uint32_t>((offset - line_start) + 1U);
	}

	mixed_t to_mixed() const {
		auto diagnostic_items = table_();
		for (const auto &diagnostic : diagnostics) {
			auto item = table_();
			item->set(string_t("kind"), mixed_t(string_t(diagnostic.kind)));
			item->set(string_t("offset"), mixed_t(int_t(static_cast<std::int64_t>(diagnostic.offset))));
			item->set(string_t("line"), mixed_t(int_t(diagnostic.line)));
			item->set(string_t("column"), mixed_t(int_t(diagnostic.column)));
			static_cast<void>(diagnostic_items->append(mixed_t(std::move(item))));
		}

		auto out = table_();
		out->set(string_t("schema_version"), mixed_t(int_t(1)));
		out->set(string_t("language"), mixed_t(string_t(language)));
		out->set(string_t("source_length"), mixed_t(int_t(static_cast<std::int64_t>(source_length))));
		out->set(string_t("token_count"), mixed_t(int_t(token_count())));
		out->set(string_t("diagnostic_count"), mixed_t(int_t(static_cast<std::int64_t>(diagnostics.size()))));
		out->set(string_t("kind_ids"), mixed_t(detail::make_int_column(kind_ids)));
		out->set(string_t("start_offsets"), mixed_t(detail::make_int_column(start_offsets)));
		auto expanded_lengths = std::vector<std::uint32_t>();
		expanded_lengths.reserve(lengths.size());
		auto line_numbers = std::vector<std::uint32_t>();
		auto columns = std::vector<std::uint32_t>();
		line_numbers.reserve(start_offsets.size());
		columns.reserve(start_offsets.size());
		std::size_t line_index = 0;
		for (std::size_t i = 0; i < start_offsets.size(); ++i) {
			const auto offset = start_offsets[i];
			while (line_index + 1U < line_start_offsets.size() && line_start_offsets[line_index + 1U] <= offset) {
				++line_index;
			}
			const auto line_start = line_start_offsets.empty() ? 0U : line_start_offsets[line_index];
			expanded_lengths.push_back(token_length(i));
			line_numbers.push_back(static_cast<std::uint32_t>(line_index + 1U));
			columns.push_back(static_cast<std::uint32_t>((offset - line_start) + 1U));
		}
		out->set(string_t("lengths"), mixed_t(detail::make_int_column(expanded_lengths)));
		out->set(string_t("line_numbers"), mixed_t(detail::make_int_column(line_numbers)));
		out->set(string_t("columns"), mixed_t(detail::make_int_column(columns)));
		out->set(string_t("flags"), mixed_t(detail::make_int_column(flags)));
		out->set(string_t("line_start_offsets"), mixed_t(detail::make_int_column(line_start_offsets)));
		out->set(string_t("diagnostics"), mixed_t(std::move(diagnostic_items)));
		return mixed_t(std::move(out));
	}
};

using token_buffer_t = shared_p<token_buffer>;

namespace detail {

[[nodiscard]] inline bool is_alpha(const unsigned char ch) noexcept {
	return (ch >= static_cast<unsigned char>('a') && ch <= static_cast<unsigned char>('z'))
		|| (ch >= static_cast<unsigned char>('A') && ch <= static_cast<unsigned char>('Z'))
		|| ch == static_cast<unsigned char>('_');
}

[[nodiscard]] inline bool is_digit(const unsigned char ch) noexcept {
	return ch >= static_cast<unsigned char>('0') && ch <= static_cast<unsigned char>('9');
}

[[nodiscard]] inline bool is_identifier_continue(const unsigned char ch) noexcept {
	return is_alpha(ch) || is_digit(ch);
}

[[nodiscard]] inline bool is_whitespace_no_newline(const unsigned char ch) noexcept {
	return ch == static_cast<unsigned char>(' ') || ch == static_cast<unsigned char>('\t') || ch == static_cast<unsigned char>('\r');
}

[[nodiscard]] inline bool slice_equals(std::string_view source, const std::size_t start, const std::size_t length, std::string_view literal) noexcept {
	return length == literal.size() && start <= source.size() && length <= source.size() - start && source.substr(start, length) == literal;
}

[[nodiscard]] inline bool phs_keyword(std::string_view source, const std::size_t start, const std::size_t length) noexcept {
	return slice_equals(source, start, length, "function")
		|| slice_equals(source, start, length, "class")
		|| slice_equals(source, start, length, "public")
		|| slice_equals(source, start, length, "private")
		|| slice_equals(source, start, length, "protected")
		|| slice_equals(source, start, length, "static")
		|| slice_equals(source, start, length, "return")
		|| slice_equals(source, start, length, "if")
		|| slice_equals(source, start, length, "else")
		|| slice_equals(source, start, length, "for")
		|| slice_equals(source, start, length, "while")
		|| slice_equals(source, start, length, "switch")
		|| slice_equals(source, start, length, "case")
		|| slice_equals(source, start, length, "default")
		|| slice_equals(source, start, length, "foreach")
		|| slice_equals(source, start, length, "try")
		|| slice_equals(source, start, length, "catch")
		|| slice_equals(source, start, length, "throw")
		|| slice_equals(source, start, length, "break")
		|| slice_equals(source, start, length, "continue")
		|| slice_equals(source, start, length, "true")
		|| slice_equals(source, start, length, "false")
		|| slice_equals(source, start, length, "null");
}

[[nodiscard]] inline bool jss_keyword(std::string_view source, const std::size_t start, const std::size_t length) noexcept {
	return slice_equals(source, start, length, "function")
		|| slice_equals(source, start, length, "class")
		|| slice_equals(source, start, length, "return")
		|| slice_equals(source, start, length, "if")
		|| slice_equals(source, start, length, "else")
		|| slice_equals(source, start, length, "for")
		|| slice_equals(source, start, length, "while")
		|| slice_equals(source, start, length, "let")
		|| slice_equals(source, start, length, "const")
		|| slice_equals(source, start, length, "true")
		|| slice_equals(source, start, length, "false")
		|| slice_equals(source, start, length, "null");
}

template <typename KeywordFn>
token_buffer scan_ascii_language(const string_t &source_value, const char *language, KeywordFn keyword_fn, const bool phs_variables) {
	const std::string_view source(source_value.native_value());
	token_buffer out(language, source.size());
	out.add_line_start(0);

	std::size_t offset = 0;
	std::int64_t line = 1;
	std::int64_t column = 1;
	bool whitespace_before = false;
	bool newline_before = false;

	const auto token_flags = [&]() {
		std::uint16_t value = token_flag_none;
		if (whitespace_before) {
			value = static_cast<std::uint16_t>(value | token_flag_whitespace_before);
		}
		if (newline_before) {
			value = static_cast<std::uint16_t>(value | token_flag_newline_before);
		}
		whitespace_before = false;
		newline_before = false;
		return value;
	};

	const auto advance_one = [&]() {
		++offset;
		++column;
	};

	const auto advance_newline = [&]() {
		++offset;
		++line;
		column = 1;
		out.add_line_start(offset);
	};

	while (offset < source.size()) {
		const auto ch = static_cast<unsigned char>(source[offset]);

		if (ch == static_cast<unsigned char>('\n')) {
			advance_newline();
			whitespace_before = true;
			newline_before = true;
			continue;
		}
		if (is_whitespace_no_newline(ch)) {
			advance_one();
			whitespace_before = true;
			continue;
		}

		if (ch == static_cast<unsigned char>('/') && offset + 1 < source.size()) {
			const auto next = static_cast<unsigned char>(source[offset + 1]);
			if (next == static_cast<unsigned char>('/')) {
				const auto start = offset;
				const auto start_column = column;
				offset += 2;
				column += 2;
				while (offset < source.size() && source[offset] != '\n') {
					advance_one();
				}
				out.add_token(token_comment, start, offset - start, line, start_column, token_flags());
				continue;
			}
			if (next == static_cast<unsigned char>('*')) {
				const auto start = offset;
				const auto start_line = line;
				const auto start_column = column;
				offset += 2;
				column += 2;
				bool closed = false;
				while (offset < source.size()) {
					if (source[offset] == '\n') {
						advance_newline();
						continue;
					}
					if (source[offset] == '*' && offset + 1 < source.size() && source[offset + 1] == '/') {
						offset += 2;
						column += 2;
						closed = true;
						break;
					}
					advance_one();
				}
				out.add_token(token_comment, start, offset - start, start_line, start_column, token_flags());
				if (!closed) {
					out.add_diagnostic("unterminated_comment", start, start_line, start_column);
				}
				continue;
			}
		}

		if (ch == static_cast<unsigned char>('"') || ch == static_cast<unsigned char>('\'')) {
			const auto quote = ch;
			const auto start = offset;
			const auto start_line = line;
			const auto start_column = column;
			advance_one();
			bool closed = false;
			while (offset < source.size()) {
				const auto current = static_cast<unsigned char>(source[offset]);
				if (current == static_cast<unsigned char>('\n')) {
					out.add_diagnostic("unterminated_string", start, start_line, start_column);
					break;
				}
				if (current == static_cast<unsigned char>('\\')) {
					advance_one();
					if (offset < source.size()) {
						advance_one();
					}
					continue;
				}
				if (current == quote) {
					advance_one();
					closed = true;
					break;
				}
				advance_one();
			}
			out.add_token(token_string, start, offset - start, start_line, start_column, token_flags());
			if (!closed && offset >= source.size()) {
				out.add_diagnostic("unterminated_string", start, start_line, start_column);
			}
			continue;
		}

		if (is_alpha(ch)) {
			const auto start = offset;
			const auto start_column = column;
			advance_one();
			while (offset < source.size() && is_identifier_continue(static_cast<unsigned char>(source[offset]))) {
				advance_one();
			}
			const auto length = offset - start;
			out.add_token(keyword_fn(source, start, length) ? token_keyword : token_identifier, start, length, line, start_column, token_flags());
			continue;
		}

		if (is_digit(ch)) {
			const auto start = offset;
			const auto start_column = column;
			advance_one();
			while (offset < source.size() && is_digit(static_cast<unsigned char>(source[offset]))) {
				advance_one();
			}
			out.add_token(token_number, start, offset - start, line, start_column, token_flags());
			continue;
		}

		if (phs_variables && ch == static_cast<unsigned char>('$')) {
			out.add_token(token_symbol, offset, 1, line, column, token_flags());
			advance_one();
			continue;
		}

		const auto start = offset;
		const auto start_column = column;
		std::size_t length = 1;
		if (offset + 2 < source.size()) {
			if ((source[offset] == '=' && source[offset + 1] == '=' && source[offset + 2] == '=')
				|| (source[offset] == '<' && source[offset + 1] == '=' && source[offset + 2] == '>')
				|| (source[offset] == '!' && source[offset + 1] == '=' && source[offset + 2] == '=')) {
				length = 3;
			}
		}
		if (offset + 1 < source.size()) {
			const auto next = source[offset + 1];
			if (length == 1 && ((source[offset] == ':' && next == ':')
				|| (source[offset] == '=' && next == '>')
				|| (source[offset] == '=' && next == '=')
				|| (source[offset] == '!' && next == '=')
				|| (source[offset] == '<' && next == '=')
				|| (source[offset] == '>' && next == '=')
				|| (source[offset] == '&' && next == '&')
				|| (source[offset] == '|' && next == '|')
				|| (source[offset] == '?' && next == '?')
				|| (source[offset] == '-' && next == '>'))) {
				length = 2;
			}
		}
		offset += length;
		column += static_cast<std::int64_t>(length);
		out.add_token(token_symbol, start, length, line, start_column, token_flags());
	}

	out.add_token(token_eof, offset, 0, line, column, token_flags());
	return out;
}

} // namespace detail

[[nodiscard]] inline token_buffer_t phs_tokenize_buffer(const string_t &source) {
	return shared<token_buffer>(detail::scan_ascii_language(source, "phs", detail::phs_keyword, true));
}

[[nodiscard]] inline token_buffer_t jss_tokenize_buffer(const string_t &source) {
	return shared<token_buffer>(detail::scan_ascii_language(source, "jss", detail::jss_keyword, false));
}

[[nodiscard]] inline int_t<> token_buffer_count(const token_buffer_t &buffer) {
	return int_t(buffer->token_count());
}

[[nodiscard]] inline int_t<> token_buffer_kind_id(const token_buffer_t &buffer, const int_t<> &index) {
	return int_t(buffer->kind_ids.at(static_cast<std::size_t>(index.native_value())));
}

[[nodiscard]] inline int_t<> token_buffer_start_offset(const token_buffer_t &buffer, const int_t<> &index) {
	return int_t(static_cast<std::int64_t>(buffer->start_offsets.at(static_cast<std::size_t>(index.native_value()))));
}

[[nodiscard]] inline int_t<> token_buffer_length(const token_buffer_t &buffer, const int_t<> &index) {
	return int_t(static_cast<std::int64_t>(buffer->token_length(static_cast<std::size_t>(index.native_value()))));
}

[[nodiscard]] inline int_t<> token_buffer_line(const token_buffer_t &buffer, const int_t<> &index) {
	return int_t(static_cast<std::int64_t>(buffer->line_for_offset(buffer->start_offsets.at(static_cast<std::size_t>(index.native_value())))));
}

[[nodiscard]] inline int_t<> token_buffer_column(const token_buffer_t &buffer, const int_t<> &index) {
	return int_t(static_cast<std::int64_t>(buffer->column_for_offset(buffer->start_offsets.at(static_cast<std::size_t>(index.native_value())))));
}

[[nodiscard]] inline int_t<> token_buffer_flags(const token_buffer_t &buffer, const int_t<> &index) {
	return int_t(static_cast<std::int64_t>(buffer->flags.at(static_cast<std::size_t>(index.native_value()))));
}

[[nodiscard]] inline mixed_t token_buffer_to_mixed(const token_buffer_t &buffer) {
	return buffer->to_mixed();
}

[[nodiscard]] inline token_buffer_t phs_tokenize(const string_t &source) {
	return phs_tokenize_buffer(source);
}

[[nodiscard]] inline token_buffer_t jss_tokenize(const string_t &source) {
	return jss_tokenize_buffer(source);
}

} // namespace scpp::tokenizer
