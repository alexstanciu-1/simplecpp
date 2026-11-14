namespace str {
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
template<bool reverse, bool folded>
static int64 search_position(std::string_view subject, std::string_view needle, int64 offset, search_policy policy) {
    const auto size = detail::checked_integer_cast<int64>(subject.size());
    const bool suffix = policy == search_policy::signed_offset && reverse && offset >= 0;
    if (policy == search_policy::signed_offset) {
        if (offset < 0) offset += size;
        if (offset < 0 || offset > size) throw ValueError("Search offset outside subject");
    } else if (policy == search_policy::clamp) {
        if (offset < 0) offset = 0;
        else if (offset > size) offset = size;
    } else {
        if (offset < 0) throw ValueError("Search position must be nonnegative");
        if (offset > size) {
            if (!reverse) return -1;
            offset = size;
        }
    }
    const auto pos = static_cast<std::size_t>(offset);
    if (suffix) subject.remove_prefix(pos);
    const auto match = byte_match<reverse, folded>(subject, needle, suffix ? std::string_view::npos : pos);
    if (match == std::string_view::npos) return -1;
    return detail::checked_integer_cast<int64>(match) + (suffix ? offset : 0);
}
}
