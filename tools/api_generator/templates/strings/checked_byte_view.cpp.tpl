namespace detail {
class checked_byte_view final {
    const char* const bytes_;
    const @key@ size_;
    [[noreturn]] static void invalid_index();
public:
    explicit checked_byte_view(const @n@& owner) : bytes_(owner.data()), size_(owner.size()) {}
    checked_byte_view(const @n@&&) = delete;
    checked_byte_view(const checked_byte_view&) = delete;
    @key@ size() const noexcept { return size_; }
    @result@ read(@key@ index) const {
        if (index < 0 || index >= size_) invalid_index();
        return static_cast<@result@>(bytes_[index]);
    }
};
}
inline detail::checked_byte_view @n@::checked_bytes() const & { return detail::checked_byte_view(*this); }
