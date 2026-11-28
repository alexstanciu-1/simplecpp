    const auto hex = detail::sha256({arg0.data(), static_cast<std::size_t>(arg0.size())});
    return string{hex.data(), 64};
