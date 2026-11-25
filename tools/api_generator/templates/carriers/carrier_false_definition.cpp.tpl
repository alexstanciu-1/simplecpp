@n@ @n@::from_false() noexcept { return {}; }
bool_t @n@::is_false() const noexcept { return bool_t{!storage_ || storage_->content.is_false()}; }
