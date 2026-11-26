// Feasibility probe for the existing native Encoder protocol. Compile with assertions enabled.
#include <scpp/encoder.hpp>
#include <scpp/vectors.hpp>
#include <cassert>
#include <cstdlib>
#include <utility>

namespace {
using namespace scpp;

// Only a single flat list is admitted by this probe.
template<class Box>
class ListSink final : public Encoder {
    Box list;
    bool open = false;
public:
    void begin_array() override {
        assert(!open);
        list = Box::container();
        open = true;
    }
    void write(int64 value) override {
        assert(open);
        list.append(Box::from_integer(value));
    }
    void write(const string& value) override {
        assert(open);
        list.append(Box::from_string(value));
    }
    void end_array() override { assert(open); open = false; }
    Box take() { assert(!open); return std::move(list); }

    void begin_object() override { std::abort(); }
    void end_object() override { std::abort(); }
    void field(std::string_view) override { std::abort(); }
    void write_null() override { std::abort(); }
    void write(bool_t) override { std::abort(); }
    void write(float64) override { std::abort(); }
    void write(const mixed&) override { std::abort(); }
    void write(const dynamic&) override { std::abort(); }
    void write(const nullable_int64&) override { std::abort(); }
    void write(const nullable_float64&) override { std::abort(); }
    void write(const nullable_bool_t&) override { std::abort(); }
    void write(const nullable_string&) override { std::abort(); }
};

template<class Vector>
void visit(const Vector& source, Encoder& out) {
    out.begin_array();
    for (int64 i = 0, count = source.size(); i < count; ++i) out.write(source.at(i));
    out.end_array();
}

template<class Box>
void check() {
    const int64 first = 0, second = 1;
    Box payload = Box::container();
    const string data{"data"}, words{"words"};
    const string long_word{"a string longer than the inline storage capacity"};
    {
        vector_int64 numbers;
        vector_string strings;
        ListSink<Box> sink;
        visit(numbers, sink);
        auto empty = sink.take();
        assert(empty.is_packed_container() && empty.container_size() == 0);
        numbers.append(2);
        numbers.append(7);
        strings.append(long_word);
        strings.append(string{"last"});
        visit(numbers, sink);
        payload.set(data, sink.take());
        visit(strings, sink);
        payload.set(words, sink.take());
        numbers.set(first, 99);
        strings.set(first, string{"changed"});
        assert(numbers.at(first) == 99 && strings.at(first) == string{"changed"});
        numbers.clear();
        strings.clear();
    }
    auto numbers = payload.read(data);
    auto strings = payload.read(words);
    assert(numbers.is_packed_container() && numbers.container_size() == 2);
    assert(strings.is_packed_container() && strings.container_size() == 2);
    assert(numbers.read(first).get_integer() == 2 && numbers.read(second).get_integer() == 7);
    assert(strings.read(first).get_string() == long_word && strings.read(second).get_string() == string{"last"});
}
}

int main() {
    check<scpp::mixed>();
    check<scpp::dynamic>();
}
