std::string bytes(detail::string_access::read(value));
    for (char& byte : bytes) {
        if (byte >= 'A' && byte <= 'Z') byte = static_cast<char>('A' + (byte - 'A' + 13) % 26);
        else if (byte >= 'a' && byte <= 'z') byte = static_cast<char>('a' + (byte - 'a' + 13) % 26);
    }
    return detail::string_access::own(std::move(bytes));
