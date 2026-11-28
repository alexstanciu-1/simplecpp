std::string bytes(detail::string_access::read(value));
    bool separators[256]{};
    for (unsigned char byte : detail::string_access::read(other)) separators[byte] = true;
    bool at_start = true;
    for (char& byte : bytes) {
        if (at_start && byte >= 'a' && byte <= 'z') byte -= 'a' - 'A';
        at_start = separators[static_cast<unsigned char>(byte)];
    }
    return detail::string_access::own(std::move(bytes));
