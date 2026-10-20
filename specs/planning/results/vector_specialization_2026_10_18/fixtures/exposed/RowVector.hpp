#pragma once
#include "Row.hpp"
#include "Row_payload.hpp"
#include "bounds.hpp"
class RowVector {
    scpp::vector_t<Row> values_;
public:
    size_t size() const { return values_.size(); }
    void append(Row value) { values_.append(static_cast<Row&&>(value)); }
    Row get(size_t i) const { if(i >= size()) throw BoundsError{}; return values_.at(i); }
    void set(size_t i, Row value) { if(i >= size()) throw BoundsError{}; values_.at(i) = static_cast<Row&&>(value); }
};
