#include "modules/json/json.hpp"

#include "scpp/memory.hpp"

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
#include <unordered_set>

namespace scpp::json {
namespace {

// Private parser unwind signal. Only this failure is converted to an in-band error;
// allocation failures and unrelated runtime exceptions retain their own contracts.
struct json_parse_failure final {
	const char *message;
	std::size_t position;
};

[[noreturn]] void fail_json_parse(const char *message, const std::size_t position) {
	throw json_parse_failure{message, position};
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

template <typename Value> struct json_family;

template <> struct json_family<mixed_t> {
	static mixed_t container() { return mixed_t{std::make_unique<hash_t<mixed_t>>()}; }
	static auto &table(mixed_t &value) { return value.get_hash(); }
};

template <> struct json_family<detail::dynamic_value> {
	static detail::dynamic_value container() { return detail::dynamic_value::make_container(); }
	static auto &table(detail::dynamic_value &value) { return value.get_table(); }
};

// One grammar; only construction/storage varies with the selected family.
template <typename Value>
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
				fail_json_parse("unfinished unicode escape", pos_);
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
			fail_json_parse("invalid unicode escape", pos_ - 1);
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
							fail_json_parse("expected low surrogate after high surrogate", pos_);
						}
						const auto low = parse_hex4();
						if (low < 0xDC00u || low > 0xDFFFu) {
							fail_json_parse("invalid low surrogate", pos_ - 4);
						}
						const auto codepoint = 0x10000u + (((high - 0xD800u) << 10u) | (low - 0xDC00u));
						out += utf8_from_codepoint(codepoint);
						break;
					}
					if (high >= 0xDC00u && high <= 0xDFFFu) {
						fail_json_parse("unexpected low surrogate", pos_ - 4);
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

	[[nodiscard]] Value parse_number() {
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

		bool is_float = false;
		if (!at_end() && peek() == '.') {
			is_float = true;
			++pos_;
			if (at_end() || !is_ascii_digit(peek())) {
				fail_json_parse("invalid fraction", pos_);
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
				fail_json_parse("invalid exponent", pos_);
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
				return Value(int_t<>(value));
			}
		}

		std::string number_text(token);
		char *end_ptr = nullptr;
		const double value = std::strtod(number_text.c_str(), &end_ptr);
		if (end_ptr == nullptr || *end_ptr != '\0' || !std::isfinite(value)) {
			fail_json_parse("invalid numeric value", start);
		}
		return Value(float_t(value));
	}

	[[nodiscard]] Value parse_array() {
		if (get() != '[') {
			fail_json_parse("expected '['", pos_);
		}
		auto out = json_family<Value>::container();
		skip_whitespace();
		if (!at_end() && peek() == ']') {
			++pos_;
			return out;
		}
		while (true) {
			static_cast<void>(json_family<Value>::table(out).append(parse_value()));
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
			fail_json_parse("expected ',' or ']'", pos_);
		}
		return out;
	}

	[[nodiscard]] Value parse_object() {
		if (get() != '{') {
			fail_json_parse("expected '{'", pos_);
		}
		auto out = json_family<Value>::container();
		skip_whitespace();
		if (!at_end() && peek() == '}') {
			++pos_;
			return out;
		}
		while (true) {
			skip_whitespace();
			if (peek() != '"') {
				fail_json_parse("expected string key", pos_);
			}
			const auto key = parse_string();
			skip_whitespace();
			if (get() != ':') {
				fail_json_parse("expected ':'", pos_ - 1);
			}
			skip_whitespace();
			json_family<Value>::table(out).set(key, parse_value());
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
			fail_json_parse("expected ',' or '}'", pos_);
		}
		return out;
	}

public:
	explicit json_parser(const string_t &json)
		: input_(json.native_value()) {
	}

	[[nodiscard]] Value parse_value() {
		skip_whitespace();
		if (at_end()) {
			fail_json_parse("expected JSON value", pos_);
		}
		switch (peek()) {
			case 'n':
				expect_literal("null");
				return Value(null_t{});
			case 't':
				expect_literal("true");
				return Value(bool_t(true));
			case 'f':
				expect_literal("false");
				return Value(bool_t(false));
			case '"':
				return Value(parse_string());
			case '[':
				return parse_array();
			case '{':
				return parse_object();
			default:
				if (peek() == '-' || is_ascii_digit(peek())) {
					return parse_number();
				}
				fail_json_parse("unexpected character", pos_);
		}
	}

	[[nodiscard]] Value parse_document() {
		Value value = parse_value();
		skip_whitespace();
		if (!at_end()) {
			fail_json_parse("trailing non-whitespace after JSON value", pos_);
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

// Private writer unwind signal; unrelated runtime exceptions are not encoding failures.
struct json_encode_failure final {
	const char *message;
};

[[noreturn]] void fail_json_encode(const char *message) {
	throw json_encode_failure{message};
}

using active_tables = std::unordered_set<const void *>;
template <typename Table>
void encode_hash(const Table &value, std::string &out, active_tables &active);

template <typename Value>
bool encode_scalar(const Value &value, std::string &out) {
	using kind = typename Value::kind_t;
	switch (value.kind()) {
		case kind::null_v:
			out += "null";
			return true;
		case kind::bool_v:
			out += value.get_bool().native_value() ? "true" : "false";
			return true;
		case kind::int_v:
			out += std::to_string(value.get_int().native_value());
			return true;
		case kind::float_v: {
			const double native = value.get_float().native_value();
			if (!std::isfinite(native)) {
				fail_json_encode("json_encode: non-finite float_t is not supported");
			}
			std::ostringstream stream;
			stream << std::setprecision(std::numeric_limits<double>::max_digits10) << native;
			out += stream.str();
			return true;
		}
		case kind::string_v:
			json_escape_string(value.get_string().native_value(), out);
			return true;
		default: return false;
	}
}

void encode_value(const detail::dynamic_value &value, std::string &out, active_tables &active) {
	if (encode_scalar(value, out)) return;
	encode_hash(value.get_table(), out, active);
}

void encode_value(const mixed_t &value, std::string &out, active_tables &active) {
	if (encode_scalar(value, out)) return;
	switch (value.kind()) {
		case mixed_t::kind_t::table_v:
		case mixed_t::kind_t::shared_table_v:
		case mixed_t::kind_t::dynamic_v:
			encode_hash(value.get_hash(), out, active);
			return;
		case mixed_t::kind_t::weak_table_v:
			fail_json_encode("json_encode: weak tables are not supported");
		default: fail_json_encode("json_encode: unsupported value kind");
	}
}

template <typename Table>
void encode_hash(const Table &value, std::string &out, active_tables &active) {
	if (!active.insert(&value).second) fail_json_encode("json_encode: cyclic container is not supported");
	struct path_guard final {
		active_tables &active;
		const void *identity;
		~path_guard() { active.erase(identity); }
	} guard{active, &value};
	if (value.is_packed().native_value()) {
		out.push_back('[');
		bool first = true;
		for (auto it = value.begin_entries(); it != value.end_entries(); ++it) {
			if (!first) {
				out.push_back(',');
			}
			first = false;
			encode_value((*it).value_ref(), out, active);
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
			fail_json_encode("json_encode: object key must lower to string_t or int_t<>");
		}
		out.push_back(':');
		encode_value((*it).value_ref(), out, active);
	}
	out.push_back('}');
}

} // namespace

template <typename Value>
static result<Value> decode_family(const string_t &json) {
	try {
		json_parser<Value> parser(json);
		return parser.parse_document();
	} catch (const json_parse_failure &failure) {
		return error_t(string_t(std::string("json error at byte ")
			+ std::to_string(failure.position) + ": " + failure.message));
	}
}

result<detail::dynamic_value> decode_dynamic(const string_t &json) {
	return decode_family<detail::dynamic_value>(json);
}

result<mixed_t> decode_mixed(const string_t &json) {
	return decode_family<mixed_t>(json);
}

template <typename Value>
static result<string_t> encode_family(const Value &value) {
	try {
		std::string out;
		out.reserve(64);
		active_tables active;
		encode_value(value, out, active);
		return string_t(std::move(out));
	} catch (const json_encode_failure &failure) {
		return error_t(string_t(failure.message));
	}
}

result<string_t> encode(const mixed_t &value) { return encode_family(value); }
result<string_t> encode(const detail::dynamic_value &value) { return encode_family(value); }

} // namespace scpp::json
