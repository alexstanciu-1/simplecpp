namespace str {
template<bool raw>
static string encode_component(const string& value) {
    constexpr char digits[] = "0123456789ABCDEF";
    const auto input = detail::string_access::read(value);
    std::string output;
    output.reserve(input.size());
    for (const unsigned char byte : input) {
        if ((byte >= 'A' && byte <= 'Z') || (byte >= 'a' && byte <= 'z') ||
            (byte >= '0' && byte <= '9') || byte == '-' || byte == '_' || byte == '.' ||
            (raw && byte == '~')) {
            output += static_cast<char>(byte);
        } else if (!raw && byte == ' ') {
            output += '+';
        } else {
            output += '%';
            output += digits[byte >> 4];
            output += digits[byte & 15];
        }
    }
    return detail::string_access::own(std::move(output));
}
}
