@n@::@n@(const @src@& source) { @n@ converted; for (const auto& item : detail::vector_access::read(source)) converted.append(static_cast<@e@>(item)); std::swap(storage_, converted.storage_); }
