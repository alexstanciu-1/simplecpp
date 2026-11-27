// The generated Packet header is supplied by the test runner.
#include SCPP_PACKET_HEADER
#include <scpp/json/encode_fields.hpp>
#include <scpp/json/decode_record.hpp>
#include <cstdlib>
#include <string>
#include <string_view>

namespace App {
using Result = record_result_Packet;
Result readPacket_(scpp::string);
scpp_records::Packet snapshot_(Result&);
scpp::result_string encodeLate_();
int scpp_entry_body();
}

static unsigned copies;
extern "C" void __real__ZN4scpp12vector_int64C1ERKS0_(void*, const void*);
extern "C" void* __real__ZN4scpp12vector_int64aSERKS0_(void*, const void*);
extern "C" void __wrap__ZN4scpp12vector_int64C1ERKS0_(void* dest, const void* src) {
    ++copies;
    __real__ZN4scpp12vector_int64C1ERKS0_(dest, src);
}
extern "C" void* __wrap__ZN4scpp12vector_int64aSERKS0_(void* dest, const void* src) {
    ++copies;
    return __real__ZN4scpp12vector_int64aSERKS0_(dest, src);
}

// A second provider proves that the generated method only uses the Decoder contract.
class VersionReader final : public scpp::Decoder {
    int field = 0;
    int element = 0;
public:
    void begin_object() override {}
    bool next_field() override { return ++field <= 2; }
    std::string_view field_name() override { return field == 1 ? "version" : "data"; }
    scpp::int64 read_integer() override { return field == 1 ? 6 : element; }
    void begin_array() override { element = 0; }
    bool next_element() override { return ++element <= 2; }
    bool read_boolean() override { reject("bool"); }
    scpp::string read_string() override { return scpp::string(element == 1 ? "first" : "second"); }
    [[noreturn]] void reject(const char* why) override { throw std::runtime_error(why); }
};

// Capture the generated traversal independently of JSON punctuation and escaping.
class PacketEvents final : public scpp::Encoder {
public:
    std::string events;
    void begin_object() override { events += "object "; }
    void end_object() override { events += "end "; }
    void field(std::string_view name) override { events += name; events += ' '; }
    void begin_array() override { events += "array "; }
    void end_array() override { events += "end "; }
    void write(scpp::int64 value) override { events += std::to_string(value) + ' '; }
    void write_null() override { std::abort(); }
    void write(scpp::bool_t) override { std::abort(); }
    void write(const scpp::dynamic&) override { std::abort(); }
    void write(scpp::float64) override { std::abort(); }
    void write(const scpp::mixed&) override { std::abort(); }
    void write(const scpp::nullable_bool_t&) override { std::abort(); }
    void write(const scpp::nullable_float64&) override { std::abort(); }
    void write(const scpp::nullable_int64&) override { std::abort(); }
    void write(const scpp::nullable_string&) override { std::abort(); }
    void write(const scpp::string&) override { std::abort(); }
};

// Count allocations only from the last emitted value until encode_fields returns.
static bool counting;
static std::size_t allocations;
extern "C" void* __real__Znwm(std::size_t);
extern "C" void* __wrap__Znwm(std::size_t size) {
    if (counting) ++allocations;
    return __real__Znwm(size);
}

static bool check_publication(std::size_t bytes) {
    std::string expected(bytes - 2, 'x');
    scpp::string input(expected.data(), static_cast<scpp::int64>(expected.size()));
    expected = '"' + expected + '"';
    allocations = 0;
    auto encoded = scpp::json::encode_fields(&input, [](const void* value, scpp::Encoder& out) {
        out.write(*static_cast<const scpp::string*>(value));
        counting = true;
    });
    counting = false;
    const bool small = bytes <= scpp::string::inline_capacity;
    // One result carrier, plus the runtime string storage object for long output.
    if (allocations != (small ? 1u : 2u) || !encoded.has_value()) return false;
    auto value = encoded.value();
    return value.is_inline() == small && std::string_view(value.data(), value.size()) == expected;
}

int main() {
    if (const auto status = App::scpp_entry_body()) return status;
    for (auto bytes : {21u, 22u, 23u, 65536u})
        if (!check_publication(bytes)) return 47;
    scpp::string input(R"({"version":9,"data":[1,2,3,4]})");
    auto snapshot_error = scpp::json::read_record(input, &input, [](void* alias, scpp::Decoder& decoder) {
        static_cast<scpp::string*>(alias)->clear();
        auto packet = App::scpp_records::Packet::decode(decoder);
        if (packet.version != 9 || packet.data.size() != 4 || packet.data.at(3) != 4) std::abort();
    });
    if (snapshot_error.has_error()) return 55;
    // The owning callback may replace the source before decoding a full prefix and its escape.
    const std::string prefix(4095, 'x');
    const std::string document = R"({"text":")" + prefix + R"(\u0000"})";
    scpp::string wire(document.c_str());
    struct StringRead { scpp::string& wire; scpp::string decoded; } string_read{wire, {}};
    auto string_error = scpp::json::read_record(wire, &string_read, [](void* context, scpp::Decoder& decoder) {
        auto& state = *static_cast<StringRead*>(context);
        state.wire.clear();
        decoder.begin_object();
        if (!decoder.next_field() || decoder.field_name() != "text") std::abort();
        state.decoded = decoder.read_string();
        if (decoder.next_field()) std::abort();
    });
    if (string_error.has_error() || string_read.decoded.size() != 4096
        || std::string_view(string_read.decoded.data(), 4095) != prefix || string_read.decoded.data()[4095] != 0) return 56;
    VersionReader reader;
    auto decoded = App::scpp_records::Packet::decode(reader);
    if (decoded.version != 6 || decoded.data.size() != 2 || decoded.data.at(1) != 2) return 38;
    scpp::vector_string words;
    reader.read_strings(words);
    if (words.size() != 2 || words.at(0) != scpp::string("first") || words.at(1) != scpp::string("second")) return 54;
    auto bad = App::readPacket_(scpp::string{R"({"data":[10,false,30]})"});
    if (!bad.has_error()) return 30;
    auto message = bad.error().get_message();
    if (std::string_view(message.data(), message.size()).find(R"($["data"][1])") == std::string_view::npos) return 31;
    // A duplicate is diagnosed at the second opening quote, even before a missing colon.
    auto duplicate = App::readPacket_(scpp::string{R"({"data":[],"\u0064ata" 42})"});
    if (!duplicate.has_error()) return 50;
    auto diagnostic = duplicate.error().get_message();
    if (std::string_view(diagnostic.data(), diagnostic.size()) !=
        R"(: byte 11 at $["\u0064ata"]: duplicate object key "\u0064ata")") return 51;
    auto saved = App::readPacket_(scpp::string{R"({"data":[3,4]})"});
    PacketEvents encoder;
    saved.value().encode(encoder);
    if (encoder.events != "object data array 3 4 end version 1 end ") return 44;
    auto failure = App::encodeLate_();
    if (failure.has_value() || !failure.has_error()) return 45;
    auto detail = failure.error().get_message();
    if (std::string_view(detail.data(), detail.size()) != "json_encode: non-finite float is not supported") return 46;
    if (copies != 0) return 32;
    auto copy = App::snapshot_(saved);
    copy.data.append(5);
    if (copies != 1 || saved.value().data.size() != 2 || copy.data.size() != 3) return 33;
    return 0;
}
