namespace str {
@signature@ {
    const auto pattern = detail::string_access::read(fill);
    if (reject_empty && pattern.empty()) throw ValueError("str_pad(): Argument #3 ($pad_string) must not be empty");
    if (side < static_cast<int64>(pad_side::left) || side > static_cast<int64>(pad_side::both))
        throw ValueError("str_pad(): Argument #4 ($pad_type) must be STR_PAD_LEFT, STR_PAD_RIGHT, or STR_PAD_BOTH");
    if (width <= subject.size() || pattern.empty()) return subject;

    const auto bytes = detail::string_access::read(subject);
    const auto extra = static_cast<std::size_t>(width) - bytes.size();
    const auto left = side == static_cast<int64>(pad_side::both) ? extra / 2
                    : side == static_cast<int64>(pad_side::left) ? extra : 0;
    std::string output;
    output.reserve(static_cast<std::size_t>(width));
    const auto append_fill = [&](std::size_t count) {
        if (pattern.size() == 1) { output.append(count, pattern.front()); return; }
        while (count >= pattern.size()) {
            output.append(pattern);
            count -= pattern.size();
        }
        output.append(pattern.substr(0, count));
    };
    append_fill(left);
    output.append(bytes);
    append_fill(extra - left);
    return detail::string_access::own(std::move(output));
}
}
