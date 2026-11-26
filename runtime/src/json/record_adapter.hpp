// Bridge structural JSON transports to the codec-independent data-record schema.
#pragma once
#include "../structure/record.hpp"
#include "structural_reader.hpp"

namespace scpp::detail::json {
// The caller selects the typed value reader, including recursive collection and
// record readers. finish_document() belongs to the outermost transport.
template<class Decode> struct record_input {
    pull_reader& source;
    Decode& decode;

    template<class Fields> void fields(Fields consume) {
        source.begin_object();
        while (source.next_property()) consume(source.property_view());
    }
    template<class Value> Value read() { return decode.template operator()<Value>(source); }
    void reject(const char* message) { source.reject(message); }
};

// Names in the generated schema are source identifiers. Their bytes require
// no JSON escaping. Value encoding and escaping belong to the supplied codec.
template<class Record, class Sink, class Encode>
void write_record(const Record& value, Sink&& sink, Encode&& encode) {
    sink("{");
    bool first = true;
    structure::visit(value, [&](std::string_view name, const auto& field) {
        sink(first ? "\"" : ",\"");
        first = false;
        sink(name);
        sink("\":");
        encode(field, sink);
    });
    sink("}");
}
}
