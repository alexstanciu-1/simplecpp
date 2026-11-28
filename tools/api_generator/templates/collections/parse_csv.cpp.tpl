namespace str {
@signature@ {
    const detail::csv_controls controls{detail::string_access::read(separator),
        detail::string_access::read(enclosure), detail::string_access::read(escape)};
    auto strip_ending = [](std::string_view text) {
        if (text.ends_with('\n')) text.remove_suffix(1);
        if (text.ends_with('\r')) text.remove_suffix(1);
        return text;
    };
    const auto input = detail::string_access::read(subject);
    auto remaining = strip_ending(input);
    const auto ending = input.substr(remaining.size());
    @vector@ fields;
    for (;;) {
        std::size_t start = 0;
        while (start < remaining.size() && remaining[start] != controls.separator &&
               std::string_view{" \t\r\n\v\f"}.find(remaining[start]) != std::string_view::npos) ++start;
        if (start < remaining.size() && remaining[start] == controls.enclosure) {
            remaining.remove_prefix(start + 1);
            std::string field;
            bool closed = false;
            while (!remaining.empty()) {
                char ch = remaining.front();
                remaining.remove_prefix(1);
                if (ch == controls.enclosure) {
                    if (remaining.empty() || remaining.front() != ch) { closed = true; break; }
                    remaining.remove_prefix(1);
                } else if (controls.escapes(ch) && !remaining.empty()) {
                    field.push_back(ch);
                    ch = remaining.front();
                    remaining.remove_prefix(1);
                }
                field.push_back(ch);
            }
            if (!closed) field.append(ending);
            const auto end = remaining.find(controls.separator);
            field.append(remaining.substr(0, end));
            fields.append(string{field.data(), detail::checked_integer_cast<int64>(field.size())});
            if (end == remaining.npos) break;
            remaining.remove_prefix(end + 1);
        } else {
            const auto end = remaining.find(controls.separator);
            const auto field = strip_ending(remaining.substr(0, end));
            fields.append(string{field.data(), detail::checked_integer_cast<int64>(field.size())});
            if (end == remaining.npos) break;
            remaining.remove_prefix(end + 1);
        }
    }
    return fields;
}
}
