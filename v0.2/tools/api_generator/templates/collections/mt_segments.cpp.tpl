#pragma once
#include <stddef.h>
#include <stdint.h>

namespace scpp::mt {
// Compiler-created views borrow a live vector allocation for one joined call.
// The compiler retains the element type; stride is its native sizeof.
struct segment {
    unsigned char* data;
    size_t stride;
    int64_t size, offset, length;
    void* at(int64_t index) const;
};
struct job {
    int64_t (*run)(segment, segment);
    segment inputs[2];
    int64_t result{};
};
void join(job* jobs, size_t count);
}
