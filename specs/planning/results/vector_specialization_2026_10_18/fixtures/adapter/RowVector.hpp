#pragma once
#include "Row.hpp"
#include "bounds.hpp"
struct RowVectorState;
class RowVector {
    RowVectorState *state_;
public:
    RowVector();
    RowVector(const RowVector&);
    RowVector(RowVector&&) noexcept;
    RowVector& operator=(const RowVector&);
    RowVector& operator=(RowVector&&) noexcept;
    ~RowVector();
    size_t size() const;
    void append(Row);
    Row get(size_t) const;
    void set(size_t, Row);
};
