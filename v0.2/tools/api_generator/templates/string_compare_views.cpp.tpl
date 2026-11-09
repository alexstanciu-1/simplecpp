namespace detail {
static int64 compare_byte_views(std::string_view left, std::string_view right, bool_t insensitive) {
    if (!insensitive) return left.compare(right);
    const auto fold = [](unsigned char byte) -> int {
        return byte >= 'A' && byte <= 'Z' ? byte + ('a' - 'A') : byte;
    };
    for (std::size_t i = 0; i < std::min(left.size(), right.size()); ++i) {
        const int difference = fold(left[i]) - fold(right[i]);
        if (difference != 0) return difference;
    }
    return (left.size() > right.size()) - (left.size() < right.size());
}
}
