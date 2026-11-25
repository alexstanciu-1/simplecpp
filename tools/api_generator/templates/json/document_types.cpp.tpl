namespace scpp {
namespace detail::json { struct document_storage; struct document_access; }
class json_document final {
    friend struct detail::json::document_access;
    detail::json::document_storage* data_ = nullptr;
public:
    json_document() noexcept = default;
    ~json_document();
    json_document(const json_document&) noexcept;
    json_document(json_document&&) noexcept;
    json_document& operator=(const json_document&) noexcept;
    json_document& operator=(json_document&&) noexcept;
};
class json_node final {
    friend struct detail::json::document_access;
    json_document owner_;
    __SIZE_TYPE__ index_ = 0;
};
struct json_parse_error {
    string category;
    int64 byte_offset{};
    string message;
};
}
