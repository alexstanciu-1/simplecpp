namespace str {
@signature@ {
    const auto& values = detail::vector_access::read(pieces);
    if (values.empty()) return string{};
    const auto delimiter = detail::string_access::read(separator);
    std::string output(detail::string_access::read(values.front()));
    for (std::size_t i = 1; i < values.size(); ++i) {
        output.append(delimiter);
        output.append(detail::string_access::read(values[i]));
    }
    return detail::string_access::own(std::move(output));
}
}
