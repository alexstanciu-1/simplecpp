namespace str {
@signature@ {
    if (limit < 0) throw ValueError("Byte comparison limit must be nonnegative");
    if (limit == 0) return 0;
    const auto count = static_cast<std::size_t>(std::min<std::uint64_t>(limit, std::string_view::npos));
    return detail::compare_byte_views(detail::string_access::read(left).substr(0, count),
        detail::string_access::read(right).substr(0, count), insensitive);
}
}
