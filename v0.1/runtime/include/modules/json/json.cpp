#include "modules/json/json.hpp"
#include "modules/json/detail/reader.hpp"

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

using reader_detail::json_parse_failure;
using reader_detail::fail_json_parse;
using reader_detail::is_ascii_digit;

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
class json_parser final : private reader_detail::json_reader {
private:
	[[nodiscard]] Value parse_number() {
		const std::size_t start = pos_;
		const auto token = parse_number_token();
		const bool is_float = token.find_first_of(".eE") != std::string_view::npos;
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
		// Compare decoded JSON names, before any family-specific key normalization.
		std::unordered_set<std::string> names;
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
			const auto key_start = pos_;
			const auto key = parse_string();
			if (!names.insert(key.native_value()).second) {
				fail_json_parse("duplicate object key " + std::string(input_.substr(key_start, pos_ - key_start)), key_start);
			}
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
		: json_reader(json.native_value()) {
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

} // namespace

namespace writer_detail {

void writer::write(null_t) { output_ += "null"; }
void writer::write(const bool_t &value) { output_ += value.native_value() ? "true" : "false"; }
void writer::write(const int_t<> &value) { output_ += std::to_string(value.native_value()); }
void writer::write(const float_t &value) {
	const double native = value.native_value();
	if (!std::isfinite(native)) throw encode_failure{"json_encode: non-finite float_t is not supported"};
	std::ostringstream stream;
	stream << std::setprecision(std::numeric_limits<double>::max_digits10) << native;
	output_ += stream.str();
}
void writer::write(const string_t &value) { json_escape_string(value.native_value(), output_); }
void writer::key(const string_t &value) { write(value); }
void writer::key(const int_t<> &value) { json_escape_string(std::to_string(value.native_value()), output_); }
void writer::key(const mixed_t &value) {
	if (value.kind() == mixed_t::kind_t::string_v) return key(value.get_string());
	if (value.kind() == mixed_t::kind_t::int_v) return key(value.get_int());
	throw encode_failure{"json_encode: object key must lower to string_t or int_t<>"};
}

template <typename Box>
bool writer::scalar(const Box &value) {
	using kind = typename Box::kind_t;
	switch (value.kind()) {
		case kind::null_v: write(null_t{}); return true;
		case kind::bool_v: write(value.get_bool()); return true;
		case kind::int_v: write(value.get_int()); return true;
		case kind::float_v: write(value.get_float()); return true;
		case kind::string_v: write(value.get_string()); return true;
		default: return false;
	}
}
void writer::write(const detail::dynamic_value &value) {
	if (scalar(value)) return;
	table(value.get_table(), value.get_table().is_packed().native_value());
}
void writer::write(const mixed_t &value) {
	if (scalar(value)) return;
	switch (value.kind()) {
		case mixed_t::kind_t::table_v:
		case mixed_t::kind_t::shared_table_v:
			table(value.get_hash(), value.get_hash().is_packed().native_value()); return;
		case mixed_t::kind_t::weak_table_v:
			throw encode_failure{"json_encode: weak tables are not supported"};
		default: throw encode_failure{"json_encode: unsupported value kind"};
	}
}

} // namespace writer_detail

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

result<string_t> encode(const mixed_t &value) { return writer_detail::encode(value); }
result<string_t> encode(const detail::dynamic_value &value) { return writer_detail::encode(value); }

} // namespace scpp::json
