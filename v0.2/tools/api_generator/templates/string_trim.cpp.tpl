namespace str {
static string trim_edges(const string& value, trim_side side, const auto& matches) {
    auto bytes = detail::string_access::read(value);
    if (side != trim_side::end)
        while (!bytes.empty() && matches(bytes.front())) bytes.remove_prefix(1);
    if (side != trim_side::start)
        while (!bytes.empty() && matches(bytes.back())) bytes.remove_suffix(1);
    return string{bytes.data(), static_cast<@count@>(bytes.size())};
}
@signature@ {
    const char extra = set == trim_set::php_space ? '\0' : '\f';
    return trim_edges(value, side, [extra](char ch) {
        return ch == ' ' || ch == '\t' || ch == '\n' || ch == '\v' || ch == '\r' || ch == extra;
    });
}
@masked@ {
    const auto bytes = detail::string_access::read(mask);
    if (bytes.empty()) return value;
    unsigned char members[32]{};
    for (unsigned char byte : bytes)
        members[byte >> 3] |= static_cast<unsigned char>(1u << (byte & 7));
    return trim_edges(value, side, [&members](unsigned char byte) {
        return (members[byte >> 3] & (1u << (byte & 7))) != 0;
    });
}
}
