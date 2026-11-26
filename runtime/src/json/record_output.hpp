#ifndef SCPP_DETAIL_JSON_RECORD_OUTPUT_HPP
#define SCPP_DETAIL_JSON_RECORD_OUTPUT_HPP
#include "../structure/record_fields.hpp"

namespace scpp::detail::json {
// Names in the generated schema are source identifiers. Their bytes require
// no JSON escaping. Value encoding and escaping belong to the supplied codec.
template<class Record, class Sink, class Encode>
void write_record(const Record& value, Sink&& sink, Encode&& encode) {
    sink("{");
    bool first = true;
    structure::visit_fields(value, [&](std::string_view name, const auto& field, auto shape) {
        sink(first ? "\"" : ",\"");
        first = false;
        sink(name);
        sink("\":");
        if constexpr (std::is_same_v<decltype(shape), structure::detail::leaf>) {
            encode(field, sink);
        } else {
            sink("[");
            const auto count = shape.count(field);
            for (std::remove_cv_t<decltype(count)> index = 0; index < count; ++index) {
                if (index) sink(",");
                encode(shape.at(field, index), sink);
            }
            sink("]");
        }
    });
    sink("}");
}
}
#endif
