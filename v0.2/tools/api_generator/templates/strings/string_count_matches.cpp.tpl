namespace str {
static int64 count_byte_window(@args@, int64 offset, const int64* length) {
    const auto needle = detail::string_access::read(other);
    if (needle.empty()) throw ValueError("Substring count requires a nonempty needle");
    auto remaining = detail::string_access::read(value);
    const auto size = detail::checked_integer_cast<int64>(remaining.size());
    if (offset < 0) offset += size;
    if (offset < 0 || offset > size) throw ValueError("Substring count offset outside subject");
    const auto available = size - offset;
    auto width = available;
    if (length) {
        width = *length < 0 ? available + *length : *length;
        if (width < 0 || width > available) throw ValueError("Substring count length outside subject");
    }
    remaining = remaining.substr(static_cast<std::size_t>(offset), static_cast<std::size_t>(width));
    std::size_t count = 0;
    for (auto match = remaining.find(needle); match != std::string_view::npos; match = remaining.find(needle)) {
        ++count;
        remaining.remove_prefix(match + needle.size());
    }
    return detail::checked_integer_cast<int64>(count);
}
int64 @name@(@args@) { return count_byte_window(value, other, 0, nullptr); }
int64 @name@(@args@, int64 offset) { return count_byte_window(value, other, offset, nullptr); }
int64 @name@(@args@, int64 offset, int64 length) { return count_byte_window(value, other, offset, &length); }
}
