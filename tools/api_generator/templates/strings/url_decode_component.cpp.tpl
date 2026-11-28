namespace str {
template<bool raw>
static string decode_component(const string& value) {
    const auto input = detail::string_access::read(value);
    std::string output;
    output.reserve(input.size());
    for (std::size_t i = 0; i < input.size(); ++i) {
        const char byte = input[i];
        if (byte == '%' && input.size() - i >= 3) {
            const int high = hex_digit(input[i + 1]);
            const int low = hex_digit(input[i + 2]);
            if (high >= 0 && low >= 0) {
                output += static_cast<char>((high << 4) | low);
                i += 2;
                continue;
            }
        }
        output += (!raw && byte == '+') ? ' ' : byte;
    }
    return detail::string_access::own(std::move(output));
}
}
