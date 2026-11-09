namespace str {
@signature@ {
    const auto needle = detail::string_access::read(other);
    if (needle.empty()) throw ValueError("Substring count requires a nonempty needle");
    auto remaining = detail::string_access::read(value);
    std::size_t count = 0;
    for (auto match = remaining.find(needle); match != std::string_view::npos; match = remaining.find(needle)) {
        ++count;
        remaining.remove_prefix(match + needle.size());
    }
    return detail::checked_integer_cast<int64>(count);
}
}
