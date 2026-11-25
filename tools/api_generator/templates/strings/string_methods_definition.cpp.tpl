@n@::@n@(const char* bytes) {
    if (!bytes) throw std::invalid_argument("null_string_pointer");
    detail::string_access::init(*this, bytes);
}
@n@::@n@(const char* bytes, @count@ count) {
    if (!bytes) throw std::invalid_argument("null_string_pointer");
    detail::string_access::init(*this, {bytes, detail::byte_count(count)});
}
@count@ @n@::size() const { return detail::checked_integer_cast<@count@>(detail::string_access::read(*this).size()); }
bool_t @n@::empty() const noexcept { return detail::string_access::read(*this).empty(); }
const char* @n@::data() const noexcept { return detail::string_access::read(*this).data(); }
void @n@::append(const @n@& other) {
    const auto suffix = detail::string_access::read(other);
    if (!is_inline()) {
        rep_.large.ptr->bytes.append(suffix);
    } else if (suffix.size() <= inline_capacity - rep_.small.tag) {
        const auto length = rep_.small.tag;
        suffix.copy(rep_.small.bytes + length, suffix.size());
        rep_.small.tag = static_cast<unsigned char>(length + suffix.size());
        rep_.small.bytes[rep_.small.tag] = '\0';
    } else {
        std::string joined(detail::string_access::read(*this));
        joined.append(suffix);
        rep_.large = {255, new storage{std::move(joined)}};
    }
}
void @n@::clear() noexcept {
    if (!is_inline()) delete rep_.large.ptr;
    rep_.small = {};
}
