struct @n@::storage { std::string bytes; };
namespace detail { struct string_access {
    static std::string_view read(const @n@& value) noexcept {
        if (value.is_inline()) return {value.rep_.small.bytes, value.rep_.small.tag};
        return value.rep_.large.ptr->bytes;
    }
@classic_storage@    // Called only during construction, with the empty short member active.
    static void init(@n@& value, std::string_view bytes) {
        if (bytes.size() <= @n@::inline_capacity) {
            bytes.copy(value.rep_.small.bytes, bytes.size());
            value.rep_.small.tag = static_cast<unsigned char>(bytes.size());
        } else {
            value.rep_.large = {255, new @n@::storage{std::string(bytes)}};
        }
    }
}; }
@n@::~@n@() { if (!is_inline()) delete rep_.large.ptr; }
@n@::@n@(const @n@& other) { detail::string_access::init(*this, detail::string_access::read(other)); }
@n@::@n@(@n@&& other) noexcept : rep_(other.rep_) { other.rep_.small = {}; }
@n@& @n@::operator=(const @n@& other) {
    if (this != &other) *this = @n@(other);
    return *this;
}
@n@& @n@::operator=(@n@&& other) noexcept {
    if (this != &other) {
        clear();
        rep_ = other.rep_;
        other.rep_.small = {};
    }
    return *this;
}
