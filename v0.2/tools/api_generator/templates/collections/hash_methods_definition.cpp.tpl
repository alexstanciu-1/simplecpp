@count@ @n@::size() const { return @count@{detail::checked_integer_cast<long long>(storage_ ? storage_->table.size() : 0)}; }
bool_t @n@::empty() const noexcept { return bool_t{!storage_ || storage_->table.size() == 0}; }
bool_t @n@::has(const @k@& key) const { return bool_t{storage_ && storage_->table.find(key)}; }
@v@ @n@::at(const @k@& key) const { const auto* value = storage_ ? storage_->table.find(key) : nullptr; if (!value) throw std::out_of_range("hash_missing_key"); return *value; }
@nullable@ @n@::find(const @k@& key) const { const auto* value = storage_ ? storage_->table.find(key) : nullptr; return value ? @nullable@{*value} : @nullable@{}; }
void @n@::set(@k@ key, @v@ value) { if (!storage_) storage_ = new storage; storage_->table.set(std::move(key), std::move(value)); }
bool_t @n@::remove(const @k@& key) { return bool_t{storage_ && storage_->table.remove(key)}; }
void @n@::clear() noexcept { delete storage_; storage_ = nullptr; }
