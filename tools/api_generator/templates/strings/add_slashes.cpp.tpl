const auto input = detail::string_access::read(value);
    std::string bytes;
    bytes.reserve(input.size());
    for (char byte : input) {
        if (byte == '\0' || byte == '\'' || byte == '"' || byte == '\\') bytes += '\\';
        bytes += byte == '\0' ? '0' : byte;
    }
    return detail::string_access::own(std::move(bytes));
