@n@::@n@(const @src@& source) {
    @n@ converted;
    const auto& values = @access@::read(source);
    for (std::size_t i = 0; i < values.size(); ++i) {
        const auto& item = values[i];
        converted.append(@conversion@);
    }
    storage_ = converted.storage_;
    converted.storage_ = nullptr;
}
