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
    return storage::iteration_reference(storage_, position);
}

@inline@@v@& @n@::iteration_reference(@count@ position) {
    return storage::iteration_reference(storage_, position);
}

@inline@const @v@& @n@::scpp_escape_read_slot(@count@ position) const & {
    return storage::iteration_reference(storage_, position);
}
