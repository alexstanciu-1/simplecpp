#pragma once

#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/memory.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/string_t.hpp"

#include <cstddef>
#include <cstdint>
#include <string_view>

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

namespace detail {

struct token_buffer_builder final {
	unique_p<hash_t<mixed_t>> kind_ids = table_();
	unique_p<hash_t<mixed_t>> start_offsets = table_();
	unique_p<hash_t<mixed_t>> lengths = table_();
	unique_p<hash_t<mixed_t>> line_numbers = table_();
	unique_p<hash_t<mixed_t>> columns = table_();
	unique_p<hash_t<mixed_t>> flags = table_();
	unique_p<hash_t<mixed_t>> line_start_offsets = table_();
	unique_p<hash_t<mixed_t>> diagnostics = table_();
	std::int64_t token_count = 0;

	void add_line_start(const std::size_t offset) {
		static_cast<void>(line_start_offsets->append(mixed_t(int_t(static_cast<std::int64_t>(offset)))));
	}

	void add_token(
		const token_kind_id kind,
		const std::size_t start,
		const std::size_t length,
		const std::int64_t line,
		const std::int64_t column,
		const std::int64_t flag_value = 0
	) {
		static_cast<void>(kind_ids->append(mixed_t(int_t(static_cast<std::int64_t>(kind)))));
		static_cast<void>(start_offsets->append(mixed_t(int_t(static_cast<std::int64_t>(start)))));
		static_cast<void>(lengths->append(mixed_t(int_t(static_cast<std::int64_t>(length)))));
		static_cast<void>(line_numbers->append(mixed_t(int_t(line))));
		static_cast<void>(columns->append(mixed_t(int_t(column))));
		static_cast<void>(flags->append(mixed_t(int_t(flag_value))));
		++token_count;
	}

	void add_diagnostic(const char *kind, const std::size_t offset, const std::int64_t line, const std::int64_t column) {
		auto item = table_();
		item->set(string_t("kind"), mixed_t(string_t(kind)));
		item->set(string_t("offset"), mixed_t(int_t(static_cast<std::int64_t>(offset))));
		item->set(string_t("line"), mixed_t(int_t(line)));
		item->set(string_t("column"), mixed_t(int_t(column)));
		static_cast<void>(diagnostics->append(mixed_t(std::move(item))));
	}

	mixed_t finish(const char *language, const std::size_t source_length) {
		auto out = table_();
		out->set(string_t("schema_version"), mixed_t(int_t(1)));
		out->set(string_t("language"), mixed_t(string_t(language)));
		out->set(string_t("source_length"), mixed_t(int_t(static_cast<std::int64_t>(source_length))));
		out->set(string_t("token_count"), mixed_t(int_t(token_count)));
		out->set(string_t("diagnostic_count"), mixed_t(int_t(static_cast<std::int64_t>(diagnostics->size()))));
		out->set(string_t("kind_ids"), mixed_t(std::move(kind_ids)));
		out->set(string_t("start_offsets"), mixed_t(std::move(start_offsets)));
		out->set(string_t("lengths"), mixed_t(std::move(lengths)));
		out->set(string_t("line_numbers"), mixed_t(std::move(line_numbers)));
		out->set(string_t("columns"), mixed_t(std::move(columns)));
		out->set(string_t("flags"), mixed_t(std::move(flags)));
		out->set(string_t("line_start_offsets"), mixed_t(std::move(line_start_offsets)));
		out->set(string_t("diagnostics"), mixed_t(std::move(diagnostics)));
		return mixed_t(std::move(out));
	}
};

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
mixed_t tokenize_ascii_language(const string_t &source_value, const char *language, KeywordFn keyword_fn, const bool phs_variables) {
	const std::string_view source(source_value.native_value());
	token_buffer_builder out;
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
	return out.finish(language, source.size());
}

} // namespace detail

[[nodiscard]] inline mixed_t phs_tokenize(const string_t &source) {
	return detail::tokenize_ascii_language(source, "phs", detail::phs_keyword, true);
}

[[nodiscard]] inline mixed_t jss_tokenize(const string_t &source) {
	return detail::tokenize_ascii_language(source, "jss", detail::jss_keyword, false);
}

} // namespace scpp::tokenizer

