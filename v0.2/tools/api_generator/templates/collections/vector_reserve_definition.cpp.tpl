@inline@void @n@::reserve(@count@ count) {
    if (count < 0) throw ::scpp::ValueError("negative_vector_capacity");
    if (static_cast<unsigned long long>(count) > @buffer@::vector_capacity_limit(sizeof(@e@)))
        throw std::length_error("vector_capacity_out_of_range");
    if (count == 0) return;
    if (storage_) { storage_->reserve(static_cast<std::size_t>(count)); return; }
    auto* fresh = new storage;
    try { fresh->reserve(static_cast<std::size_t>(count)); }
    catch (...) { delete fresh; throw; }
    storage_ = fresh;
}
