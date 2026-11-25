    if (arg2 < 0 || arg2 > arg0.size()) throw ValueError("string_byte_find offset outside subject");
    const auto pos = std::string_view(arg0.data(), arg0.size()).find(
        std::string_view(arg1.data(), arg1.size()), static_cast<std::size_t>(arg2));
    if (pos == std::string_view::npos) return result_or_false_int64::from_false();
    return result_or_false_int64{static_cast<int64>(pos)};
