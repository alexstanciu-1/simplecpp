@count@ @n@::size() const { return @count@{detail::checked_integer_cast<long long>(detail::vector_access::read(*this).size())}; }
bool_t @n@::empty() const noexcept { return bool_t{detail::vector_access::read(*this).empty()}; }
void @n@::clear() noexcept { if (storage_) storage_->values.clear(); }
void @n@::append(@e@ value) { if (!storage_) storage_ = new storage; storage_->values.push_back(std::move(value)); }
@e@ @n@::at(@count@ index) const { const auto& values = detail::vector_access::read(*this); return values[detail::sequence_index(index, values.size())]; }
void @n@::set(@count@ index, @e@ value) { const auto offset = detail::sequence_index(index, detail::vector_access::read(*this).size()); storage_->values[offset] = std::move(value); }
bool_t @n@::remove(@count@ index) {
    if (index < 0 || !storage_ || static_cast<unsigned long long>(index) >= storage_->values.size()) return false;
    storage_->values.erase(storage_->values.begin() + index);
    return true;
}
bool_t @n@::has(@count@ index) const noexcept {
    return index >= 0 && storage_ && static_cast<unsigned long long>(index) < storage_->values.size();
}
