#include "modules/json/document.hpp"
#include "modules/json/pull_reader.hpp"
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

class parser final {
	storage data_;
	pull_reader reader_;
	std::size_t parse_value() {
		value out;
		const auto type = reader_.kind().native_value();
		if (type == "null") { reader_.read_null(); }
		else if (type == "boolean") { out.type = kind::boolean; out.boolean = reader_.read_boolean().native_value(); }
		else if (type == "string") { out.type = kind::string; out.text = reader_.read_string().native_value(); }
		else if (type == "number") { out.type = kind::number; out.text = reader_.read_number().native_value(); }
		else if (type == "object") {
			out.type = kind::object;
			reader_.begin_object();
			while (reader_.next_property().native_value()) {
				auto key = reader_.property_name().native_value();
				out.members.emplace(key, out.children.size());
				out.keys.push_back(std::move(key));
				out.children.push_back(parse_value());
			}
		} else {
			out.type = kind::array;
			reader_.begin_array();
			while (reader_.next_element().native_value()) out.children.push_back(parse_value());
		}
		const auto index = data_.values.size();
		data_.values.push_back(std::move(out));
		return index;
	}
public:
	parser(const string_t &input, const int_t<> &max_depth) : reader_(input, string_t("<json>"), max_depth) {}
	std::shared_ptr<const storage> parse() {
		data_.root = parse_value();
		reader_.finish_document();
		return std::make_shared<const storage>(std::move(data_));
	}
};
error_t access_error(const char *message) { return error_t(string_t(std::string("json document: ") + message)); }
} // namespace scpp::json::document_detail

namespace scpp::json {
result<shared_p<document>> document_parse(const string_t &text, shared_p<parse_error> &diagnostic, const int_t<> &max_depth) {
	diagnostic.reset();
	try {
		return document_detail::access::make_document(document_detail::parser(text, max_depth).parse());
	} catch (const read_error &failure) {
		auto info = std::make_shared<parse_error>();
		info->category = string_t(failure.category);
		info->byte_offset = int_t<>(static_cast<std::int64_t>(failure.position));
		info->message = string_t(failure.detail);
		diagnostic = shared_p<parse_error>(std::move(info));
		return error_t(string_t(std::string("json document error at byte ") + std::to_string(failure.position) + ": " + failure.detail));
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
