namespace str {
@signature@ {
    auto a = detail::string_access::read(left);
    auto b = detail::string_access::read(right);
    const auto digit = [](char ch) { return ch >= '0' && ch <= '9'; };
    const auto trim_zeroes = [&](std::string_view& text) {
        while (text.size() > 1 && text.front() == '0' && digit(text[1])) text.remove_prefix(1);
    };
    const auto take_digits = [&](std::string_view& text) {
        std::size_t count = 0;
        while (count < text.size() && digit(text[count])) ++count;
        const auto run = text.substr(0, count);
        text.remove_prefix(count);
        return run;
    };
    const auto skip_space = [](std::string_view& text) {
        while (!text.empty() && (text.front() == ' ' || (text.front() >= '\t' && text.front() <= '\r'))) text.remove_prefix(1);
    };
    const auto byte = [insensitive](std::string_view text) -> unsigned char {
        const unsigned char ch = text.empty() ? 0 : static_cast<unsigned char>(text.front());
        return insensitive && ch >= 'a' && ch <= 'z' ? ch - ('a' - 'A') : ch;
    };
    trim_zeroes(a);
    trim_zeroes(b);
    while (!a.empty() && !b.empty()) {
        skip_space(a);
        skip_space(b);
        if (!a.empty() && !b.empty() && digit(a.front()) && digit(b.front())) {
            const auto x = take_digits(a);
            const auto y = take_digits(b);
            if (x.front() != '0' && y.front() != '0' && x.size() != y.size()) return x.size() < y.size() ? -1 : 1;
            const auto order = x.compare(y);
            if (order != 0) return order < 0 ? -1 : 1;
            if (a.empty() || b.empty()) break;
        }
        // PHP compares the immediate suffix bytes after equal numeric runs.
        const auto x = byte(a);
        const auto y = byte(b);
        if (x != y) return x < y ? -1 : 1;
        if (!a.empty()) a.remove_prefix(1);
        if (!b.empty()) b.remove_prefix(1);
    }
    return static_cast<int>(!a.empty()) - static_cast<int>(!b.empty());
}
}
