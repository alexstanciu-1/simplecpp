const auto from = detail::string_access::read(other);
    const auto to = detail::string_access::read(third);
    const auto pairs = std::min(from.size(), to.size());
    if (pairs == 0 || value.empty()) return value;

    unsigned char translation[256];
    for (unsigned i = 0; i < 256; ++i) translation[i] = static_cast<unsigned char>(i);
    for (std::size_t i = 0; i < pairs; ++i) translation[static_cast<unsigned char>(from[i])] = to[i];
    std::string bytes(detail::string_access::read(value));
    for (char& byte : bytes) byte = static_cast<char>(translation[static_cast<unsigned char>(byte)]);
    return detail::string_access::own(std::move(bytes));
