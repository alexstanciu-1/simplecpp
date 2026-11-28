namespace str {
@signature@ {
    const auto text = detail::string_access::read(value);
    if (text.size() % 2) return @result@::from_false();
    std::string output;
    output.reserve(text.size() / 2);
    for (std::size_t i = 0; i < text.size(); i += 2) {
        const int high = hex_digit(text[i]);
        const int low = hex_digit(text[i + 1]);
        if (high < 0 || low < 0) return @result@::from_false();
        output.push_back(static_cast<char>((high << 4) | low));
    }
    return @result@{detail::string_access::own(std::move(output))};
}
}
