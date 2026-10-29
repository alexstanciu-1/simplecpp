// Parser/writer algorithms adapted from modules/json/json.cpp and scpp/json/writer.hpp
// at 2f39e536d. No boxed-family intermediate, public traversal or typed extraction.
#include "json_native.hpp"
#include "reader.hpp"
#include "boxed_inspection.hpp"
#include <charconv>
#include <cmath>
#include <cstdlib>
#include <iomanip>
#include <limits>
#include <locale>
#include <locale.h>
#include <sstream>
#include <unordered_set>
namespace scpp::detail::json {
namespace {
using reader_detail::json_parse_failure;
using reader_detail::fail_json_parse;
using reader_detail::is_ascii_digit;
locale_t numeric_locale() {
    struct locale_owner {
        locale_t value = newlocale(LC_NUMERIC_MASK, "C", nullptr);
        locale_owner() { if (!value) throw std::runtime_error("json_numeric_locale_unavailable"); }
        ~locale_owner() { freelocale(value); }
    };
    static locale_owner owner;
    return owner.value;
}
// Preserve donor normalization: canonical unsigned decimal through UINT32_MAX.
// Wider spellings remain strings. Recognized but unrepresentable keys are errors.
template<class Value> void set_object_entry(Value& out, const std::string& key, Value value, std::size_t position) {
    bool numeric = !key.empty() && !(key.size() > 1 && key[0] == '0');
    std::uint64_t number = 0;
    for (unsigned char c : key) {
        if (c < '0' || c > '9') { numeric = false; break; }
        number = number * 10 + (c - '0');
        if (number > std::numeric_limits<std::uint32_t>::max()) { numeric = false; break; }
    }
    if (numeric) {
        if (number > packed_key::max_integer)
            fail_json_parse("numeric object key exceeds INT32_MAX", position, "key_limit");
        out.set(static_cast<std::int64_t>(number), std::move(value));
    } else out.set(key, std::move(value));
}
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
				return Value::integer(value);
			}
		}

		std::string number_text(token);
		char *end_ptr = nullptr;
		const double value = strtod_l(number_text.c_str(), &end_ptr, numeric_locale());
		if (end_ptr == nullptr || *end_ptr != '\0' || !std::isfinite(value)) {
			fail_json_parse("invalid numeric value", start);
		}
		return Value::floating(value);
	}

	[[nodiscard]] Value parse_array() {
		if (get() != '[') {
			fail_json_parse("expected '['", pos_);
		}
		auto out = Value::container();
		skip_whitespace();
		if (!at_end() && peek() == ']') {
			++pos_;
			return out;
		}
		while (true) {
			static_cast<void>(out.append(parse_value()));
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
		auto out = Value::container();
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
			if (!names.insert(key).second) {
				fail_json_parse("duplicate object key " + std::string(input_.substr(key_start, pos_ - key_start)), key_start);
			}
			skip_whitespace();
			if (get() != ':') {
				fail_json_parse("expected ':'", pos_ - 1);
			}
			skip_whitespace();
			set_object_entry(out, key, parse_value(), key_start);
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
	explicit json_parser(std::string_view json)
		: json_reader(json) {
	}

	[[nodiscard]] Value parse_value() {
		skip_whitespace();
		if (at_end()) {
			fail_json_parse("expected JSON value", pos_);
		}
		switch (peek()) {
			case 'n':
				expect_literal("null");
				return Value{};
			case 't':
				expect_literal("true");
				return Value::boolean(true);
			case 'f':
				expect_literal("false");
				return Value::boolean(false);
			case '"':
				return Value::string(parse_string());
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
                    constexpr char hex[] = "0123456789ABCDEF";
                    out += "\\u00";
                    out.push_back(hex[byte >> 4]);
                    out.push_back(hex[byte & 15]);
				} else {
					out.push_back(static_cast<char>(byte));
				}
		}
	}
	out.push_back('"');
}

struct encode_failure { const char* message; };
class writer {
    std::string output_;
    std::unordered_set<const void*> active_;
    template<class Value> void table(const Value& value) {
        const auto& table = boxed_inspection::table(value);
        if (!active_.insert(&table).second) throw encode_failure{"json_encode: cyclic container is not supported"};
        struct guard {
            std::unordered_set<const void*>& active;
            const void* identity;
            ~guard() { active.erase(identity); }
        } path{active_, &table};
        const bool array = table.is_packed();
        output_.push_back(array ? '[' : '{');
        bool first = true;
        table.visit_entries([&](std::uint32_t code, const Value& entry) {
            if (!first) output_.push_back(',');
            first = false;
            if (!array) {
                if (packed_key::is_string(code)) json_escape_string(packed_string_pool::instance().resolve(code), output_);
                else json_escape_string(std::to_string(code), output_);
                output_.push_back(':');
            }
            write(entry);
        });
        output_.push_back(array ? ']' : '}');
    }
public:
    writer() { output_.reserve(64); }
    void write(std::nullptr_t) { output_ += "null"; }
    void write(bool value) { output_ += value ? "true" : "false"; }
    void write(std::int64_t value) { output_ += std::to_string(value); }
    void write(double value) {
        if (!std::isfinite(value)) throw encode_failure{"json_encode: non-finite float is not supported"};
        std::ostringstream stream;
        stream.exceptions(std::ios::badbit | std::ios::failbit);
        stream.imbue(std::locale::classic());
        stream << std::setprecision(std::numeric_limits<double>::max_digits10) << value;
        output_ += stream.str();
    }
    void write(std::string_view value) { json_escape_string(value, output_); }
    template<boxed_family Family> void write(const boxed_value<Family>& value) {
        using kind = typename boxed_value<Family>::kind;
        switch (value.type()) {
            case kind::null: write(nullptr); break;
            case kind::boolean: write(value.get_boolean()); break;
            case kind::integer: write(value.get_integer()); break;
            case kind::floating: write(value.get_floating()); break;
            case kind::string: write(std::string_view{value.get_string()}); break;
            case kind::container: table(value); break;
        }
    }
    std::string finish() && { return std::move(output_); }
};
template<class Value> outcome<Value> decode_family(std::string_view input) {
    try { return {json_parser<Value>(input).parse_document(), {}}; }
    catch (const json_parse_failure& failure) {
        return {{}, "json error at byte " + std::to_string(failure.position) + ": " + failure.message};
    }
}
template<class Value> outcome<std::string> encode_value(const Value& value) {
    try { writer output; output.write(value); return {std::move(output).finish(), {}}; }
    catch (const encode_failure& failure) { return {{}, failure.message}; }
}
} // namespace
outcome<dynamic_value> decode(std::string_view input) { return decode_family<dynamic_value>(input); }
outcome<mixed_value> decode_mixed(std::string_view input) { return decode_family<mixed_value>(input); }
outcome<std::string> encode(const mixed_value& value) { return encode_value(value); }
outcome<std::string> encode(const dynamic_value& value) { return encode_value(value); }
outcome<std::string> encode_null() { return encode_value(nullptr); }
outcome<std::string> encode(std::int64_t value) { return encode_value(value); }
outcome<std::string> encode(double value) { return encode_value(value); }
outcome<std::string> encode(bool value) { return encode_value(value); }
outcome<std::string> encode(std::string_view value) { return encode_value(value); }
} // namespace scpp::detail::json
