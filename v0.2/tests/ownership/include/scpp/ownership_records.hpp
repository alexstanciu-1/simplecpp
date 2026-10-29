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
// Over-alignment and move capabilities belong to the payload, not the wrapper.
struct alignas(32) record_move_only final {
    int64 value{0};
    record_move_only() noexcept = default;
    explicit record_move_only(int64 initial) noexcept : value(initial) {}
    record_move_only(const record_move_only &) = delete;
    record_move_only &operator=(const record_move_only &) = delete;
    record_move_only(record_move_only &&other) noexcept : value(other.value) { other.value = int64{0}; }
    record_move_only &operator=(record_move_only &&other) noexcept {
        if (this != &other) { value = other.value; other.value = int64{0}; }
        return *this;
    }
};
struct record_move_failure {};
struct record_throwing_move final {
    int64 value{0};
    record_throwing_move() noexcept = default;
    explicit record_throwing_move(int64 initial) noexcept : value(initial) {}
    record_throwing_move(const record_throwing_move &) = default;
    record_throwing_move &operator=(const record_throwing_move &) = default;
    record_throwing_move(record_throwing_move &&other);
    record_throwing_move &operator=(record_throwing_move &&other);
};
}
