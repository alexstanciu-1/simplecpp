namespace str {
@signature@ {
    const auto delimiter = detail::string_access::read(separator);
    if (delimiter.empty()) throw ValueError("Split requires a nonempty separator");
    auto remaining = detail::string_access::read(subject);
    @vector@ parts;
    for (;;) {
        const auto match = remaining.find(delimiter);
        const auto field = remaining.substr(0, match);
        parts.append(string{field.data(), detail::checked_integer_cast<int64>(field.size())});
        if (match == std::string_view::npos) return parts;
        remaining.remove_prefix(match + delimiter.size());
    }
}
}
