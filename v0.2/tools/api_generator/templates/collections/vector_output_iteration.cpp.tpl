@inline@bool_t @n@::@next_method@(@count@& position, @count@ limit, iteration_output& output) const {
    @count@ key;
    return @next_method@(position, limit, key, output);
}
@inline@bool_t @n@::@next_method@(@count@& position, @count@ limit, @count@& key, iteration_output& output) const {
    if (position < 0 || limit < 0) throw std::out_of_range("iteration_bounds");
    const auto* item = storage_ ? storage_->iteration_select(position, limit) : nullptr;
    if (!item) return false;
    auto selected = *item;
    key = position++;
    output.publish(static_cast<@e@&&>(selected));
    return true;
}
