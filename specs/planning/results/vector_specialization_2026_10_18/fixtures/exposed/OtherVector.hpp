#pragma once
#include "Other.hpp"
#include "Other_payload.hpp"
#include "bounds.hpp"
class OtherVector {
    scpp::vector_t<Other> values_;
public:
    size_t size() const { return values_.size(); }
    void append(Other value) { values_.append(static_cast<Other&&>(value)); }
    Other get(size_t i) const { if(i >= size()) throw BoundsError{}; return values_.at(i); }
    void set(size_t i, Other value) { if(i >= size()) throw BoundsError{}; values_.at(i) = static_cast<Other&&>(value); }
};
