int64 @n@::@limit_method@() const { return @self@.@limit_method@(); }
bool_t @n@::@next_method@(int64& position, int64 limit, @n@& value) const {
    return @self@.@next_method@(position, limit, @value@);
}
bool_t @n@::@next_method@(int64& position, int64 limit, int64& key_index, string& key_text, @n@& value) const {
    auto next = position;
    std::uint32_t code;
    @native@ selected;
    if (!@self@.@next_method@(next, limit, selected, &code)) { position = next; return false; }
    string text;
    const bool is_text = detail::packed_key::is_string(code);
    if (is_text) {
        const auto bytes = detail::packed_string_pool::instance().resolve(code);
        text = string{bytes.data(), detail::checked_integer_cast<@count@>(bytes.size())};
    }
    key_index = is_text ? -1 : static_cast<int64>(code);
    key_text = std::move(text);
    position = next;
    @value@ = std::move(selected);
    return true;
}
