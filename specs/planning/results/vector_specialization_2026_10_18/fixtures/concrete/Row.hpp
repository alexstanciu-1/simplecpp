#pragma once
#include <cstddef>
#include <cstdint>
#include "owner.hpp"
class Row {
    SharedOwner *state_ = nullptr;
public:
    Row() = default;
    explicit Row(int64_t value);
    Row(const Row&);
    Row(Row&&) noexcept;
    Row& operator=(const Row&);
    Row& operator=(Row&&) noexcept;
    ~Row();
    int64_t value() const;
    void value(int64_t);
    bool same(const Row&) const;
    static int live();
    static int made();
    static int dropped();
};
