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

using App::scpp_records::Packet;
static int vector_copies;
extern "C" void __real__ZN4scpp12vector_int64C1ERKS0_(void*, const void*);
extern "C" void __wrap__ZN4scpp12vector_int64C1ERKS0_(void* target, const void* source) {
    ++vector_copies;
    __real__ZN4scpp12vector_int64C1ERKS0_(target, source);
}
extern "C" void* __real__ZN4scpp12vector_int64aSERKS0_(void*, const void*);
extern "C" void* __wrap__ZN4scpp12vector_int64aSERKS0_(void* target, const void* source) {
    ++vector_copies;
    return __real__ZN4scpp12vector_int64aSERKS0_(target, source);
}

Packet read_packet(std::string text) {
    json::pull_reader source(std::move(text), "packet", 16);
    auto integer = []<class Value>(auto& reader) {
        static_assert(std::is_same_v<Value, std::int64_t>);
        return reader.read_integer();
    };
    json::record_input input{source, integer};
    auto result = structure::read<Packet>(input, Packet::scpp_escape_from_data);
    source.finish_document();
    return result;
}

std::string write_packet(const Packet& packet) {
    std::string out;
    json::write_record(packet, [&](std::string_view part) { out.append(part); }, encode_integer);
    return out;
}

void check_packet() {
    auto packet = read_packet(R"({"data":[0]})");
    packet = read_packet(R"({"version":9,"data":[3,-4,8]})");
    assert(packet.version == 9 && packet.data.size() == 3);
    assert(write_packet(packet) == R"({"data":[3,-4,8],"version":9})");
    assert(write_packet(read_packet(R"({"data":[]})")) == R"({"data":[],"version":1})");
    try {
        packet = read_packet(R"({"data":[1,false,3]})");
        assert(false);
    } catch (const json::read_error& error) {
        assert(error.path == R"($["data"][1])");
    }
    assert(write_packet(packet) == R"({"data":[3,-4,8],"version":9})");
    assert(vector_copies == 0);
    auto copy = packet;
    copy.data = packet.data;
    assert(vector_copies == 2);

    using App::scpp_records::Flags;
    json::pull_reader source(R"({"data":[true,false]})", "flags", 16);
    auto boolean = []<class Value>(auto& reader) {
        static_assert(std::is_same_v<Value, bool>);
        return reader.read_boolean();
    };
    json::record_input input{source, boolean};
    auto flags = structure::read<Flags>(input, Flags::scpp_escape_from_data);
    source.finish_document();
    std::string output;
    json::write_record(flags, [&](std::string_view part) { output += part; },
                       [](bool value, auto& sink) { sink(value ? "true" : "false"); });
    assert(output == R"({"data":[true,false]})");
}

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

void check_duplicate(std::string input, std::string_view expected_path) {
    auto offset = input.rfind(',') + 1;
    json::pull_reader reader(std::move(input), "test", 16);
    reader.begin_object();
    try {
        while (reader.next_property()) reader.read_integer();
        assert(false);
    } catch (const json::read_error& error) {
        assert(error.category == "duplicate_key");
        assert(error.position == offset && error.path == expected_path);
    }
}

void check_small_objects() {
    std::string source = R"([{"y":1,"x":2},{"x":3,"y":4}])";
    json::pull_reader reader(std::move(source), "test", 16);
    reader.begin_array();
    assert(reader.next_element());
    assert(generated(reader).x == 2);
    auto before = allocations;
    assert(reader.next_element());
    assert(generated(reader).x == 3);
    assert(allocations == before);
    assert(!reader.next_element());
    reader.finish_document();
}

// Compare committed elements and the full diagnostic against ordinary scalar traversal.
void check_integer_arrays() {
    auto decode = [](const std::string& text, bool batched) {
        std::pair<std::vector<std::int64_t>, std::string> result;
        try {
            json::pull_reader reader(text, "fixture", 128);
            reader.begin_object();
            assert(reader.next_property());
            if (batched) reader.read_integers([&](auto value) { result.first.push_back(value); });
            else {
                reader.begin_array();
                while (reader.next_element()) result.first.push_back(reader.read_integer());
            }
            assert(!reader.next_property());
            reader.finish_document();
        } catch (const json::read_error& error) {
            result.second = error.what();
        }
        return result;
    };
    for (const auto prefix : {"", "-9223372036854775808, 0,9223372036854775807,"}) {
        for (const auto suffix : {"", "-0", "-00", "01", "-", "+1", "1.", "2e3", "1e-", "false",
                                  "null", "{}", "[]", "9223372036854775808", "1 2", "3,", "2x"}) {
            std::string doc = std::string{"{\"data\":["} + prefix + suffix + "]}";
            assert(decode(doc, false) == decode(doc, true));
        }
    }
}
int main(int argc, char** argv) {
    check_integer_arrays();
    check_packet();
    check_small_objects();
    check_duplicate(R"({"a":1,"b":2,"c":3,"d":4,"\u0061":5})", R"($["\u0061"])");
    check_duplicate(R"({"a":1,"b":2,"\u0063":3,"d":4,"c":5})", R"($["c"])");
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
    const int repeats = argc == 2 && std::string_view(argv[1]) == "--quick" ? 1 : 64;
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
            for (int repeat = 0; repeat < repeats; ++repeat) assert(transcode_fn(source) == expected);
            auto count = allocations - before;
            double seconds = std::chrono::duration<double>(std::chrono::steady_clock::now() - start).count();
            std::cout << " " << count / repeats << " alloc/doc " << source.size() * repeats / seconds / 1e6 << " MB/s";
            return count;
        };
        std::cout << records << " records, handwritten then generated:";
        auto baseline = sample(transcode<false>);
        auto measured = sample(transcode<true>);
        assert(measured == baseline);
        std::cout << '\n';
    }
}
