if (other <= 0) throw ValueError("chunk_split(): Argument #2 ($length) must be greater than 0");
    const auto input = detail::string_access::read(value);
    const auto separator = detail::string_access::read(third);
    if (input.empty()) return third;
    if (separator.empty()) return value;
    std::string bytes;
    const auto width = static_cast<std::size_t>(other);
    for (auto rest = input; !rest.empty();) {
        const auto chunk = rest.substr(0, width);
        bytes += chunk;
        bytes += separator;
        rest.remove_prefix(chunk.size());
    }
    return detail::string_access::own(std::move(bytes));
