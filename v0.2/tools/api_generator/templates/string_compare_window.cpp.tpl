namespace str {
int64 @name@(@args@, int64 length, bool_t insensitive) {
    const int64 size = subject.size();
    if (offset < 0) offset += size;
    if (offset < 0 || offset > size) offset = size;
    const int64 available = size - offset;
    const int64 limit = length < 0 ? std::max<int64>(0, available + length) : length;
    const auto left = detail::string_access::read(subject).substr(static_cast<std::size_t>(offset),
        static_cast<std::size_t>(std::min(available, limit)));
    const auto right = detail::string_access::read(other).substr(0, static_cast<std::size_t>(std::min(other.size(), limit)));
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
int64 @name@(@args@) {
    return @name@(subject, other, offset, std::numeric_limits<int64>::max(), false);
}
}
