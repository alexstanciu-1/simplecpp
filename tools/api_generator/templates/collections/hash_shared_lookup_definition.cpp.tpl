@inline@@v@ @n@::get_or_null(const @k@& key) const {
    const auto* value = storage_ ? storage_->find(key) : nullptr;
    return value ? *value : @v@{};
}
