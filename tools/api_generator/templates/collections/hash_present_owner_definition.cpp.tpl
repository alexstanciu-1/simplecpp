@inline@bool @n@::has_present_owner(const @k@& key) const {
    const auto* owner = storage_ ? storage_->find(key) : nullptr;
    return owner && static_cast<bool>(*owner);
}
