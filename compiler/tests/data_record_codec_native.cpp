#include "json/record_adapter.hpp"
#include <cassert>
#include <chrono>
#include <cstdlib>
#include <iostream>
#include <new>

using App::scpp_records::Point;
namespace json = scpp::detail::json;
namespace structure = scpp::structure;
static std::size_t allocations;

void* operator new(std::size_t size) {
    ++allocations;
    if (auto p = std::malloc(size ? size : 1)) return p;
    throw std::bad_alloc();
}
void operator delete(void* p) noexcept { std::free(p); }
void operator delete(void* p, std::size_t) noexcept { std::free(p); }

Point generated(json::pull_reader& parser) {
    auto integer = []<class Value>(auto& source) {
        static_assert(std::is_same_v<Value, std::int64_t>);
        return source.read_integer();
    };
    json::record_input input{parser, integer};
    return structure::read<Point>(input, Point::scpp_escape_from_data);
}

Point handwritten(json::pull_reader& parser) {
    std::optional<std::int64_t> x;
    std::int64_t y = 7;
    parser.begin_object();
    while (parser.next_property()) {
        auto key = parser.property_view();
        if (key == "x") x = parser.read_integer();
        else if (key == "y") y = parser.read_integer();
        else parser.reject("unknown field");
    }
    if (!x) parser.reject("missing required field");
    return Point::scpp_escape_from_data(*x, y);
}

auto encode_integer = [](std::int64_t value, auto& sink) {
    char buffer[32];
    auto end = std::to_chars(buffer, buffer + sizeof buffer, value).ptr;
    sink(std::string_view(buffer, end - buffer));
};

template<bool adapter>
std::string transcode(const std::string& source) {
    json::pull_reader reader(source, "test", 16);
    std::string out = "[";
    auto sink = [&](std::string_view bytes) { out.append(bytes); };
    reader.begin_array();
    while (reader.next_element()) {
        auto p = adapter ? generated(reader) : handwritten(reader);
        if (out.size() > 1) sink(",");
        if constexpr (adapter) json::write_record(p, sink, encode_integer);
        else {
            sink("{\"x\":");
            encode_integer(p.x, sink);
            sink(",\"y\":");
            encode_integer(p.y, sink);
            sink("}");
        }
    }
    reader.finish_document();
    out += ']';
    return out;
}

int main() {
    const auto original = Point::scpp_escape_from_data(2, 7);
    int visited = 0;
    structure::visit(original, [&](std::string_view name, const auto& member) {
        assert(name == (visited == 0 ? "x" : "y"));
        assert(&member == (visited == 0 ? &original.x : &original.y));
        ++visited;
    });
    assert(visited == 2);
    assert(transcode<true>("[{\"x\":3}]") == "[{\"x\":3,\"y\":7}]");
    for (auto input : {"[{}]", "[{\"z\":2}]", "[{\"x\":2,\"x\":3}]", "[{\"x\":false}]", "[]!"}) {
        try { transcode<true>(input); assert(false); }
        catch (const json::read_error&) {}
    }
    for (int records : {1, 128}) {
        std::string source = "[";
        for (int i = 0; i < records; ++i) {
            if (i) source += ',';
            source += "{\"y\":12,\"x\":3}";
        }
        source += ']';
        auto expected = transcode<false>(source);
        // Compare identical parser, output transport, key order and result checks.
        auto sample = [&](auto transcode_fn) {
            auto start = std::chrono::steady_clock::now();
            auto before = allocations;
            for (int repeat = 0; repeat < 64; ++repeat) assert(transcode_fn(source) == expected);
            auto count = allocations - before;
            double seconds = std::chrono::duration<double>(std::chrono::steady_clock::now() - start).count();
            std::cout << " " << count / 64 << " alloc/doc " << source.size() * 64 / seconds / 1e6 << " MB/s";
            return count;
        };
        std::cout << records << " records, handwritten then generated:";
        auto baseline = sample(transcode<false>);
        auto measured = sample(transcode<true>);
        assert(measured == baseline);
        std::cout << '\n';
    }
}
