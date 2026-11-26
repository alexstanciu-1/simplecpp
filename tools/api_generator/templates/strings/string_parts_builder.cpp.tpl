struct string_parts_builder::storage { std::vector<string> parts; int64 length = 0; };
void string_parts_builder::reserve(int64 n) {
    if (n < 0) throw ValueError("negative string parts reserve");
    if (n > capacity()) writable().parts.reserve(detail::checked_integer_cast<std::size_t>(n));
}
int64 string_parts_builder::capacity() const {
    return data_ ? detail::checked_integer_cast<int64>(data_->parts.capacity()) : 0;
}
int64 string_parts_builder::count() const {
    return data_ ? detail::checked_integer_cast<int64>(data_->parts.size()) : 0;
}
int64 string_parts_builder::byte_len() const { return data_ ? data_->length : 0; }
void string_parts_builder::append_string(const string& part) {
    auto& state = writable();
    if (part.size() > std::numeric_limits<int64>::max() - state.length)
        throw std::length_error("string parts byte length overflow");
    state.parts.push_back(part);
    state.length += part.size();
}
string string_parts_builder::to_string() const {
    if (!data_ || !data_->length) return {};
    std::string joined;
    joined.reserve(detail::checked_integer_cast<std::size_t>(data_->length));
    for (const auto& part : data_->parts) joined.append(part.data(), part.size());
    return string{joined.data(), data_->length};
}
void string_parts_builder::clear() {
    if (data_) {
        data_->parts.clear();
        data_->length = 0;
    }
}
