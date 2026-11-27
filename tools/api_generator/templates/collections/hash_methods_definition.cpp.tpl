@inline@@count@ @n@::size() const { return static_cast<@count@>(storage_ ? storage_->size() : 0); }
@inline@bool_t @n@::empty() const noexcept { return bool_t{!storage_ || storage_->size() == 0}; }
@inline@bool_t @n@::has(const @k@& key) const { return bool_t{storage_ && storage_->find(key)}; }
@inline@@v@ @n@::at(const @k@& key) const { const auto* value = storage_ ? storage_->find(key) : nullptr; if (!value) throw std::out_of_range("hash_missing_key"); return *value; }
@inline@@v@& @n@::checked_slot(const @k@& key) & {
    if (storage_) {
        const auto found = storage_->locate(key);
        if (found.position != UINT32_MAX) return storage_->at_slot(found.position).value;
    }
    throw std::out_of_range("hash_missing_key");
}
@inline@void @n@::set(@k@ key, @v@ value) { if (!storage_) storage_ = new storage; storage_->set(static_cast<@k@&&>(key), static_cast<@v@&&>(value)); }
@inline@bool_t @n@::remove(const @k@& key) { return bool_t{storage_ && storage_->remove(key)}; }
@inline@void @n@::clear() noexcept { delete storage_; storage_ = nullptr; }
