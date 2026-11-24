@inline@bool_t @n@::@next_key_method@(@count@& position, @count@ limit, @k@& key) const {
    if (position < 0 || limit < 0) throw std::out_of_range("iteration_bounds");
    auto selected_position = position;
    const auto* selected = storage_ ? storage_->iteration_select(selected_position, limit) : nullptr;
    if (!selected) { position = selected_position; return false; }
    auto selected_key = selected->key;
    key = static_cast<@k@&&>(selected_key);
    position = selected_position + 1;
    return true;
}

@inline@@v@ @n@::@current_value_method@(@count@ position) const {
    if (position <= 0 || !storage_) throw std::out_of_range("iteration_bounds");
    const auto slot = static_cast<std::uint64_t>(position - 1);
    if (slot >= storage_->used || !storage_->live[slot]) throw std::out_of_range("iteration_bounds");
    return storage_->at_slot(slot).value;
}
