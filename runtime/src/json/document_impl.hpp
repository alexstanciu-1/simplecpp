// Compiled in the selected JSON module with its generated handle declarations.
#include "structural_reader.hpp"
#include <atomic>
#include <memory>
#include <unordered_map>

namespace scpp::detail::json {
enum class kind { null, boolean, number, string, array, object };
struct value {
    kind type = kind::null;
    bool boolean = false;
    std::string text;
    std::vector<std::size_t> children;
    std::vector<std::string> keys;
    std::unordered_map<std::string,std::size_t> members;
};
struct document_storage {
    std::atomic_size_t owners{1};
    std::vector<value> values;
    std::size_t root = 0;
};
struct document_access {
    static json_document adopt(document_storage* data) {
        json_document d;
        d.data_ = data;
        return d;
    }
    static const value* get(const json_node& n) {
        return n.owner_.data_ ? &n.owner_.data_->values[n.index_] : nullptr;
    }
    static json_node node(const json_document& d, std::size_t index) {
        json_node n;
        n.owner_ = d;
        n.index_ = index;
        return n;
    }
    static result_json_node root(const json_document& d) {
        if (!d.data_) return result_json_node{exception{string{"null document"}}};
        return result_json_node{node(d, d.data_->root)};
    }
    static json_node child(const json_node& n, std::size_t index) { return node(n.owner_, index); }
};
static string owned(const std::string& bytes) {
    return string{bytes.data(), checked_integer_cast<int64>(bytes.size())};
}
class parser final {
	std::unique_ptr<document_storage> data_ = std::make_unique<document_storage>();
	pull_reader reader_;
	std::size_t parse_value() {
		value out;
		const auto type = reader_.kind();
		if (type == "null") { reader_.read_null(); }
		else if (type == "boolean") { out.type = kind::boolean; out.boolean = reader_.read_boolean(); }
		else if (type == "string") { out.type = kind::string; out.text = reader_.read_string(); }
		else if (type == "number") { out.type = kind::number; out.text = reader_.read_number(); }
		else if (type == "object") {
			out.type = kind::object;
			reader_.begin_object();
			while (reader_.next_property()) {
				auto key = reader_.property_name();
				out.members.emplace(key, out.children.size());
				out.keys.push_back(std::move(key));
				out.children.push_back(parse_value());
			}
		} else {
			out.type = kind::array;
			reader_.begin_array();
			while (reader_.next_element()) out.children.push_back(parse_value());
		}
		const auto index = data_->values.size();
		data_->values.push_back(std::move(out));
		return index;
	}
public:
	parser(const string& input, int64 max_depth) : reader_(std::string(input.data(), input.size()), "<json>", max_depth) {}
	json_document parse() {
		data_->root = parse_value();
		reader_.finish_document();
		return document_access::adopt(data_.release());
	}
};

}
namespace scpp {
json_document::~json_document() {
    if (data_ && data_->owners.fetch_sub(1, std::memory_order_acq_rel) == 1) delete data_;
}
json_document::json_document(const json_document& other) noexcept : data_(other.data_) {
    if (data_) data_->owners.fetch_add(1, std::memory_order_relaxed);
}
json_document::json_document(json_document&& other) noexcept : data_(std::exchange(other.data_, nullptr)) {}
json_document& json_document::operator=(const json_document& other) noexcept {
    json_document copy(other);
    std::swap(data_, copy.data_);
    return *this;
}
json_document& json_document::operator=(json_document&& other) noexcept {
    json_document moved(std::move(other));
    std::swap(data_, moved.data_);
    return *this;
}
}
namespace scpp::json {
using detail::json::document_access;
using detail::json::kind;
using detail::json::owned;
static exception access_error(const char* reason) { return exception{owned(std::string("json document: ") + reason)}; }
result_json_document document_parse(const string& text, nullable_json_parse_error& diagnostic, int64 depth) {
    diagnostic.reset();
    try {
        return result_json_document{detail::json::parser(text, depth).parse()};
    } catch (const detail::json::read_error& e) {
        diagnostic = nullable_json_parse_error{json_parse_error{owned(e.category), detail::checked_integer_cast<int64>(e.position), owned(e.detail)}};
        return result_json_document{exception{string{e.what()}}};
    }
}
result_json_node document_root(const json_document& d) { return document_access::root(d); }
result_string node_kind(const json_node &n) {
	const auto *v = document_access::get(n);
	if (!v) return result_string{access_error("null node")};
	static constexpr const char *names[]{"null", "boolean", "number", "string", "array", "object"};
	return result_string{string(names[static_cast<unsigned>(v->type)])};
}
result_int64 node_size(const json_node &n) {
	const auto *v = document_access::get(n);
	if (!v || (v->type != kind::array && v->type != kind::object)) return result_int64{access_error("expected array or object")};
	return result_int64{int64(static_cast<std::int64_t>(v->children.size()))};
}
result_json_node node_at(const json_node &n, int64 index) {
	const auto *v = document_access::get(n); const auto i = index;
	if (!v || v->type != kind::array) return result_json_node{access_error("expected array")};
	if (i < 0 || static_cast<std::uint64_t>(i) >= v->children.size()) return result_json_node{access_error("array index out of range")};
	return result_json_node{document_access::child(n, v->children[static_cast<std::size_t>(i)])};
}
result_string node_key(const json_node &n, int64 index) {
	const auto *v = document_access::get(n); const auto i = index;
	if (!v || v->type != kind::object) return result_string{access_error("expected object")};
	if (i < 0 || static_cast<std::uint64_t>(i) >= v->keys.size()) return result_string{access_error("object index out of range")};
	return result_string{owned(v->keys[static_cast<std::size_t>(i)])};
}
result_bool_t node_has(const json_node &n, const string &key) {
	const auto *v = document_access::get(n);
	if (!v || v->type != kind::object) return result_bool_t{access_error("expected object")};
	return result_bool_t{bool(v->members.contains(std::string(key.data(), key.size())))};
}
result_json_node node_member(const json_node &n, const string &key) {
	const auto *v = document_access::get(n);
	if (!v || v->type != kind::object) return result_json_node{access_error("expected object")};
	const auto it = v->members.find(std::string(key.data(), key.size()));
	if (it == v->members.end()) return result_json_node{access_error("missing object member")};
	return result_json_node{document_access::child(n, v->children[it->second])};
}
result_string node_string(const json_node &n) {
	const auto *v = document_access::get(n);
	if (!v || v->type != kind::string) return result_string{access_error("expected string")};
	return result_string{owned(v->text)};
}
result_bool_t node_boolean(const json_node &n) {
	const auto *v = document_access::get(n);
	if (!v || v->type != kind::boolean) return result_bool_t{access_error("expected boolean")};
	return result_bool_t{bool(v->boolean)};
}
result_string node_number(const json_node &n) {
	const auto *v = document_access::get(n);
	if (!v || v->type != kind::number) return result_string{access_error("expected number")};
	return result_string{owned(v->text)};
}
result_int64 node_int(const json_node &n) {
	const auto *v = document_access::get(n);
	if (!v || v->type != kind::number) return result_int64{access_error("expected number")};
	if (v->text.find_first_of(".eE") != std::string::npos) return result_int64{access_error("expected integer spelling")};
	std::int64_t number;
	const auto converted = std::from_chars(v->text.data(), v->text.data() + v->text.size(), number);
	if (converted.ec != std::errc{} || converted.ptr != v->text.data() + v->text.size()) return result_int64{access_error("integer out of range")};
	return result_int64{int64(number)};
}
}
