@count@ @n@::size() const { return static_cast<@count@>(detail::vector_access::read(*this).size()); }
bool_t @n@::empty() const noexcept { return detail::vector_access::read(*this).empty(); }
void @n@::clear() noexcept { if (storage_) storage_->clear(); }
void @n@::append(@e@ value) {
    if (storage_) { storage_->append(static_cast<@e@&&>(value)); return; }
    auto* fresh = new storage;
    try { fresh->append(static_cast<@e@&&>(value)); }
    catch (...) { delete fresh; throw; }
    storage_ = fresh;
}
@e@ @n@::at(@count@ index) const { const auto& values = detail::vector_access::read(*this); return values[detail::vector_index(index, values.size())]; }
void @n@::set(@count@ index, @e@ value) { const auto offset = detail::vector_index(index, detail::vector_access::read(*this).size()); (*storage_)[offset] = static_cast<@e@&&>(value); }
bool_t @n@::remove(@count@ index) {
    if (!has(index)) return false;
    storage_->remove(static_cast<std::size_t>(index));
    return true;
}
bool_t @n@::has(@count@ index) const noexcept {
    return index >= 0 && storage_ && static_cast<unsigned long long>(index) < storage_->size();
}
