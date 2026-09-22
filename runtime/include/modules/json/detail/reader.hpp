#pragma once
#include "scpp/string_t.hpp"
#include <cstdint>
#include <stdexcept>
#include <string_view>

namespace scpp::json::reader_detail {
// Private parser unwind signal. Only this failure is converted to an in-band error;
// allocation failures and unrelated runtime exceptions retain their own contracts.
struct json_parse_failure final {
	const char *message;
	std::size_t position;
	const char *category;
};

[[noreturn]] inline void fail_json_parse(const char *message, const std::size_t position, const char *category = "syntax") {
	throw json_parse_failure{message, position, category};
}

[[nodiscard]] inline bool is_ascii_digit(const char ch) noexcept {
	return ch >= '0' && ch <= '9';
}

[[nodiscard]] inline std::string utf8_from_codepoint(const std::uint32_t codepoint) {
	std::string out;
	if (codepoint <= 0x7Fu) {
		out.push_back(static_cast<char>(codepoint));
		return out;
	}
	if (codepoint <= 0x7FFu) {
		out.push_back(static_cast<char>(0xC0u | ((codepoint >> 6u) & 0x1Fu)));
		out.push_back(static_cast<char>(0x80u | (codepoint & 0x3Fu)));
		return out;
	}
	if (codepoint <= 0xFFFFu) {
		out.push_back(static_cast<char>(0xE0u | ((codepoint >> 12u) & 0x0Fu)));
		out.push_back(static_cast<char>(0x80u | ((codepoint >> 6u) & 0x3Fu)));
		out.push_back(static_cast<char>(0x80u | (codepoint & 0x3Fu)));
		return out;
	}
	if (codepoint <= 0x10FFFFu) {
		out.push_back(static_cast<char>(0xF0u | ((codepoint >> 18u) & 0x07u)));
		out.push_back(static_cast<char>(0x80u | ((codepoint >> 12u) & 0x3Fu)));
		out.push_back(static_cast<char>(0x80u | ((codepoint >> 6u) & 0x3Fu)));
		out.push_back(static_cast<char>(0x80u | (codepoint & 0x3Fu)));
		return out;
	}
	throw std::runtime_error("json unicode error: invalid Unicode code point");
}

class json_reader {
protected:
	std::string_view input_;
	std::size_t pos_ = 0;

	[[nodiscard]] bool at_end() const noexcept {
		return pos_ >= input_.size();
	}

	[[nodiscard]] char peek() const noexcept {
		return at_end() ? '\0' : input_[pos_];
	}

	[[nodiscard]] char get() {
		if (at_end()) {
			fail_json_parse("unexpected end of input", pos_);
		}
		return input_[pos_++];
	}

	void skip_whitespace() noexcept {
		while (!at_end()) {
			const char ch = input_[pos_];
			if (ch == ' ' || ch == '\t' || ch == '\r' || ch == '\n') {
				++pos_;
				continue;
			}
			break;
		}
	}

	void expect_literal(const std::string_view literal) {
		if (input_.substr(pos_, literal.size()) != literal) {
			fail_json_parse("invalid literal", pos_);
		}
		pos_ += literal.size();
	}

	[[nodiscard]] std::uint32_t parse_hex4() {
		std::uint32_t value = 0;
		for (int i = 0; i < 4; ++i) {
			if (at_end()) {
				fail_json_parse("unfinished unicode escape", pos_, "unicode_escape");
			}
			const char ch = get();
			value <<= 4u;
			if (ch >= '0' && ch <= '9') {
				value |= static_cast<std::uint32_t>(ch - '0');
				continue;
			}
			if (ch >= 'a' && ch <= 'f') {
				value |= static_cast<std::uint32_t>(10 + ch - 'a');
				continue;
			}
			if (ch >= 'A' && ch <= 'F') {
				value |= static_cast<std::uint32_t>(10 + ch - 'A');
				continue;
			}
			fail_json_parse("invalid unicode escape", pos_ - 1, "unicode_escape");
		}
		return value;
	}

	[[nodiscard]] string_t parse_string() {
		const std::size_t start = pos_;
		if (get() != '"') {
			fail_json_parse("expected string", start);
		}

		std::string out;
		while (!at_end()) {
			const char ch = get();
			if (ch == '"') {
				return string_t(std::move(out));
			}
			if (static_cast<unsigned char>(ch) < 0x20u) {
				fail_json_parse("control character in string", pos_ - 1);
			}
			if (ch != '\\') {
				out.push_back(ch);
				continue;
			}

			if (at_end()) {
				fail_json_parse("unfinished escape sequence", pos_);
			}
			const char esc = get();
			switch (esc) {
				case '"': out.push_back('"'); break;
				case '\\': out.push_back('\\'); break;
				case '/': out.push_back('/'); break;
				case 'b': out.push_back('\b'); break;
				case 'f': out.push_back('\f'); break;
				case 'n': out.push_back('\n'); break;
				case 'r': out.push_back('\r'); break;
				case 't': out.push_back('\t'); break;
				case 'u': {
					const auto high = parse_hex4();
					if (high >= 0xD800u && high <= 0xDBFFu) {
						if (at_end() || get() != '\\' || at_end() || get() != 'u') {
							fail_json_parse("expected low surrogate after high surrogate", pos_, "unicode_escape");
						}
						const auto low = parse_hex4();
						if (low < 0xDC00u || low > 0xDFFFu) {
							fail_json_parse("invalid low surrogate", pos_ - 4, "unicode_escape");
						}
						const auto codepoint = 0x10000u + (((high - 0xD800u) << 10u) | (low - 0xDC00u));
						out += utf8_from_codepoint(codepoint);
						break;
					}
					if (high >= 0xDC00u && high <= 0xDFFFu) {
						fail_json_parse("unexpected low surrogate", pos_ - 4, "unicode_escape");
					}
					out += utf8_from_codepoint(high);
					break;
				}
				default:
					fail_json_parse("invalid escape sequence", pos_ - 1);
			}
		}

		fail_json_parse("unterminated string", start);
	}

	[[nodiscard]] std::string_view parse_number_token() {
		const std::size_t start = pos_;
		if (peek() == '-') {
			++pos_;
		}
		if (at_end()) {
			fail_json_parse("invalid number", start);
		}
		if (peek() == '0') {
			++pos_;
			if (!at_end() && is_ascii_digit(peek())) {
				fail_json_parse("leading zeros are not allowed", pos_);
			}
		} else {
			if (!is_ascii_digit(peek())) {
				fail_json_parse("invalid number", pos_);
			}
			while (!at_end() && is_ascii_digit(peek())) {
				++pos_;
			}
		}

		if (!at_end() && peek() == '.') {
			++pos_;
			if (at_end() || !is_ascii_digit(peek())) {
				fail_json_parse("invalid fraction", pos_);
			}
			while (!at_end() && is_ascii_digit(peek())) {
				++pos_;
			}
		}
		if (!at_end() && (peek() == 'e' || peek() == 'E')) {
			++pos_;
			if (!at_end() && (peek() == '+' || peek() == '-')) {
				++pos_;
			}
			if (at_end() || !is_ascii_digit(peek())) {
				fail_json_parse("invalid exponent", pos_);
			}
			while (!at_end() && is_ascii_digit(peek())) {
				++pos_;
			}
		}

		return input_.substr(start, pos_ - start);
	}

	explicit json_reader(std::string_view input) : input_(input) {}
};
} // namespace scpp::json::reader_detail
