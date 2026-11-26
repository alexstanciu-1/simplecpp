struct text_builder::storage { std::string bytes; };
void text_builder::reserve_bytes(int64 n) {
    if (n < 0) throw ValueError("negative text builder reserve");
    if (n > capacity_bytes()) writable().bytes.reserve(detail::checked_integer_cast<std::size_t>(n));
}
int64 text_builder::capacity_bytes() const {
    return data_ ? detail::checked_integer_cast<int64>(data_->bytes.capacity()) : 0;
}
int64 text_builder::byte_len() const {
    return data_ ? detail::checked_integer_cast<int64>(data_->bytes.size()) : 0;
}
void text_builder::append_string(const string& part) {
    writable().bytes.append(part.data(), detail::checked_integer_cast<std::size_t>(part.size()));
}
string text_builder::to_string() const {
    return data_ ? string{data_->bytes.data(), byte_len()} : string{};
}
string text_builder::take_string() {
    string result = to_string();
    clear();
    return result;
}
void text_builder::clear() { if (data_) data_->bytes.clear(); }
