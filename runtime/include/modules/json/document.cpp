#include "modules/json/document.hpp"
#include "modules/json/detail/reader.hpp"
#include <charconv>
#include <unordered_map>
#include <vector>

namespace scpp::json::document_detail {
enum class kind { null, boolean, number, string, array, object };
struct value {
	kind type = kind::null;
	bool boolean = false;
	std::string text;
	std::vector<std::size_t> children;
	std::vector<std::string> keys;
	std::unordered_map<std::string, std::size_t> members;
};
struct storage { std::vector<value> values; std::size_t root = 0; };
struct access {
	static shared_p<document> make_document(std::shared_ptr<const storage> data) {
		return shared_p<document>(std::shared_ptr<document>(new document(std::move(data))));
	}
	static shared_p<node> make_node(std::shared_ptr<const storage> data, std::size_t index) {
		return shared_p<node>(std::shared_ptr<node>(new node(std::move(data), index)));
	}
	static const value *get(const shared_p<node> &n) {
		if (!n.has_value().native_value()) return nullptr;
		return &n->data_->values[n->index_];
	}
	static shared_p<node> root(const shared_p<document> &d) { return make_node(d->data_, d->data_->root); }
	static shared_p<node> child(const shared_p<node> &n, std::size_t index) { return make_node(n->data_, index); }
};

// Validate complete source bytes before grammar; escaped codepoints are checked
// by the shared lexical reader. No Unicode normalization or locale dependence.
void validate_utf8(std::string_view input) {
	for (std::size_t i = 0; i < input.size();) {
		const auto start = i;
		const auto first = static_cast<unsigned char>(input[i++]);
		if (first < 0x80) continue;
		unsigned remaining; std::uint32_t cp, minimum;
		if (first >= 0xC2 && first <= 0xDF) { remaining = 1; cp = first & 0x1F; minimum = 0x80; }
		else if (first >= 0xE0 && first <= 0xEF) { remaining = 2; cp = first & 0x0F; minimum = 0x800; }
		else if (first >= 0xF0 && first <= 0xF4) { remaining = 3; cp = first & 7; minimum = 0x10000; }
		else { reader_detail::fail_json_parse("invalid UTF-8", start, "utf8"); }
		if (input.size() - i < remaining) reader_detail::fail_json_parse("unfinished UTF-8", start, "utf8");
		while (remaining--) {
			const auto c = static_cast<unsigned char>(input[i++]);
			if ((c & 0xC0) != 0x80) reader_detail::fail_json_parse("invalid UTF-8 continuation", start, "utf8");
			cp = (cp << 6) | (c & 0x3F);
		}
		if (cp < minimum || cp > 0x10FFFF || (cp >= 0xD800 && cp <= 0xDFFF))
			reader_detail::fail_json_parse("invalid UTF-8 codepoint", start, "utf8");
	}
}

class parser final : private reader_detail::json_reader {
	storage data_;
	std::size_t max_depth_;
	std::size_t parse_value(std::size_t depth) {
		skip_whitespace();
		if (at_end()) reader_detail::fail_json_parse("expected JSON value", pos_);
		value out;
		switch (peek()) {
			case 'n': expect_literal("null"); break;
			case 't': expect_literal("true"); out.type = kind::boolean; out.boolean = true; break;
			case 'f': expect_literal("false"); out.type = kind::boolean; break;
			case '"': out.type = kind::string; out.text = parse_string().native_value(); break;
			case '[': case '{': {
				if (depth >= max_depth_) reader_detail::fail_json_parse("nesting limit exceeded", pos_, "depth");
				const bool object = get() == '{';
				const char close = object ? '}' : ']';
				out.type = object ? kind::object : kind::array;
				skip_whitespace();
				if (peek() == close) { ++pos_; break; }
				while (true) {
					std::string key;
					if (object) {
						skip_whitespace();
						key = parse_string().native_value();
						skip_whitespace();
						if (get() != ':') reader_detail::fail_json_parse("expected ':'", pos_ - 1);
					}
					const auto child = parse_value(depth + 1);
					if (object) {
						const auto [it, inserted] = out.members.emplace(key, out.children.size());
						if (inserted) { out.keys.push_back(std::move(key)); out.children.push_back(child); }
						else { out.children[it->second] = child; }
					} else { out.children.push_back(child); }
					skip_whitespace();
					if (peek() == close) { ++pos_; break; }
					if (peek() != ',') reader_detail::fail_json_parse("expected comma or container end", pos_);
					++pos_;
				}
				break;
			}
			default:
				if (peek() != '-' && !reader_detail::is_ascii_digit(peek())) reader_detail::fail_json_parse("unexpected character", pos_);
				out.type = kind::number; out.text = parse_number_token();
		}
		const auto index = data_.values.size();
		data_.values.push_back(std::move(out));
		return index;
	}
public:
	parser(std::string_view input, std::size_t max_depth) : json_reader(input), max_depth_(max_depth) {}
	std::shared_ptr<const storage> parse() {
		validate_utf8(input_);
		data_.root = parse_value(0);
		skip_whitespace();
		if (!at_end()) reader_detail::fail_json_parse("trailing non-whitespace after JSON value", pos_);
		return std::make_shared<const storage>(std::move(data_));
	}
};
error_t access_error(const char *message) { return error_t(string_t(std::string("json document: ") + message)); }
} // namespace scpp::json::document_detail

namespace scpp::json {
result<shared_p<document>> document_parse(const string_t &text, shared_p<parse_error> &diagnostic, const int_t<> &max_depth) {
	diagnostic.reset();
	try {
		const auto limit = max_depth.native_value();
		if (limit < 1 || limit > 256) reader_detail::fail_json_parse("max_depth must be 1..256", 0, "invalid_limit");
		return document_detail::access::make_document(document_detail::parser(text.native_value(), static_cast<std::size_t>(limit)).parse());
	} catch (const reader_detail::json_parse_failure &failure) {
		auto info = std::make_shared<parse_error>();
		info->category = string_t(failure.category);
		info->byte_offset = int_t<>(static_cast<std::int64_t>(failure.position));
		info->message = string_t(failure.message);
		diagnostic = shared_p<parse_error>(std::move(info));
		return error_t(string_t(std::string("json document error at byte ") + std::to_string(failure.position) + ": " + failure.message));
	}
}
result<shared_p<node>> document_root(const shared_p<document> &d) {
	if (!d.has_value().native_value()) return document_detail::access_error("null document");
	return document_detail::access::root(d);
}
result<string_t> node_kind(const shared_p<node> &n) {
	const auto *v = document_detail::access::get(n);
	if (!v) return document_detail::access_error("null node");
	static constexpr const char *names[]{"null", "boolean", "number", "string", "array", "object"};
	return string_t(names[static_cast<unsigned>(v->type)]);
}
result<int_t<>> node_size(const shared_p<node> &n) {
	const auto *v = document_detail::access::get(n);
	if (!v || (v->type != document_detail::kind::array && v->type != document_detail::kind::object)) return document_detail::access_error("expected array or object");
	return int_t<>(static_cast<std::int64_t>(v->children.size()));
}
result<shared_p<node>> node_at(const shared_p<node> &n, const int_t<> &index) {
	const auto *v = document_detail::access::get(n); const auto i = index.native_value();
	if (!v || v->type != document_detail::kind::array) return document_detail::access_error("expected array");
	if (i < 0 || static_cast<std::uint64_t>(i) >= v->children.size()) return document_detail::access_error("array index out of range");
	return document_detail::access::child(n, v->children[static_cast<std::size_t>(i)]);
}
result<string_t> node_key(const shared_p<node> &n, const int_t<> &index) {
	const auto *v = document_detail::access::get(n); const auto i = index.native_value();
	if (!v || v->type != document_detail::kind::object) return document_detail::access_error("expected object");
	if (i < 0 || static_cast<std::uint64_t>(i) >= v->keys.size()) return document_detail::access_error("object index out of range");
	return string_t(v->keys[static_cast<std::size_t>(i)]);
}
result<bool_t> node_has(const shared_p<node> &n, const string_t &key) {
	const auto *v = document_detail::access::get(n);
	if (!v || v->type != document_detail::kind::object) return document_detail::access_error("expected object");
	return bool_t(v->members.contains(key.native_value()));
}
result<shared_p<node>> node_member(const shared_p<node> &n, const string_t &key) {
	const auto *v = document_detail::access::get(n);
	if (!v || v->type != document_detail::kind::object) return document_detail::access_error("expected object");
	const auto it = v->members.find(key.native_value());
	if (it == v->members.end()) return document_detail::access_error("missing object member");
	return document_detail::access::child(n, v->children[it->second]);
}
result<string_t> node_string(const shared_p<node> &n) {
	const auto *v = document_detail::access::get(n);
	if (!v || v->type != document_detail::kind::string) return document_detail::access_error("expected string");
	return string_t(v->text);
}
result<bool_t> node_boolean(const shared_p<node> &n) {
	const auto *v = document_detail::access::get(n);
	if (!v || v->type != document_detail::kind::boolean) return document_detail::access_error("expected boolean");
	return bool_t(v->boolean);
}
result<string_t> node_number(const shared_p<node> &n) {
	const auto *v = document_detail::access::get(n);
	if (!v || v->type != document_detail::kind::number) return document_detail::access_error("expected number");
	return string_t(v->text);
}
result<int_t<>> node_int(const shared_p<node> &n) {
	const auto *v = document_detail::access::get(n);
	if (!v || v->type != document_detail::kind::number) return document_detail::access_error("expected number");
	if (v->text.find_first_of(".eE") != std::string::npos) return document_detail::access_error("expected integer spelling");
	std::int64_t number;
	const auto converted = std::from_chars(v->text.data(), v->text.data() + v->text.size(), number);
	if (converted.ec != std::errc{} || converted.ptr != v->text.data() + v->text.size()) return document_detail::access_error("integer out of range");
	return int_t<>(number);
}
} // namespace scpp::json
