namespace str {
// Consume one malformed sequence without swallowing the start of another character.
static std::size_t html_invalid_width(std::string_view bytes) {
    const unsigned char first = bytes.front();
    if (first < 0xc2 || first > 0xf4) return 1;
    const unsigned limit = first < 0xe0 ? 2 : first < 0xf0 ? 3 : 4;
    std::size_t n = 1;
    while (n < limit && n < bytes.size()) {
        const unsigned char next = bytes[n];
        if (next < 0x80 || (next >= 0xc2 && next <= 0xf4)) break;
        ++n;
    }
    return n;
}

@signature@ {
    if ((flags & ~int64{15}) != 0 || (flags & 3) == 1)
        throw ValueError("htmlspecialchars(): unsupported flags for HTML 4.01");
    if (!double_encode) throw ValueError("htmlspecialchars(): double_encode must be true");
    if (encoding) {
        const auto name = detail::string_access::read(*encoding);
        if (name.size() != 5 || (name[0] != 'U' && name[0] != 'u') ||
            (name[1] != 'T' && name[1] != 't') || (name[2] != 'F' && name[2] != 'f') ||
            name[3] != '-' || name[4] != '8')
            throw ValueError("htmlspecialchars(): encoding must be UTF-8");
    }
    auto input = detail::string_access::read(value);
    std::string output;
    output.reserve(input.size());
    while (!input.empty()) {
        const unsigned char byte = input.front();
        if (byte < 0x80) {
            if (byte == '&') output += "&amp;";
            else if (byte == '<') output += "&lt;";
            else if (byte == '>') output += "&gt;";
            else if (byte == '"' && (flags & 2)) output += "&quot;";
            else if (byte == '\'' && (flags & 1)) output += "&#039;";
            else output += static_cast<char>(byte);
            input.remove_prefix(1);
        } else if (const auto width = detail::utf8::validated_width(input)) {
            output.append(input.data(), width);
            input.remove_prefix(width);
        } else {
            if (!(flags & 4)) {
                if (!(flags & 8)) return {};
                output += "\xef\xbf\xbd";
            }
            input.remove_prefix(html_invalid_width(input));
        }
    }
    return detail::string_access::own(std::move(output));
}
}
