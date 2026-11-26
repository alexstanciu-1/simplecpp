// Protocol for embedded field-derived records containing scalar fields and flat scalar lists.
template<class Box>
class boxed_record_writer final : public Encoder {
    enum class phase { start, field, value, list, done };
    phase state = phase::start;
    struct frame { Box object; string key; };
    frame current;
    std::vector<frame> parents;
    Box list;
    void require(phase expected) const {
        if (state != expected) throw std::logic_error("invalid boxed record traversal");
    }
    void put(Box value) {
        if (state == phase::list) list.append(std::move(value));
        else {
            require(phase::value);
            current.object.set(current.key, std::move(value));
            state = phase::field;
        }
    }
public:
    void begin_object() override {
        if (state == phase::value) parents.push_back(std::move(current));
        else require(phase::start);
        current = {Box::container(), {}};
        state = phase::field;
    }
    void field(std::string_view name) override {
        require(phase::field);
        current.key = string{name.data(), detail::checked_integer_cast<int64>(name.size())};
        state = phase::value;
    }
    void end_object() override {
        require(phase::field);
        if (parents.empty()) { state = phase::done; return; }
        Box child = std::move(current.object);
        current = std::move(parents.back());
        parents.pop_back();
        state = phase::value;
        put(std::move(child));
    }
    void begin_array() override { require(phase::value); list = Box::container(); state = phase::list; }
    void end_array() override { require(phase::list); state = phase::value; put(std::move(list)); }
    void write_null() override { put(Box{}); }
@methods@    Box finish() { require(phase::done); return std::move(current.object); }
};
