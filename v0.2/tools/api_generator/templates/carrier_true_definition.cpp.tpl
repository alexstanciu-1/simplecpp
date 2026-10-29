@n@ @n@::from_true() { @n@ out; out.storage_ = new storage{storage::content_type::sentinel_true()}; return out; }
bool_t @n@::is_true() const noexcept { return bool_t{storage_ && storage_->content.is_true()}; }
