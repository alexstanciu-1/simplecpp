@n@::@n@(exception error) : storage_(new storage{storage::content_type::failure(std::move(error))}) {}
bool_t @n@::has_error() const noexcept { return bool_t{storage_ && storage_->content.has_error()}; }
exception @n@::error() const { if (!storage_) throw std::runtime_error("result_no_error"); return storage_->content.error(); }
