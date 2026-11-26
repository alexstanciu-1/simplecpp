// Keep a record and its current list inline. Descending into an object suspends both.
template<class Box>
class boxed_record_writer final : public Encoder {
    enum class phase { start, field, value, list, root_list, done };
    struct frame { Box object; string key; Box list; phase state = phase::start; };
    frame current;
    std::vector<frame> parents;
    void require(phase expected) const {
        if (current.state != expected) throw std::logic_error("invalid boxed record traversal");
    }
    bool writing_list() const { return current.state == phase::list || current.state == phase::root_list; }
    void put(Box value) {
        if (writing_list()) current.list.append(std::move(value));
        else {
            require(phase::value);
            current.object.set(current.key, std::move(value));
            current.state = phase::field;
        }
    }
public:
    bool stable_record_elements() const noexcept override { return true; }
    void begin_object() override {
        if (current.state == phase::value || writing_list()) parents.push_back(std::move(current));
        else require(phase::start);
        current = {Box::container(), {}, {}, phase::field};
    }
    void field(std::string_view name) override {
        require(phase::field);
        current.key = string{name.data(), detail::checked_integer_cast<int64>(name.size())};
        current.state = phase::value;
    }
    void end_object() override {
        require(phase::field);
        if (parents.empty()) { current.state = phase::done; return; }
        Box child = std::move(current.object);
        current = std::move(parents.back());
        parents.pop_back();
        put(std::move(child));
    }
    void begin_array() override {
        const bool root = current.state == phase::start;
        if (!root) require(phase::value);
        current.list = Box::container();
        current.state = root ? phase::root_list : phase::list;
    }
    void end_array() override {
        if (current.state == phase::root_list) {
            current.object = std::move(current.list);
            current.state = phase::done;
        } else {
            require(phase::list);
            current.state = phase::value;
            put(std::move(current.list));
        }
    }
    void write_null() override { put(Box{}); }
@methods@    Box finish() { require(phase::done); return std::move(current.object); }
};
