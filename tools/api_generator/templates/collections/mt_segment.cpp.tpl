#pragma once
#include <stddef.h>
#include <stdint.h>

namespace scpp::mt {
struct segment_range { int64_t offset{}, length{}; };
segment_range balanced_range(int64_t length, int64_t parts, int64_t part);

// Compiler-created views borrow collection storage for one joined call.
// Vector views use stride; hash views traverse resolved live entries.
class segment {
    friend struct job;
    friend struct batch_admission;
    using field_reader = const void* (*)(const void*, int64_t, bool);
    const void* hash_owner{};
    field_reader field{};
    int64_t physical_begin{}, physical_end{};
    const void* selected(int64_t cursor, bool key) const;
    const unsigned char* data;
    bool read_only;
    size_t stride;
    int64_t size, offset, length;
    segment(const unsigned char* data, size_t stride, int64_t size, int64_t offset, int64_t length, bool read_only)
        : data(data), read_only(read_only), stride(stride), size(size), offset(offset), length(length) {}
public:
    bool next(int64_t& cursor) const noexcept;
    const void* key(int64_t cursor) const { return selected(cursor, true); }
    const void* value(int64_t cursor) const { return selected(cursor, false); }
    void* writable_value(int64_t cursor) const;
    int64_t count() const noexcept { return length; }
    const void* read_at(int64_t index) const;
    void* at(int64_t index) const;
};
}
