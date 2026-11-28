namespace str {
@signature@ {
    const auto bytes = detail::string_access::read(value);
    const auto set = detail::string_access::read(mask);
    const int64 size = value.size();
    const int64 start = offset < 0 ? std::max<int64>(0, size + offset) : std::min(size, offset);
    const int64 remaining = size - start;
    const int64 count = length < 0 ? std::max<int64>(0, remaining + length) : std::min(remaining, length);
    const auto window = bytes.substr(static_cast<std::size_t>(start), static_cast<std::size_t>(count));
    const auto end = complement ? window.find_first_of(set) : window.find_first_not_of(set);
    return end == std::string_view::npos ? count : static_cast<int64>(end);
}
}
