@inline@@count@ @n@::@limit_method@() const { return static_cast<@count@>(storage_ ? storage_->physical_size() : 0); }
@inline@bool_t @n@::@next_method@(@count@& position, @count@ limit, iteration_output& output) const {
    if (position < 0 || limit < 0) throw std::out_of_range("iteration_bounds");
    auto selected_position = position;
    const auto* selected = storage_ ? storage_->iteration_select(selected_position, limit) : nullptr;
    if (!selected) { position = selected_position; return false; }
    output.publish(selected->value);
    position = selected_position + 1;
    return true;
}
@inline@bool_t @n@::@next_method@(@count@& position, @count@ limit, @k@& key, iteration_output& output) const {
    if (position < 0 || limit < 0) throw std::out_of_range("iteration_bounds");
    auto selected_position = position;
    const auto* selected = storage_ ? storage_->iteration_select(selected_position, limit) : nullptr;
    if (!selected) { position = selected_position; return false; }
    auto selected_value = selected->value;
    auto selected_key = selected->key;
    key = static_cast<@k@&&>(selected_key);
    output.publish(static_cast<@v@&&>(selected_value));
    position = selected_position + 1;
    return true;
}
