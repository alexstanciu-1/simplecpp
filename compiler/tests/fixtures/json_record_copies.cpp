// The generated Packet header is supplied by the test runner.
#include SCPP_PACKET_HEADER
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
    bool offered = false;
public:
    void begin_object() override {}
    bool next_field() override { return !std::exchange(offered, true); }
    std::string_view field_name() override { return "version"; }
    scpp::int64 read_integer() override { return 6; }
    void begin_array() override { reject("array"); }
    bool next_element() override { reject("element"); }
    bool read_boolean() override { reject("bool"); }
    scpp::string read_string() override { reject("string"); }
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
    void write(const scpp::int64& value) override { events += std::to_string(value) + ' '; }
    void write_null() override { std::abort(); }
    void write(const scpp::bool_t&) override { std::abort(); }
    void write(const scpp::dynamic&) override { std::abort(); }
    void write(const scpp::float64&) override { std::abort(); }
    void write(const scpp::mixed&) override { std::abort(); }
    void write(const scpp::nullable_bool_t&) override { std::abort(); }
    void write(const scpp::nullable_float64&) override { std::abort(); }
    void write(const scpp::nullable_int64&) override { std::abort(); }
    void write(const scpp::nullable_string&) override { std::abort(); }
    void write(const scpp::string&) override { std::abort(); }
};

int main() {
    if (const auto status = App::scpp_entry_body()) return status;
    VersionReader reader;
    if (App::scpp_records::Packet::decode(reader).version != 6) return 38;
    auto bad = App::readPacket_(scpp::string{R"({"data":[10,false,30]})"});
    if (!bad.has_error()) return 30;
    auto message = bad.error().get_message();
    if (std::string_view(message.data(), message.size()).find(R"($["data"][1])") == std::string_view::npos) return 31;
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
