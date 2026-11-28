const auto input = detail::string_access::read(value);
    std::string bytes;
    bytes.reserve(input.size());
    for (std::size_t i = 0; i < input.size(); ++i) {
        const char byte = input[i];
        if (byte == '\r' || byte == '\n') {
            bytes += other ? "<br />" : "<br>";
            if (i + 1 < input.size() && input[i + 1] == (byte == '\r' ? '\n' : '\r')) {
                bytes += byte;
                ++i;
            }
        }
        bytes += input[i];
    }
    return detail::string_access::own(std::move(bytes));
