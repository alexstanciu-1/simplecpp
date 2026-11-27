// Generated Scalar and Words record headers are supplied by the driver build.
#include "scpp/json/decode_record.hpp"
#include <cassert>
#include <string>
#include <string_view>

static std::string bytes(const scpp::string& s) {
    return {s.data(), static_cast<std::size_t>(s.size())};
}

template<class Result>
static Result decode_then_overwrite(const std::string& document) {
    scpp::string input(document.data(), static_cast<scpp::int64>(document.size()));
    auto result = Result::from_text(input);
    for (scpp::int64 i = 0; i < input.size(); ++i) input.set_byte(i, '!');
    return result;
}

int main() {
    using namespace App;
    const std::string plain(4096, 'p');
    const std::string escaped = plain + R"(\u0000tail)";
    const std::string decoded = plain + std::string("\0tail", 5);
    for (const auto& s : {std::string("x"), plain, escaped}) {
        const auto result = decode_then_overwrite<record_result_Scalar>("{\"value\":\"" + s + "\"}");
        assert(result.has_value() && bytes(result.value().value) == (s == escaped ? decoded : s));
    }
    const auto words = decode_then_overwrite<record_result_Words>("{\"value\":[\"\",\"" + plain + "\",\"" + escaped + "\"]}");
    assert(words.has_value() && words.value().value.size() == 3);
    assert(bytes(words.value().value.at(0)).empty());
    assert(bytes(words.value().value.at(1)) == plain && bytes(words.value().value.at(2)) == decoded);

    // An arbitrary native consumer can replace the input before consuming any fields.
    scpp::string input(R"({"payload":"ordinary text longer than inline storage"})");
    auto failure = scpp::json::read_record(input, &input, [](void* alias, scpp::Decoder& d) {
        static_cast<scpp::string*>(alias)->clear();
        d.begin_object();
        assert(d.next_field() && d.field_name() == "payload");
        assert(bytes(d.read_string()) == "ordinary text longer than inline storage");
        assert(!d.next_field());
    });
    assert(!failure.has_error());
}
