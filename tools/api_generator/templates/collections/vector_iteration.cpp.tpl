@inline@@count@ @n@::@limit_method@() const { return size(); }
@inline@bool_t @n@::@next_method@(@count@& position, @count@ limit, @e@& value) const {
    @count@ key;
    return @next_method@(position, limit, key, value);
}
@inline@bool_t @n@::@next_method@(@count@& position, @count@ limit, @count@& key, @e@& value) const {
    if (position < 0 || limit < 0) throw std::out_of_range("iteration_bounds");
    const auto* item = storage_ ? storage_->iteration_select(position, limit) : nullptr;
    if (!item) return false;
    auto selected = *item;
    const auto selected_key = position;
    ++position;
    key = selected_key;
    value = static_cast<@e@&&>(selected);
    return true;
}
