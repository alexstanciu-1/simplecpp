    auto bits = detail::fnv1a64({arg0.data(), static_cast<std::size_t>(arg0.size())});
    constexpr char digits[] = "0123456789abcdef";
    char hex[16];
    for (int i = 15; i >= 0; --i) {
        hex[i] = digits[bits & 15];
        bits >>= 4;
    }
    return string{hex, 16};
