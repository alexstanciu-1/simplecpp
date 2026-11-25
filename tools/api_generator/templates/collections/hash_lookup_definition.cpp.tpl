@nullable@ @n@::find(const @k@& key) const { const auto* value = storage_ ? storage_->find(key) : nullptr; return value ? @nullable@{*value} : @nullable@{}; }
