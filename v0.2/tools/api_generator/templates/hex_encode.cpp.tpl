namespace str {
@signature@ {
    constexpr char digits[] = "0123456789abcdef";
    const auto bytes = detail::string_access::read(value);
    std::string output;
    if (bytes.size() > output.max_size() / 2) throw std::length_error("hex encoding too large");
    output.resize(bytes.size() * 2);
    std::size_t pos = 0;
    for (unsigned char byte : bytes) {
        output[pos++] = digits[byte >> 4];
        output[pos++] = digits[byte & 15];
    }
    return detail::string_access::own(std::move(output));
}
}
