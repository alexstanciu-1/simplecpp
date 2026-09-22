#pragma once
#include <cstddef>

namespace scpp_provider {
// Concrete sequence semantics live with this provider, selected by family metadata.
// No references to elements escape these calls. Mutation takes a distinct live source.
template<class Sequence, class Element>
void sequence_append(Sequence& values, const Element& value) {
    values.append(value);
}

template<class Sequence>
std::size_t sequence_length(const Sequence& values) {
    return values.size();
}

template<class Sequence, class Element>
Element sequence_read(const Sequence& values, std::size_t index) {
    return values.at(index);
}
}
