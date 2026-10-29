bool_t @n@::has_value() const noexcept { return bool_t{static_cast<bool>(@this@)}; }
@n@::operator bool() const noexcept { return static_cast<bool>(@this@); }
@t@& @n@::operator*() const { return detail::ownership_access::checked(@this@); }
@t@* @n@::operator->() const { return std::addressof(detail::ownership_access::checked(@this@)); }
