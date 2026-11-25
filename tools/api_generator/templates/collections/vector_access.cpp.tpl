    static const @n@::storage& read(const @n@& value) noexcept { static const @n@::storage empty; return value.storage_ ? *value.storage_ : empty; }
