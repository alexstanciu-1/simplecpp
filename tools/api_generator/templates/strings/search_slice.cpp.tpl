namespace str {
@signature@ {
    const auto subject = detail::string_access::read(value);
    const auto needle = detail::string_access::read(other);
@validation@    const auto match = @match@;
    if (match == std::string_view::npos) return @result@::from_false();
    const auto slice = @slice@;
    return @result@{string{slice.data(), static_cast<int64>(slice.size())}};
}
}
