@type@::storage& @type@::writable() {
    if (!data_) data_ = new storage{};
    return *data_;
}
@type@::~@type@() { delete data_; }
@type@::@type@(const @type@& other)
    : data_(other.data_ ? new storage(*other.data_) : nullptr) {}
@type@::@type@(@type@&& other) noexcept : data_(std::exchange(other.data_, nullptr)) {}
@type@& @type@::operator=(const @type@& other) {
    if (this != &other) *this = @type@(other);
    return *this;
}
@type@& @type@::operator=(@type@&& other) noexcept {
    if (this != &other) {
        delete data_;
        data_ = std::exchange(other.data_, nullptr);
    }
    return *this;
}
void @type@::append_int(int64 value) {
    char buffer[32];
    const auto result = std::to_chars(buffer, buffer + sizeof(buffer), value);
    append_string(string{buffer, result.ptr - buffer});
}
void @type@::append_bool(bool_t value) { append_string(string{value ? "1" : ""}); }
