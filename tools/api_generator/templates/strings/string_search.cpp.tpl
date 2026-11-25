namespace str {
int64 @name@(@args@) {
    const auto pos = byte_match<@reverse@, @fold@>(detail::string_access::read(subject), detail::string_access::read(needle),
        @reverse@ ? std::string_view::npos : 0);
    return pos == std::string_view::npos ? -1 : detail::checked_integer_cast<int64>(pos);
}
int64 @name@(@args@, int64 offset, search_policy policy) {
    return search_position<@reverse@, @fold@>(detail::string_access::read(subject), detail::string_access::read(needle), offset, policy);
}
}
