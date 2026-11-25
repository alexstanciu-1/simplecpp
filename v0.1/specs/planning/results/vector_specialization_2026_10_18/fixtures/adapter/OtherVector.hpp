#pragma once
#include "Other.hpp"
#include "bounds.hpp"
struct OtherVectorState;
class OtherVector {
    OtherVectorState *state_;
public:
    OtherVector();
    OtherVector(const OtherVector&);
    OtherVector(OtherVector&&) noexcept;
    OtherVector& operator=(const OtherVector&);
    OtherVector& operator=(OtherVector&&) noexcept;
    ~OtherVector();
    size_t size() const;
    void append(Other);
    Other get(size_t) const;
    void set(size_t, Other);
};
