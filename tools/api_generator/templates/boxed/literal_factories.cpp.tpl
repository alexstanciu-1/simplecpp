@n@ @n@::from_entries(keyed_entry* entries, int64 count) {
    if (count < 0) throw std::length_error("negative_initializer_count");
    auto result = container();
    for (int64 i = 0; i < count; ++i) {
        auto& entry = entries[i];
        if (entry.key.index < 0) {
            if (result.has(entry.key.text)) throw std::invalid_argument("duplicate_initializer_key");
            result.set(entry.key.text, std::move(entry.value));
        } else {
            if (result.has(entry.key.index)) throw std::invalid_argument("duplicate_initializer_key");
            result.set(entry.key.index, std::move(entry.value));
        }
    }
    return result;
}

@n@ @n@::from_list(@n@* values, int64 count) {
    if (count < 0) throw std::length_error("negative_initializer_count");
    auto result = container();
    for (int64 i = 0; i < count; ++i) result.append(std::move(values[i]));
    return result;
}
