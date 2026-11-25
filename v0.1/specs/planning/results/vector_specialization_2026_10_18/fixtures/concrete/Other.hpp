#pragma once
#include <cstddef>
#include <cstdint>
#include "owner.hpp"
class Other {
    SharedOwner *state_ = nullptr;
public:
    Other() = default;
    explicit Other(int64_t value);
    Other(const Other&);
    Other(Other&&) noexcept;
    Other& operator=(const Other&);
    Other& operator=(Other&&) noexcept;
    ~Other();
    int64_t value() const;
    void value(int64_t);
    bool same(const Other&) const;
    static int live();
    static int made();
    static int dropped();
};
