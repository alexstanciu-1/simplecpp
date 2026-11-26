class record_writer final : public Encoder {
    detail::json::writer& output;
    record_sink sink;
    bool comma = false;
    void next_value() {
        if (comma) output.append(",");
        comma = true;
    }
public:
    explicit record_writer(detail::json::writer& writer) : output(writer), sink{writer} {}
    void begin_object() override { next_value(); output.append("{"); comma = false; }
    void end_object() override { output.append("}"); comma = true; }
    void begin_array() override { next_value(); output.append("["); comma = false; }
    void end_array() override { output.append("]"); comma = true; }
    void field(std::string_view name) override {
        output.append(comma ? ",\"" : "\"");
        output.append(name);
        output.append("\":");
        comma = false;
    }
    void write_null() override { next_value(); output.write(nullptr); }
@methods@};
result_string encode_fields(const void* value, Encoder::visit emit) {
    struct source { const void* value; Encoder::visit emit; } input{value, emit};
    try {
        return encode_record_erased(&input, [](const void* raw, detail::json::writer& output) {
            const auto& input = *static_cast<const source*>(raw);
            record_writer writer{output};
            if (input.value) input.emit(input.value, writer);
            else writer.write_null();
        });
    } catch (const encoder_cycle&) {
        return result_string{error_t{string{"json_encode: circular record reference"}}};
    }
}
