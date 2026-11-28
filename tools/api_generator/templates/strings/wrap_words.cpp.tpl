const auto input = detail::string_access::read(value);
    if (input.empty()) return value;
    const auto separator = detail::string_access::read(third);
    if (separator.empty()) throw ValueError("wordwrap(): Argument #3 ($break) must not be empty");
    if (fourth && other == 0) throw ValueError("wordwrap(): Argument #4 ($cut_long_words) cannot be true when argument #2 ($width) is 0");

    // PHP recognizes an existing break through its first NUL, consuming its full width.
    const auto nul = separator.find('\0');
    const auto prefix = separator.substr(0, nul == std::string_view::npos ? nul : nul + 1);
    std::string bytes;
    std::size_t start = 0, space = 0;
    for (std::size_t pos = 0; pos < input.size(); ++pos) {
        if (input.size() - pos >= separator.size() && input.substr(pos).starts_with(prefix)) {
            pos += separator.size() - 1;
            bytes += input.substr(start, pos + 1 - start);
            start = space = pos + 1;
            continue;
        }
        const bool full = static_cast<int64>(pos - start) >= other;
        if (input[pos] == ' ') {
            space = pos;
            if (!full) continue;
        } else if (!full || (space <= start && !fourth)) {
            continue;
        }
        const bool replace_space = input[pos] == ' ' || space > start;
        const auto end = replace_space ? space : pos;
        bytes += input.substr(start, end - start);
        bytes += separator;
        start = end + (replace_space ? 1 : 0);
        space = start;
    }
    bytes += input.substr(start);
    return detail::string_access::own(std::move(bytes));
