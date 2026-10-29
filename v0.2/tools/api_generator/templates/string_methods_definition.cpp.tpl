@n@::@n@(const char* bytes) : storage_(new storage{detail::string_from_c_string(bytes)}) {}
@n@::@n@(const char* bytes, @count@ count) : storage_(new storage{detail::string_from_bytes(bytes, count.native_value())}) {}
@count@ @n@::size() const { return @count@{detail::checked_integer_cast<long long>(detail::string_access::read(*this).size())}; }
bool_t @n@::empty() const noexcept { return bool_t{detail::string_access::read(*this).empty()}; }
const char* @n@::data() const noexcept { return detail::string_access::read(*this).data(); }
void @n@::append(const @n@& other) { if (!storage_) { @n@ copy(other); std::swap(storage_, copy.storage_); } else { storage_->bytes.append(detail::string_access::read(other)); } }
void @n@::clear() noexcept { delete storage_; storage_ = nullptr; }
