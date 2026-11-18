@count@ @n@::@limit_method@() const { return size(); }
bool_t @n@::@next_method@(@count@& position, @count@ limit, @e@& value) const {
    @count@ key;
    return @next_method@(position, limit, key, value);
}
bool_t @n@::@next_method@(@count@& position, @count@ limit, @count@& key, @e@& value) const {
    if (position < 0 || limit < 0) throw std::out_of_range("iteration_bounds");
    if (position >= limit || !storage_ || static_cast<unsigned long long>(position) >= storage_->values.size()) return false;
    auto selected = storage_->values[static_cast<std::size_t>(position)];
    const auto selected_key = position;
    ++position;
    key = selected_key;
    value = std::move(selected);
    return true;
}
