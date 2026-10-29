#pragma once
#include "scpp/integers.hpp"

namespace scpp {
struct record_construction_failure {};
// Concrete consumer-owned records: no runtime record-layout or class generator.
struct record_probe final {
    int64 value;
    explicit record_probe(int64 initial);
    record_probe(const record_probe &other);
    ~record_probe() noexcept;
};
struct record_noncopyable final {
    int64 value;
    explicit record_noncopyable(int64 initial);
    record_noncopyable(const record_noncopyable &) = delete;
    record_noncopyable &operator=(const record_noncopyable &) = delete;
    ~record_noncopyable() noexcept;
};
}
