// Experimental concrete ownership shell; no runtime templates visible.
#pragma once
#include "handle_abi.h"
class ConcreteAmount {
    AmountHandle *value_;
public:
    ConcreteAmount() : value_(amount_create()) {}
    explicit ConcreteAmount(AmountHandle *value) : value_(value) {}
    ConcreteAmount(const ConcreteAmount& other) : value_(amount_retain(other.value_)) {}
    ConcreteAmount(ConcreteAmount&& other) noexcept : value_(other.release()) {}
    ConcreteAmount& operator=(const ConcreteAmount&) = delete;
    ~ConcreteAmount() { amount_release(value_); }
    AmountHandle *get() const { return value_; }
    AmountHandle *release() { auto *value = value_; value_ = nullptr; return value; }
};
