@sig@ {
    const auto& a = @access@::read(left);
    const auto& b = @access@::read(right);
    if (a.size() != b.size()) return @different@;
    for (std::size_t i = 0; i < a.size(); ++i) if (!(a[i] == b[i])) return @different@;
    return @equal@;
}
