const auto bytes = detail::string_access::read(value);
    // PHP 8.5 stops reading at a literal NUL. A decoded =00 is ordinary output.
    const auto input = bytes.substr(0, bytes.find('\0'));
    std::string output;
    output.reserve(input.size());
    for (std::size_t i = 0; i < input.size();) {
        const char byte = input[i++];
        if (byte == '=') {
            if (input.size() - i >= 2) {
                const int high = hex_digit(input[i]);
                const int low = hex_digit(input[i + 1]);
                if (high >= 0 && low >= 0) {
                    output += static_cast<char>((high << 4) | low);
                    i += 2;
                    continue;
                }
            }
            auto end = i;
            while (end < input.size() && (input[end] == ' ' || input[end] == '\t')) ++end;
            if (end == input.size()) break;
            if (input[end] == '\r' || input[end] == '\n') {
                i = end + 1;
                if (input[end] == '\r' && i < input.size() && input[i] == '\n') ++i;
                continue;
            }
        }
        output += byte;
    }
    return detail::string_access::own(std::move(output));
