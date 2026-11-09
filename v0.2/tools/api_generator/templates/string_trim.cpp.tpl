namespace str {
@signature@ {
    auto bytes = detail::string_access::read(value);
    const char extra = set == trim_set::php_space ? '\0' : '\f';
    const auto matches = [extra](char ch) {
        return ch == ' ' || ch == '\t' || ch == '\n' || ch == '\v' || ch == '\r' || ch == extra;
    };
    if (side != trim_side::end)
        while (!bytes.empty() && matches(bytes.front())) bytes.remove_prefix(1);
    if (side != trim_side::start)
        while (!bytes.empty() && matches(bytes.back())) bytes.remove_suffix(1);
    return string{bytes.data(), static_cast<@count@>(bytes.size())};
}
}
