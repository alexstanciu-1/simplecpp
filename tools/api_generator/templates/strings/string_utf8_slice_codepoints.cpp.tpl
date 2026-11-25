    const detail::utf8::sequence input({arg0.data(), static_cast<std::size_t>(arg0.size())});
    const auto range = input.slice(arg1, arg2);
    return string{range.data(), static_cast<int64>(range.size())};
