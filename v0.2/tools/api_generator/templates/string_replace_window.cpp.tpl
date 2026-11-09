namespace str {
@signature@ {
    const int64 size = subject.size();
    const int64 begin = byte_position(size, offset);
    int64 removed;
    if (length < 0) {
        const int64 end = byte_position(size, length);
        if (end <= begin) return subject;
        removed = end - begin;
    } else {
        removed = length > size - begin ? size - begin : length;
    }
    std::string output(detail::string_access::read(subject));
    output.replace(static_cast<std::size_t>(begin), static_cast<std::size_t>(removed), detail::string_access::read(replacement));
    return detail::string_access::own(std::move(output));
}
}
