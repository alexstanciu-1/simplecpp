namespace php {
int64 parse_decimal_int64(const string& value) {
    auto text = detail::string_access::read(value);
    if (text.starts_with('+')) {
        text.remove_prefix(1);
        if (text.starts_with('-')) throw std::invalid_argument("invalid_decimal_text");
    }
    int64 result{};
    const auto end = text.data() + text.size();
    const auto parsed = std::from_chars(text.data(), end, result);
    if (parsed.ptr != end || parsed.ec == std::errc::invalid_argument)
        throw std::invalid_argument("invalid_decimal_text");
    if (parsed.ec == std::errc::result_out_of_range)
        throw std::out_of_range("text_number_out_of_range");
    return result;
}
}
