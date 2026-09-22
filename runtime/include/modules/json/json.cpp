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

namespace scpp::json {
namespace {

using reader_detail::fail_json_parse;
using reader_detail::json_parse_failure;
using reader_detail::is_ascii_digit;

class json_parser final : private reader_detail::json_reader {
	[[nodiscard]] mixed_t parse_number() {
		const std::size_t start = pos_;
		const auto token = parse_number_token();
		const bool is_float = token.find_first_of(".eE") != std::string_view::npos;
		if (!is_float) {
			std::int64_t value = 0;
			auto result = std::from_chars(token.data(), token.data() + token.size(), value);
			if (result.ec == std::errc{} && result.ptr == token.data() + token.size()) {
				return mixed_t(int_t<>(value));
			}
		}

		std::string number_text(token);
		char *end_ptr = nullptr;
		const double value = std::strtod(number_text.c_str(), &end_ptr);
		if (end_ptr == nullptr || *end_ptr != '\0' || !std::isfinite(value)) {
			fail_json_parse("invalid numeric value", start);
		}
		return mixed_t(float_t(value));
	}

	[[nodiscard]] mixed_t parse_array() {
		if (get() != '[') {
			fail_json_parse("expected '['", pos_);
		}
		auto out = shared<hash_t<mixed_t>>();
		skip_whitespace();
		if (!at_end() && peek() == ']') {
			++pos_;
			return mixed_t(dynamic_box(dynamic_t<>(out)));
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
			fail_json_parse("expected ',' or ']'", pos_);
		}
		return mixed_t(dynamic_box(dynamic_t<>(out)));
	}

	[[nodiscard]] mixed_t parse_object() {
		if (get() != '{') {
			fail_json_parse("expected '{'", pos_);
		}
		auto out = shared<hash_t<mixed_t>>();
		skip_whitespace();
		if (!at_end() && peek() == '}') {
			++pos_;
			return mixed_t(dynamic_box(dynamic_t<>(out)));
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
			fail_json_parse("expected ',' or '}'", pos_);
		}
		return mixed_t(dynamic_box(dynamic_t<>(out)));
	}

public:
	explicit json_parser(const string_t &json)
		: reader_detail::json_reader(json.native_value()) {
	}

	[[nodiscard]] mixed_t parse_value() {
		skip_whitespace();
		if (at_end()) {
			fail_json_parse("expected JSON value", pos_);
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
				fail_json_parse("unexpected character", pos_);
		}
	}

	[[nodiscard]] mixed_t parse_document() {
		mixed_t value = parse_value();
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
				fail_json_encode("json_encode: non-finite float_t is not supported");
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
			fail_json_encode("json_encode: weak tables are not supported");
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
			fail_json_encode("json_encode: object key must lower to string_t or int_t<>");
		}
		out.push_back(':');
		encode_value((*it).value_ref(), out);
	}
	out.push_back('}');
}

} // namespace

result<mixed_t> decode(const string_t &json) {
	try {
		json_parser parser(json);
		return parser.parse_document();
	} catch (const json_parse_failure &failure) {
		return error_t(string_t(std::string("json error at byte ")
			+ std::to_string(failure.position) + ": " + failure.message));
	}
}

result<string_t> encode(const mixed_t &value) {
	try {
		std::string out;
		out.reserve(64);
		encode_value(value, out);
		return string_t(std::move(out));
	} catch (const json_encode_failure &failure) {
		return error_t(string_t(failure.message));
	}
}

} // namespace scpp::json
