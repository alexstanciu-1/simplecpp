@n@ @n@::@factory@(@qualifier@@e@* values, @count@ count) {
    if (count < 0) throw std::length_error("negative_initializer_count");
    @n@ result;
    if (count == 0) return result;
    result.storage_ = new storage;
    auto& target = *result.storage_;
    target.data = detail::vector_allocate(static_cast<std::size_t>(count), sizeof(@e@), alignof(@e@));
    target.capacity = static_cast<std::size_t>(count);
    for (@count@ i = 0; i < count; ++i) {
        ::new (target.data + target.count * sizeof(@e@)) @e@(@transfer@);
        ++target.count;
    }
    return result;
}
