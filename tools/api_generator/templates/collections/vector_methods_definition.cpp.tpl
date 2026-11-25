@inline@@count@ @n@::size() const { return static_cast<@count@>(@access@::read(*this).size()); }
@inline@bool_t @n@::empty() const noexcept { return @access@::read(*this).empty(); }
@inline@void @n@::clear() noexcept { if (storage_) storage_->clear(); }
@inline@void @n@::append(@e@ value) {
    if (storage_) { storage_->append(static_cast<@e@&&>(value)); return; }
    auto* fresh = new storage;
    try { fresh->append(static_cast<@e@&&>(value)); }
    catch (...) { delete fresh; throw; }
    storage_ = fresh;
}
@inline@@e@ @n@::at(@count@ index) const { const auto& values = @access@::read(*this); return values[@buffer@::vector_index(index, values.size())]; }
@inline@@e@& @n@::iteration_reference(@count@ index) {
    const auto offset = @buffer@::vector_index(index, @access@::read(*this).size());
    return (*storage_)[offset];
}
@inline@void @n@::set(@count@ index, @e@ value) { iteration_reference(index) = static_cast<@e@&&>(value); }
@inline@bool_t @n@::remove(@count@ index) {
    if (!has(index)) return false;
    storage_->remove(static_cast<std::size_t>(index));
    return true;
}
@inline@bool_t @n@::has(@count@ index) const noexcept {
    return index >= 0 && storage_ && static_cast<unsigned long long>(index) < storage_->size();
}
