@n@::~@n@() { delete storage_; }
@n@::@n@(const @n@& other) : storage_(other.storage_ ? new storage(*other.storage_) : nullptr) {}
@n@::@n@(@n@&& other) noexcept : storage_(other.storage_) { other.storage_ = nullptr; }
@n@& @n@::operator=(const @n@& other) { if (this != &other) { @n@ copy(other); auto* old = storage_; storage_ = copy.storage_; copy.storage_ = old; } return *this; }
@n@& @n@::operator=(@n@&& other) noexcept { if (this != &other) { delete storage_; storage_ = other.storage_; other.storage_ = nullptr; } return *this; }
