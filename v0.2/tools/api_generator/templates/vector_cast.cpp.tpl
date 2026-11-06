@n@::@n@(const @src@& source) { @n@ converted; for (const auto& item : detail::vector_access::read(source)) converted.append(@conversion@); std::swap(storage_, converted.storage_); }
