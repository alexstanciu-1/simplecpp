const auto input = detail::string_access::read(value);
    constexpr char digits[] = "0123456789ABCDEF";
    std::string output;
    output.reserve(input.size());
    std::size_t column = 0;
    for (std::size_t i = 0; i < input.size(); ++i) {
        const unsigned char byte = input[i];
        const char next = i + 1 < input.size() ? input[i + 1] : '\0';
        if (byte == '\r' && next == '\n') {
            output += "\r\n";
            ++i;
            column = 0;
            continue;
        }
        const bool escape = byte < 32 || byte >= 127 || byte == '=' || (byte == ' ' && next == '\r');
        const unsigned width = escape ? 3 : 1;
        // PHP 8.5 reserves extra columns even for continuation bytes (80..BF).
        unsigned needed = width;
        if (byte >= 0x80 && byte <= 0xf4) needed = byte < 0xe0 ? 6 : (byte < 0xf0 ? 9 : 12);
        if (byte <= 0xf4 && column > 75 - needed) {
            output += "=\r\n";
            column = 0;
        }
        if (escape) {
            output += '=';
            output += digits[byte >> 4];
            output += digits[byte & 15];
        } else {
            output += static_cast<char>(byte);
        }
        column += width;
    }
    return detail::string_access::own(std::move(output));
