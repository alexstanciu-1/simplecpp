bool_t @n@::same_error_identity(const @peer@& other) const {
    return bool_t{has_error() && other.has_error() && storage_->content.error().same_identity(other.storage_->content.error())};
}
