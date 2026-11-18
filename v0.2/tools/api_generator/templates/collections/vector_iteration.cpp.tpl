@inline@@count@ @n@::@limit_method@() const { return size(); }
@inline@bool_t @n@::@next_method@(@count@& position, @count@ limit, @e@& value) const {
    @count@ key;
    return @next_method@(position, limit, key, value);
}
@inline@bool_t @n@::@next_method@(@count@& position, @count@ limit, @count@& key, @e@& value) const {
    if (position < 0 || limit < 0) throw std::out_of_range("iteration_bounds");
    if (position >= limit || !storage_ || static_cast<unsigned long long>(position) >= storage_->size()) return false;
    auto selected = (*storage_)[static_cast<std::size_t>(position)];
    const auto selected_key = position;
    ++position;
    key = selected_key;
    value = static_cast<@e@&&>(selected);
    return true;
}
