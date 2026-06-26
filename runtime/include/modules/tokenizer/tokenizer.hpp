#pragma once

#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/memory.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/string_t.hpp"

#include <cstddef>
#include <cstdint>
#include <string_view>
#include <vector>

namespace scpp::tokenizer {

enum token_kind_id : std::int64_t {
	token_eof = 0,
	token_identifier = 1,
	token_keyword = 2,
	token_number = 3,
	token_string = 4,
	token_symbol = 5,
	token_comment = 6,
	token_error = 7,
};

struct token_diagnostic final {
	const char *kind = "";
	std::size_t offset = 0;
	std::int64_t line = 0;
	std::int64_t column = 0;
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

} // namespace detail

struct token_buffer final {
	std::vector<std::int64_t> kind_ids;
	std::vector<std::size_t> start_offsets;
	std::vector<std::size_t> lengths;
	std::vector<std::int64_t> line_numbers;
	std::vector<std::int64_t> columns;
	std::vector<std::int64_t> flags;
	std::vector<std::size_t> line_start_offsets;
	std::vector<token_diagnostic> diagnostics;
	const char *language = "";
	std::size_t source_length = 0;

	explicit token_buffer(const char *language_value, const std::size_t source_length_value)
		: language(language_value), source_length(source_length_value) {
		const auto estimated_tokens = (source_length / 2U) + 8U;
		kind_ids.reserve(estimated_tokens);
		start_offsets.reserve(estimated_tokens);
		lengths.reserve(estimated_tokens);
		line_numbers.reserve(estimated_tokens);
		columns.reserve(estimated_tokens);
		flags.reserve(estimated_tokens);
		line_start_offsets.reserve(64U);
		diagnostics.reserve(4U);
	}

	void add_line_start(const std::size_t offset) {
		line_start_offsets.push_back(offset);
	}

	void add_token(
		const token_kind_id kind,
		const std::size_t start,
		const std::size_t length,
		const std::int64_t line,
		const std::int64_t column,
		const std::int64_t flag_value = 0
	) {
		kind_ids.push_back(static_cast<std::int64_t>(kind));
		start_offsets.push_back(start);
		lengths.push_back(length);
		line_numbers.push_back(line);
		columns.push_back(column);
		flags.push_back(flag_value);
	}

	void add_diagnostic(const char *kind, const std::size_t offset, const std::int64_t line, const std::int64_t column) {
		diagnostics.push_back(token_diagnostic{
			.kind = kind,
			.offset = offset,
			.line = line,
			.column = column,
		});
	}

	[[nodiscard]] std::int64_t token_count() const noexcept {
		return static_cast<std::int64_t>(kind_ids.size());
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
		out->set(string_t("lengths"), mixed_t(detail::make_int_column(lengths)));
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
			continue;
		}
		if (is_whitespace_no_newline(ch)) {
			advance_one();
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
				out.add_token(token_comment, start, offset - start, line, start_column);
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
				out.add_token(token_comment, start, offset - start, start_line, start_column);
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
			out.add_token(token_string, start, offset - start, start_line, start_column);
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
			out.add_token(keyword_fn(source, start, length) ? token_keyword : token_identifier, start, length, line, start_column);
			continue;
		}

		if (is_digit(ch)) {
			const auto start = offset;
			const auto start_column = column;
			advance_one();
			while (offset < source.size() && is_digit(static_cast<unsigned char>(source[offset]))) {
				advance_one();
			}
			out.add_token(token_number, start, offset - start, line, start_column);
			continue;
		}

		if (phs_variables && ch == static_cast<unsigned char>('$')) {
			out.add_token(token_symbol, offset, 1, line, column);
			advance_one();
			continue;
		}

		const auto start = offset;
		const auto start_column = column;
		std::size_t length = 1;
		if (offset + 1 < source.size()) {
			const auto next = source[offset + 1];
			if ((source[offset] == ':' && next == ':')
				|| (source[offset] == '=' && next == '>')
				|| (source[offset] == '=' && next == '=')
				|| (source[offset] == '!' && next == '=')
				|| (source[offset] == '&' && next == '&')
				|| (source[offset] == '|' && next == '|')
				|| (source[offset] == '?' && next == '?')
				|| (source[offset] == '-' && next == '>')) {
				length = 2;
			}
		}
		offset += length;
		column += static_cast<std::int64_t>(length);
		out.add_token(token_symbol, start, length, line, start_column);
	}

	out.add_token(token_eof, offset, 0, line, column);
	return out;
}

template <typename KeywordFn>
mixed_t tokenize_ascii_language(const string_t &source_value, const char *language, KeywordFn keyword_fn, const bool phs_variables) {
	return scan_ascii_language(source_value, language, keyword_fn, phs_variables).to_mixed();
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
	return int_t(static_cast<std::int64_t>(buffer->lengths.at(static_cast<std::size_t>(index.native_value()))));
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

[[nodiscard]] inline int_t<> phs_tokenize_count(const string_t &source) {
	return token_buffer_count(phs_tokenize_buffer(source));
}

[[nodiscard]] inline int_t<> jss_tokenize_count(const string_t &source) {
	return token_buffer_count(jss_tokenize_buffer(source));
}

} // namespace scpp::tokenizer
