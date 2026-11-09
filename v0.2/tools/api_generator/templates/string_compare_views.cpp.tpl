namespace detail {
static int64 compare_byte_views(std::string_view left, std::string_view right, bool_t insensitive) {
    if (!insensitive) return left.compare(right);
    for (std::size_t i = 0; i < std::min(left.size(), right.size()); ++i) {
        const int difference = fold_ascii_byte(left[i]) - fold_ascii_byte(right[i]);
        if (difference != 0) return difference;
    }
    return (left.size() > right.size()) - (left.size() < right.size());
}
}
