@inline@@e@* @n@::mt_data() noexcept { return storage_ ? reinterpret_cast<@e@*>(storage_->data) : nullptr; }
@inline@const @e@* @n@::mt_data() const noexcept { return storage_ ? reinterpret_cast<const @e@*>(storage_->data) : nullptr; }
