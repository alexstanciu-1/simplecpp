#pragma once
#include <stddef.h>
#include <stdint.h>

namespace scpp::mt {
// Compiler-created views borrow a live vector allocation for one joined call.
// The compiler retains the element type; stride is its native sizeof.
class segment {
    friend struct job;
    unsigned char* data;
    size_t stride;
    int64_t size, offset, length;
    segment(unsigned char* data, size_t stride, int64_t size, int64_t offset, int64_t length)
        : data(data), stride(stride), size(size), offset(offset), length(length) {}
public:
    int64_t count() const noexcept { return length; }
    void* at(int64_t index) const;
};
}
