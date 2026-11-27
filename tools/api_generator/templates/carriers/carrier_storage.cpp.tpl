struct @n@::storage {
    using content_type = detail::carrier_storage<@p@@error_type@>;
    content_type content;
    std::atomic<unsigned> owners{1};
    void release() noexcept { if (owners.fetch_sub(1, std::memory_order_acq_rel) == 1) delete this; }
};
@n@::~@n@() { if (storage_) storage_->release(); }
@n@::@n@(const @n@& other) : storage_(other.storage_ ? new storage{other.storage_->content} : nullptr) {}
@n@::@n@(@n@&& other) noexcept : storage_(std::exchange(other.storage_, nullptr)) {}
@n@& @n@::operator=(const @n@& other) { if (this != &other) { @n@ copy(other); std::swap(storage_, copy.storage_); } return *this; }
@n@& @n@::operator=(@n@&& other) noexcept { if (this != &other) { if (storage_) storage_->release(); storage_ = std::exchange(other.storage_, nullptr); } return *this; }
@n@::borrow_guard::borrow_guard(storage* slot, const @p@* payload) noexcept : slot_(slot), payload_(payload) { if (slot_) slot_->owners.fetch_add(1, std::memory_order_relaxed); }
@n@::borrow_guard::~borrow_guard() { if (slot_) slot_->release(); }
@n@::writable_borrow @n@::pin() { auto& payload = checked_ref(); return writable_borrow(storage_, &payload); }
@n@::borrow_guard @n@::pin() const { return borrow_guard(storage_, &checked_ref()); }
