@count@ @n@::@limit_method@() const { return static_cast<@count@>(storage_ ? storage_->physical_size() : 0); }
bool_t @n@::@next_method@(@count@& position, @count@ limit, @v@& value) const {
    if (position < 0 || limit < 0) throw std::out_of_range("iteration_bounds");
    return storage_ && storage_->@next_method@(position, limit, value);
}
bool_t @n@::@next_method@(@count@& position, @count@ limit, @k@& key, @v@& value) const {
    if (position < 0 || limit < 0) throw std::out_of_range("iteration_bounds");
    return storage_ && storage_->@next_method@(position, limit, value, &key);
}
