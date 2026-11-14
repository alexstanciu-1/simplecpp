namespace str {
static string @name@_impl(@args@, const auto& on_match) {
    const auto needle = detail::string_access::read(search);
    if (needle.empty()) return subject;
    auto remaining = detail::string_access::read(subject);
    auto match = remaining.find(needle);
    if (match == std::string_view::npos) return subject;

    const auto inserted = detail::string_access::read(replacement);
    std::string output;
    do {
        output.append(remaining.substr(0, match));
        output.append(inserted);
        on_match();
        remaining.remove_prefix(match + needle.size());
        if (mode == replace_mode::first) break;
        match = remaining.find(needle);
    } while (match != std::string_view::npos);
    output.append(remaining);
    return detail::string_access::own(std::move(output));
}
string @name@(@args@) {
    return @name@_impl(subject, search, replacement, mode, [] {});
}
}
