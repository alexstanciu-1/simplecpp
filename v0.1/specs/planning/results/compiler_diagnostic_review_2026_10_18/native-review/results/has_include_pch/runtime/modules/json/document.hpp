#pragma once
#include "scpp/result.hpp"
#include "scpp/shared_p.hpp"
#include <memory>

namespace scpp::json {
namespace document_detail { struct storage; struct access; }

class document final {
	std::shared_ptr<const document_detail::storage> data_;
	explicit document(std::shared_ptr<const document_detail::storage> data) : data_(std::move(data)) {}
	friend struct document_detail::access;
public:
	document(const document &) = delete;
	document &operator=(const document &) = delete;
};
class node final {
	std::shared_ptr<const document_detail::storage> data_;
	std::size_t index_;
	node(std::shared_ptr<const document_detail::storage> data, std::size_t index) : data_(std::move(data)), index_(index) {}
	friend struct document_detail::access;
public:
	node(const node &) = delete;
	node &operator=(const node &) = delete;
};
class parse_error final {
public:
	string_t category;
	int_t<> byte_offset;
	string_t message;
};

[[nodiscard]] result<shared_p<document>> document_parse(const string_t &, shared_p<parse_error> &, const int_t<> &max_depth = int_t<>(128));
[[nodiscard]] result<shared_p<node>> document_root(const shared_p<document> &);
[[nodiscard]] result<string_t> node_kind(const shared_p<node> &);
[[nodiscard]] result<int_t<>> node_size(const shared_p<node> &);
[[nodiscard]] result<shared_p<node>> node_at(const shared_p<node> &, const int_t<> &);
[[nodiscard]] result<string_t> node_key(const shared_p<node> &, const int_t<> &);
[[nodiscard]] result<bool_t> node_has(const shared_p<node> &, const string_t &);
[[nodiscard]] result<shared_p<node>> node_member(const shared_p<node> &, const string_t &);
[[nodiscard]] result<string_t> node_string(const shared_p<node> &);
[[nodiscard]] result<bool_t> node_boolean(const shared_p<node> &);
[[nodiscard]] result<string_t> node_number(const shared_p<node> &);
[[nodiscard]] result<int_t<>> node_int(const shared_p<node> &);
}
namespace scpp {
using json_document = json::document;
using json_node = json::node;
using json_parse_error = json::parse_error;
}
