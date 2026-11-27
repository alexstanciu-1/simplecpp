// Frames retain their boxed container. Only the selected scalar or child is materialized.
template<class Box>
class boxed_record_reader final : public Decoder {
    struct frame {
        Box container;
        bool array;
        int64 limit;
        int64 position = 0;
        int64 ordinal = -1;
        string key;
    };
    std::vector<frame> frames;
    Box selected;
    bool pending = true;

    void begin(bool array) {
        if (!pending || selected.kind() != boxed_kind::container) reject(array ? "expected array" : "expected object");
        if (frames.size() >= 128) reject("nesting limit exceeded");
        auto limit = selected.iteration_limit();
        frames.push_back({std::move(selected), array, limit, 0, -1, {}});
        pending = false;
    }
    bool advance(bool array) {
        if (pending || frames.empty() || frames.back().array != array) reject("invalid traversal");
        auto& f = frames.back();
        int64 index;
        if (!f.container.iteration_next(f.position, f.limit, index, f.key, selected)) {
            frames.pop_back();
            return false;
        }
        ++f.ordinal;
        if (array && index != f.ordinal) reject("expected consecutive list index");
        if (!array && index >= 0) reject("expected field name");
        pending = true;
        return true;
    }
    void take(boxed_kind kind, const char* message) {
        if (!pending || selected.kind() != kind) reject(message);
        pending = false;
    }
public:
    explicit boxed_record_reader(const Box& root) : selected(root) {}
    void begin_object() override { begin(false); }
    void begin_array() override { begin(true); }
    bool next_field() override { return advance(false); }
    bool next_element() override { return advance(true); }
    std::string_view field_name() override {
        if (!pending || frames.empty() || frames.back().array) reject("expected field name");
        const auto& key = frames.back().key;
        return {key.data(), static_cast<std::size_t>(key.size())};
    }
@methods@    void finish() {
        if (pending || !frames.empty()) reject("incomplete traversal");
    }
    [[noreturn]] void reject(const char* message) override {
        std::string path = "$";
        for (const auto& f : frames) {
            if (f.ordinal < 0) continue;
            if (f.array) path += "[" + std::to_string(f.ordinal) + "]";
            else { path += "."; path.append(f.key.data(), static_cast<std::size_t>(f.key.size())); }
        }
        throw std::invalid_argument(path + ": " + message);
    }
};
