#pragma once
#include <cstdint>
struct RowPayload {
    inline static int live = 0, made = 0, dropped = 0;
    int64_t value_;
    int64_t extra = 73;
    explicit RowPayload(int64_t v) : value_(v) { ++live; ++made; }
    ~RowPayload() { --live; ++dropped; }
    int64_t value() const { return value_ + extra - 73; }
};
