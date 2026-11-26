// Bridge structural JSON transports to the codec-independent data-record schema.
#pragma once
#include "../structure/record.hpp"
#include "structural_reader.hpp"
#include "record_output.hpp"

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
    template<class Vector, class Shape> void sequence(Vector& items, const Shape& shape) {
        using Element = std::remove_cvref_t<decltype(shape.at(items, shape.count(items)))>;
        source.begin_array();
        while (source.next_element()) shape.append(items, read<Element>());
    }
    void reject(const char* message) { source.reject(message); }
};

}
