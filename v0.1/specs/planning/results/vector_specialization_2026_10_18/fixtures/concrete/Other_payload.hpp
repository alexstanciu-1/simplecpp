#pragma once
#include <cstdint>
struct OtherPayload {
    inline static int live = 0, made = 0, dropped = 0;
    int64_t value_;
    
    explicit OtherPayload(int64_t v) : value_(v) { ++live; ++made; }
    ~OtherPayload() { --live; ++dropped; }
    int64_t value() const { return value_; }
};
