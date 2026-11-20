@inline@@n@ @n@::@factory@(@qualifier@@e@* values, @count@ count) {
    if (count < 0 || static_cast<std::uint64_t>(count) > UINT32_MAX / 2
        || static_cast<std::uint64_t>(count) > static_cast<std::size_t>(PTRDIFF_MAX) / sizeof(storage::entry))
        throw std::length_error("hash_initializer_size_limit");
    @n@ result;
    if (count == 0) return result;
    result.storage_ = new storage;
    auto& target = *result.storage_;
    target.reserve(static_cast<std::size_t>(count));
    const auto buckets = count < 2 ? 4 : static_cast<std::uint32_t>(count) * 2;
    target.rehash(buckets, static_cast<std::size_t>(count));
    for (@count@ i = 0; i < count; ++i) {
        if (target.find(values[i].key)) throw std::invalid_argument("duplicate_initializer_key");
        target.insert_new(@key_transfer@, @value_transfer@);
    }
    return result;
}
