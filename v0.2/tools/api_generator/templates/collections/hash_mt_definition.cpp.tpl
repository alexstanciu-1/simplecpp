@inline@const unsigned char* @n@::mt_live() const noexcept { return storage_ ? storage_->live : nullptr; }
@inline@const void* @n@::mt_field(@count@ position, bool key) const noexcept {
    const auto& entry = storage_->at_slot(position);
    return key ? static_cast<const void*>(&entry.key) : static_cast<const void*>(&entry.value);
}
