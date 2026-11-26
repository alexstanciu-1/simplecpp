namespace scpp {
@name@::~@name@() { if (state_ && state_->references.fetch_sub(1, std::memory_order_acq_rel) == 1) delete state_; }
@name@::@name@(const @name@& other) noexcept : state_(other.state_) {
    if (state_) state_->references.fetch_add(1, std::memory_order_relaxed);
}
@name@::@name@(@name@&& other) noexcept : state_(std::exchange(other.state_, nullptr)) {}
@name@& @name@::operator=(const @name@& other) noexcept {
    @name@ copy(other); std::swap(state_, copy.state_); return *this;
}
@name@& @name@::operator=(@name@&& other) noexcept {
    @name@ moved(std::move(other)); std::swap(state_, moved.state_); return *this;
}
}
