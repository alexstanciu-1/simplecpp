// Private specializations keep the runtime API concrete and the exact search on string_view's fast path.
template<bool reverse, bool folded>
static std::size_t byte_match(std::string_view subject, std::string_view needle, std::size_t pos) {
    if constexpr (!folded) return reverse ? subject.rfind(needle, pos) : subject.find(needle, pos);
    if (needle.size() > subject.size()) return std::string_view::npos;
    const auto last = subject.size() - needle.size();
    if (!reverse && pos > last) return std::string_view::npos;
    auto start = reverse ? std::min(pos, last) : pos;
    if (needle.empty()) return start;
    const auto first = detail::fold_ascii_byte(needle.front());
    for (;;) {
        if (detail::fold_ascii_byte(subject[start]) == first) {
            std::size_t i = 1;
            while (i < needle.size() && detail::fold_ascii_byte(subject[start + i]) == detail::fold_ascii_byte(needle[i])) ++i;
            if (i == needle.size()) return start;
        }
        if (reverse ? start == 0 : start == last) return std::string_view::npos;
        if constexpr (reverse) --start;
        else ++start;
    }
}
