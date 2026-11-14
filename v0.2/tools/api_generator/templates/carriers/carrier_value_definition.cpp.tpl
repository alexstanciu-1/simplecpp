@n@::@n@(@p@ value) : storage_(new storage{storage::content_type{std::move(value)}}) {}
bool_t @n@::has_value() const noexcept { return bool_t{storage_ ? storage_->content.has_value() : @result@}; }
@p@ @n@::value() const {