struct @n@::storage { std::string bytes; };
namespace detail { struct string_access {
    static const std::string& read(const @n@& value) noexcept { static const std::string empty; return value.storage_ ? value.storage_->bytes : empty; }
}; }
@ld@