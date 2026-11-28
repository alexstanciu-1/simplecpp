const auto input = detail::string_access::read(value);
    std::string bytes;
    bytes.reserve(input.size());
    for (std::size_t i = 0; i < input.size(); ++i) {
        char byte = input[i];
        if (byte == '\\') {
            if (++i == input.size()) break;
            byte = input[i] == '0' ? '\0' : input[i];
        }
        bytes += byte;
    }
    return detail::string_access::own(std::move(bytes));
