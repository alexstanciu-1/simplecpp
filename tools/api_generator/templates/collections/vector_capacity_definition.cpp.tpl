@inline@@count@ @n@::capacity() const noexcept {
    return storage_ ? static_cast<@count@>(storage_->capacity) : 0;
}
@inline@void @n@::resize(@count@ count, @qual@@e@@ref@ fill) {
    if (count < 0) throw ::scpp::ValueError("negative_vector_size");
    using element_type = @e@;
    const auto original = size();
    if (count == original) return;
    if (count < original) {
        while (storage_->count > static_cast<std::size_t>(count))
            (*storage_)[--storage_->count].~element_type();
        return;
    }
    reserve(count);
    try {
        while (storage_->count < static_cast<std::size_t>(count)) {
            ::new (storage_->data + storage_->count * sizeof(@e@)) @e@(fill);
            ++storage_->count;
        }
    } catch (...) {
        while (storage_->count > static_cast<std::size_t>(original))
            (*storage_)[--storage_->count].~element_type();
        throw;
    }
}
@inline@@n@ @n@::@fill_member@(@count@ count, @qual@@e@@ref@ fill) {
    @n@ values;
    values.resize(count, fill);
    return values;
}
@inline@void @n@::compact() { compact(size()); }
@inline@void @n@::compact(@count@ requested) {
    if (requested < 0) throw ::scpp::ValueError("negative_vector_capacity");
    const auto length = size();
    const auto target = requested < length ? length : requested;
    if (target == capacity()) return;
    @n@ replacement;
    replacement.reserve(target);
    for (@count@ i = 0; i < length; ++i)
        replacement.storage_->append(static_cast<@e@&&>((*storage_)[static_cast<std::size_t>(i)]));
    *this = static_cast<@n@&&>(replacement);
}
