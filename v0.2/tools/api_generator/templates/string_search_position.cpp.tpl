namespace str {
static int64 search_position(std::string_view subject, std::string_view needle, int64 offset, search_policy policy, bool reverse) {
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
    const auto match = reverse ? subject.rfind(needle, suffix ? std::string_view::npos : pos) : subject.find(needle, pos);
    if (match == std::string_view::npos) return -1;
    return detail::checked_integer_cast<int64>(match) + (suffix ? offset : 0);
}
}
