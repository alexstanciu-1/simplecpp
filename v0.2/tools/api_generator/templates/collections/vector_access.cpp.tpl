    static const std::vector<@e@>& read(const @n@& value) noexcept { static const std::vector<@e@> empty; return value.storage_ ? value.storage_->values : empty; }
