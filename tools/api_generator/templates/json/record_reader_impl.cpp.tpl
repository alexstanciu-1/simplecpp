class record_reader final : public Decoder {
    detail::json::pull_reader& cursor;
public:
    explicit record_reader(detail::json::pull_reader& source) : cursor(source) {}
    void begin_object() override { cursor.begin_object(); }
    bool next_field() override {
        if (cursor.next_property()) return true;
        if (cursor.at_root()) cursor.finish_document();
        return false;
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
