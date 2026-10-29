@count@ @n@::size() const { return @count@{detail::checked_integer_cast<long long>(detail::vector_access::read(*this).size())}; }
bool_t @n@::empty() const noexcept { return bool_t{detail::vector_access::read(*this).empty()}; }
void @n@::clear() noexcept { delete storage_; storage_ = nullptr; }
void @n@::append(@e@ value) { if (!storage_) storage_ = new storage; storage_->values.push_back(std::move(value)); }
@e@ @n@::at(@count@ index) const { const auto& values = detail::vector_access::read(*this); return values[detail::sequence_index(index.native_value(), values.size())]; }
void @n@::set(@count@ index, @e@ value) { const auto offset = detail::sequence_index(index.native_value(), detail::vector_access::read(*this).size()); storage_->values[offset] = std::move(value); }
