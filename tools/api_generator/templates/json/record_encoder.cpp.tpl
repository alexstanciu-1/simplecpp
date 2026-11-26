#pragma once
#include "scpp/detail/json/record_output.hpp"
@includes@
namespace scpp::detail::json { class writer; }
namespace scpp::json {
struct record_sink {
    detail::json::writer& output;
    void operator()(std::string_view bytes) const;
@declarations@};
result_string encode_record_erased(const void*, void (*)(const void*, detail::json::writer&));

template<class Record> result_string encode_record(const Record& value) {
    return encode_record_erased(&value, [](const void* input, detail::json::writer& output) {
        record_sink sink{output};
        detail::json::write_record(*static_cast<const Record*>(input), sink,
            [](const auto& field, const record_sink& target) { target.write(field); });
    });
}
template<class Owner> result_string encode_owned_record(const Owner& value) {
    if (!value) return result_string{string{"null"}};
    return encode_record(*value.operator->());
}
}
