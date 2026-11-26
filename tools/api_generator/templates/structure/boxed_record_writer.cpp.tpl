// Protocol for a field-derived record containing scalar fields and flat scalar lists.
template<class Box>
class boxed_record_writer final : public Encoder {
    enum class phase { start, field, value, list, done };
    phase state = phase::start;
    Box root;
    Box list;
    string key;
    void require(phase expected) const {
        if (state != expected) throw std::logic_error("invalid boxed record traversal");
    }
    void put(Box value) {
        if (state == phase::list) list.append(std::move(value));
        else {
            require(phase::value);
            root.set(key, std::move(value));
            state = phase::field;
        }
    }
public:
    void begin_object() override { require(phase::start); root = Box::container(); state = phase::field; }
    void field(std::string_view name) override {
        require(phase::field);
        key = string{name.data(), detail::checked_integer_cast<int64>(name.size())};
        state = phase::value;
    }
    void end_object() override { require(phase::field); state = phase::done; }
    void begin_array() override { require(phase::value); list = Box::container(); state = phase::list; }
    void end_array() override { require(phase::list); state = phase::value; put(std::move(list)); }
    void write_null() override { put(Box{}); }
@methods@    Box finish() { require(phase::done); return std::move(root); }
};
