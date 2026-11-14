namespace str {
@signature@ {
    return @name@(subject, separator, std::numeric_limits<int64>::max(), split_policy::cap);
}
@limited@ {
    const auto delimiter = detail::string_access::read(separator);
    if (delimiter.empty()) throw ValueError("Split requires a nonempty separator");
    auto remaining = detail::string_access::read(subject);
    @vector@ parts;
    if (limit < 0) {
        if (policy == split_policy::cap) throw ValueError("Split limit must be nonnegative");
        // Count kept fields from the negative limit without allocating dropped fields or negating INT64_MIN.
        auto tail = remaining;
        for (auto match = tail.find(delimiter); match != std::string_view::npos; match = tail.find(delimiter)) {
            ++limit;
            tail.remove_prefix(match + delimiter.size());
        }
        ++limit;
        policy = split_policy::cap;
    } else if (policy == split_policy::remainder && limit == 0) {
        limit = 1;
    }
    while (limit > 0) {
        const auto match = policy == split_policy::remainder && limit == 1 ? std::string_view::npos : remaining.find(delimiter);
        const auto field = remaining.substr(0, match);
        parts.append(string{field.data(), detail::checked_integer_cast<int64>(field.size())});
        if (match == std::string_view::npos) break;
        remaining.remove_prefix(match + delimiter.size());
        --limit;
    }
    return parts;
}
}
