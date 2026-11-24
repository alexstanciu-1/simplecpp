#pragma once
#include <stddef.h>
#include <stdint.h>

namespace scpp::mt {
struct segment_range { int64_t offset{}, length{}; };
segment_range balanced_range(int64_t length, int64_t parts, int64_t part);

// Compiler-created views borrow a live vector allocation for one joined call.
// The compiler retains the element type; stride is its native sizeof.
class segment {
    friend struct job;
    const unsigned char* data;
    bool read_only;
    size_t stride;
    int64_t size, offset, length;
    segment(const unsigned char* data, size_t stride, int64_t size, int64_t offset, int64_t length, bool read_only)
        : data(data), read_only(read_only), stride(stride), size(size), offset(offset), length(length) {}
public:
    int64_t count() const noexcept { return length; }
    const void* read_at(int64_t index) const;
    void* at(int64_t index) const;
};
}
