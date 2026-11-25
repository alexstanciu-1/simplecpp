#pragma once
#include "Row.hpp"
#include "bounds.hpp"
struct RowVectorState;
class RowVector {
    Row *data_ = nullptr; size_t size_ = 0, capacity_ = 0;
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
