@n@::@n@(error_t error) : storage_(new storage{storage::content_type::failure(std::move(error))}) {}
bool_t @n@::has_error() const noexcept { return bool_t{storage_ && storage_->content.has_error()}; }
error_t @n@::error() const { if (!storage_) throw std::runtime_error("result_no_error"); return storage_->content.error(); }
bool_t @n@::same_error_identity(const @n@& other) const {
    return bool_t{has_error() && other.has_error() && storage_->content.error().same_identity(other.storage_->content.error())};
}
