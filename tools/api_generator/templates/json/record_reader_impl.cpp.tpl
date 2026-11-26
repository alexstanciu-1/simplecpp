class record_reader final : public Decoder {
    detail::json::pull_reader& cursor;
    static string copy_string(std::string_view bytes) {
        return string{bytes.data(), detail::checked_integer_cast<@count@>(bytes.size())};
    }
    static string take_string(std::string&& bytes) {
        if (bytes.size() <= string::inline_capacity) return copy_string(bytes);
        return detail::own_string(std::move(bytes));
    }
public:
    explicit record_reader(detail::json::pull_reader& source) : cursor(source) {}
    void begin_object() override { cursor.begin_object(); }
    bool next_field() override {
        if (cursor.next_property()) return true;
        if (cursor.at_root()) cursor.finish_document();
        return false;
    }
    int next_record_field(field_lookup lookup, std::span<std::uint64_t> seen) override {
        int field = unknown_field;
        const bool more = cursor.next_record_property([&](std::string_view key) {
            field = lookup(key);
            if (field == unknown_field) return true; // The generated decoder rejects it after the colon.
            const auto word = static_cast<std::size_t>(field) / 64;
            if (word >= seen.size()) throw std::logic_error("record field outside duplicate mask");
            const auto bit = std::uint64_t{1} << (field % 64);
            const bool fresh = (seen[word] & bit) == 0;
            seen[word] |= bit;
            return fresh;
        });
        if (more) return field;
        if (cursor.at_root()) cursor.finish_document();
        return end_fields;
    }
    std::string_view field_name() override { return cursor.property_view(); }
    void begin_array() override { cursor.begin_array(); }
    bool next_element() override { return cursor.next_element(); }
    void reject(const char* message) override { cursor.reject(message); }
@definitions@};
error_t read_record(const string& text, void* out, void (*consume)(void*, Decoder&)) {
    try {
        detail::json::pull_reader cursor{std::string(text.data(), text.size()), "", 128};
        record_reader reader{cursor};
        consume(out, reader);
        return {};
    } catch (const detail::json::read_error& error) {
        const std::string_view message{error.what()};
        return error_t{string{message.data(), detail::checked_integer_cast<@count@>(message.size())}};
    }
}
