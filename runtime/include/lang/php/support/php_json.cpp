#include "lang/php/support/php_json.hpp"

#include "scpp/memory.hpp"
#include "lang/php/php.hpp"

#include <charconv>
#include <cmath>
#include <cstdint>
#include <cstdlib>
#include <iomanip>
#include <limits>
#include <sstream>
#include <stdexcept>
#include <string>
#include <string_view>

namespace scpp::php {
namespace {

[[noreturn]] void throw_json_error(const char *message, const std::size_t position) {
	throw std::runtime_error(std::string("json error at byte ") + std::to_string(position) + ": " + message);
}

[[nodiscard]] bool is_ascii_digit(const char ch) noexcept {
	return ch >= '0' && ch <= '9';
}

[[nodiscard]] std::string utf8_from_codepoint(const std::uint32_t codepoint) {
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

class json_parser final {
private:
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
			throw_json_error("unexpected end of input", pos_);
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
			throw_json_error("invalid literal", pos_);
		}
		pos_ += literal.size();
	}

	[[nodiscard]] std::uint32_t parse_hex4() {
		std::uint32_t value = 0;
		for (int i = 0; i < 4; ++i) {
			if (at_end()) {
				throw_json_error("unfinished unicode escape", pos_);
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
			throw_json_error("invalid unicode escape", pos_ - 1);
		}
		return value;
	}

	[[nodiscard]] string_t parse_string() {
		const std::size_t start = pos_;
		if (get() != '"') {
			throw_json_error("expected string", start);
		}

		std::string out;
		while (!at_end()) {
			const char ch = get();
			if (ch == '"') {
				return string_t(std::move(out));
			}
			if (static_cast<unsigned char>(ch) < 0x20u) {
				throw_json_error("control character in string", pos_ - 1);
			}
			if (ch != '\\') {
				out.push_back(ch);
				continue;
			}

			if (at_end()) {
				throw_json_error("unfinished escape sequence", pos_);
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
							throw_json_error("expected low surrogate after high surrogate", pos_);
						}
						const auto low = parse_hex4();
						if (low < 0xDC00u || low > 0xDFFFu) {
							throw_json_error("invalid low surrogate", pos_ - 4);
						}
						const auto codepoint = 0x10000u + (((high - 0xD800u) << 10u) | (low - 0xDC00u));
						out += utf8_from_codepoint(codepoint);
						break;
					}
					if (high >= 0xDC00u && high <= 0xDFFFu) {
						throw_json_error("unexpected low surrogate", pos_ - 4);
					}
					out += utf8_from_codepoint(high);
					break;
				}
				default:
					throw_json_error("invalid escape sequence", pos_ - 1);
			}
		}

		throw_json_error("unterminated string", start);
	}

	[[nodiscard]] mixed_t parse_number() {
		const std::size_t start = pos_;
		if (peek() == '-') {
			++pos_;
		}
		if (at_end()) {
			throw_json_error("invalid number", start);
		}
		if (peek() == '0') {
			++pos_;
			if (!at_end() && is_ascii_digit(peek())) {
				throw_json_error("leading zeros are not allowed", pos_);
			}
		} else {
			if (!is_ascii_digit(peek())) {
				throw_json_error("invalid number", pos_);
			}
			while (!at_end() && is_ascii_digit(peek())) {
				++pos_;
			}
		}

		bool is_float = false;
		if (!at_end() && peek() == '.') {
			is_float = true;
			++pos_;
			if (at_end() || !is_ascii_digit(peek())) {
				throw_json_error("invalid fraction", pos_);
			}
			while (!at_end() && is_ascii_digit(peek())) {
				++pos_;
			}
		}
		if (!at_end() && (peek() == 'e' || peek() == 'E')) {
			is_float = true;
			++pos_;
			if (!at_end() && (peek() == '+' || peek() == '-')) {
				++pos_;
			}
			if (at_end() || !is_ascii_digit(peek())) {
				throw_json_error("invalid exponent", pos_);
			}
			while (!at_end() && is_ascii_digit(peek())) {
				++pos_;
			}
		}

		const auto token = input_.substr(start, pos_ - start);
		if (!is_float) {
			std::int64_t value = 0;
			auto result = std::from_chars(token.data(), token.data() + token.size(), value);
			if (result.ec == std::errc{} && result.ptr == token.data() + token.size()) {
				return mixed_t(int_t(value));
			}
		}

		std::string number_text(token);
		char *end_ptr = nullptr;
		const double value = std::strtod(number_text.c_str(), &end_ptr);
		if (end_ptr == nullptr || *end_ptr != '\0' || !std::isfinite(value)) {
			throw_json_error("invalid numeric value", start);
		}
		return mixed_t(float_t(value));
	}

	[[nodiscard]] mixed_t parse_array() {
		if (get() != '[') {
			throw_json_error("expected '['", pos_);
		}
		auto out = shared<hash_t<mixed_t>>();
		skip_whitespace();
		if (!at_end() && peek() == ']') {
			++pos_;
			return mixed_t(dynamic_box(dynamic_t(out)));
		}
		while (true) {
			static_cast<void>(out->append(parse_value()));
			skip_whitespace();
			if (!at_end() && peek() == ',') {
				++pos_;
				skip_whitespace();
				continue;
			}
			if (!at_end() && peek() == ']') {
				++pos_;
				break;
			}
			throw_json_error("expected ',' or ']'", pos_);
		}
		return mixed_t(dynamic_box(dynamic_t(out)));
	}

	[[nodiscard]] mixed_t parse_object() {
		if (get() != '{') {
			throw_json_error("expected '{'", pos_);
		}
		auto out = shared<hash_t<mixed_t>>();
		skip_whitespace();
		if (!at_end() && peek() == '}') {
			++pos_;
			return mixed_t(dynamic_box(dynamic_t(out)));
		}
		while (true) {
			skip_whitespace();
			if (peek() != '"') {
				throw_json_error("expected string key", pos_);
			}
			const auto key = parse_string();
			skip_whitespace();
			if (get() != ':') {
				throw_json_error("expected ':'", pos_ - 1);
			}
			skip_whitespace();
			out->set(key, parse_value());
			skip_whitespace();
			if (!at_end() && peek() == ',') {
				++pos_;
				skip_whitespace();
				continue;
			}
			if (!at_end() && peek() == '}') {
				++pos_;
				break;
			}
			throw_json_error("expected ',' or '}'", pos_);
		}
		return mixed_t(dynamic_box(dynamic_t(out)));
	}

public:
	explicit json_parser(const string_t &json)
		: input_(json.native_value()) {
	}

	[[nodiscard]] mixed_t parse_value() {
		skip_whitespace();
		if (at_end()) {
			throw_json_error("expected JSON value", pos_);
		}
		switch (peek()) {
			case 'n':
				expect_literal("null");
				return mixed_t(null_t{});
			case 't':
				expect_literal("true");
				return mixed_t(bool_t(true));
			case 'f':
				expect_literal("false");
				return mixed_t(bool_t(false));
			case '"':
				return mixed_t(parse_string());
			case '[':
				return parse_array();
			case '{':
				return parse_object();
			default:
				if (peek() == '-' || is_ascii_digit(peek())) {
					return parse_number();
				}
				throw_json_error("unexpected character", pos_);
		}
	}

	[[nodiscard]] mixed_t parse_document() {
		mixed_t value = parse_value();
		skip_whitespace();
		if (!at_end()) {
			throw_json_error("trailing non-whitespace after JSON value", pos_);
		}
		return value;
	}
};

void json_escape_string(const std::string_view value, std::string &out) {
	out.push_back('"');
	for (const unsigned char byte : value) {
		switch (byte) {
			case '"': out += "\\\""; break;
			case '\\': out += "\\\\"; break;
			case '\b': out += "\\b"; break;
			case '\f': out += "\\f"; break;
			case '\n': out += "\\n"; break;
			case '\r': out += "\\r"; break;
			case '\t': out += "\\t"; break;
			default:
				if (byte < 0x20u) {
					std::ostringstream escaped;
					escaped << "\\u" << std::uppercase << std::hex << std::setw(4) << std::setfill('0') << static_cast<unsigned int>(byte);
					out += escaped.str();
				} else {
					out.push_back(static_cast<char>(byte));
				}
		}
	}
	out.push_back('"');
}

void encode_hash(const hash_t<mixed_t> &value, std::string &out);

void encode_value(const mixed_t &value, std::string &out) {
	switch (value.kind()) {
		case mixed_t::kind_t::null_v:
			out += "null";
			return;
		case mixed_t::kind_t::bool_v:
			out += value.get_bool().native_value() ? "true" : "false";
			return;
		case mixed_t::kind_t::int_v:
			out += std::to_string(value.get_int().native_value());
			return;
		case mixed_t::kind_t::float_v: {
			const double native = value.get_float().native_value();
			if (!std::isfinite(native)) {
				throw std::runtime_error("json_encode: non-finite float_t is not supported");
			}
			std::ostringstream stream;
			stream << std::setprecision(std::numeric_limits<double>::max_digits10) << native;
			out += stream.str();
			return;
		}
		case mixed_t::kind_t::string_v:
			json_escape_string(value.get_string().native_value(), out);
			return;
		case mixed_t::kind_t::table_v:
		case mixed_t::kind_t::shared_table_v:
		case mixed_t::kind_t::dynamic_v:
			encode_hash(value.get_hash(), out);
			return;
		case mixed_t::kind_t::weak_table_v:
			throw std::runtime_error("json_encode: weak tables are not supported");
	}
}

void encode_hash(const hash_t<mixed_t> &value, std::string &out) {
	if (value.is_packed().native_value()) {
		out.push_back('[');
		bool first = true;
		for (auto it = value.begin_entries(); it != value.end_entries(); ++it) {
			if (!first) {
				out.push_back(',');
			}
			first = false;
			encode_value((*it).value_ref(), out);
		}
		out.push_back(']');
		return;
	}

	out.push_back('{');
	bool first = true;
	for (auto it = value.begin_entries(); it != value.end_entries(); ++it) {
		if (!first) {
			out.push_back(',');
		}
		first = false;
		const mixed_t key = (*it).key();
		if (key.kind() == mixed_t::kind_t::string_v) {
			json_escape_string(key.get_string().native_value(), out);
		} else if (key.kind() == mixed_t::kind_t::int_v) {
			json_escape_string(std::to_string(key.get_int().native_value()), out);
		} else {
			throw std::runtime_error("json_encode: object key must lower to string_t or int_t");
		}
		out.push_back(':');
		encode_value((*it).value_ref(), out);
	}
	out.push_back('}');
}

} // namespace

mixed_t json_decode(const string_t &json) {
	json_parser parser(json);
	return parser.parse_document();
}

string_t json_encode(const mixed_t &value) {
	std::string out;
	out.reserve(64);
	encode_value(value, out);
	return string_t(std::move(out));
}

string_t json_encode(const hash_t<mixed_t> &value) {
	std::string out;
	out.reserve(64);
	encode_hash(value, out);
	return string_t(std::move(out));
}

string_t json_encode(const shared_p<hash_t<mixed_t>> &value) {
	if (!value.has_value().native_value()) {
		return string_t("null");
	}
	return json_encode(*value);
}

} // namespace scpp::php
