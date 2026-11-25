// Independent C++ execution reference for the hand-authored LLVM fixture bodies.
#include "native.hpp"
#ifndef BODY_DELTA
#define BODY_DELTA 1
#endif

extern "C" std::int32_t proof_initializer(std::int32_t seed) noexcept {
    proof_event(5, seed);
    return seed + 100;
}

extern "C" void proof_constructor_body(void *storage, std::int32_t seed) noexcept {
    auto& self = *static_cast<lifecycle_probe::custom_record*>(storage);
    proof_equal(self.first.value, seed + 100);
    proof_equal(self.marker, 0);
    self.marker = seed + BODY_DELTA;
    lifecycle_probe::append(self, self.marker);
    proof_event(6, self.marker);
}

extern "C" void proof_destructor_body(void *storage) noexcept {
    auto& self = *static_cast<lifecycle_probe::custom_record*>(storage);
    proof_event(7, self.marker);
    proof_event(10, lifecycle_probe::length(self));
    proof_event(11, self.first.value);
}

extern "C" void proof_run() {
    using namespace lifecycle_probe;
    {
        automatic_record first;
        append(first, 42);
        automatic_record copy(first);
        append(first, 99);
        proof_equal(length(copy), 1);
        proof_equal(first_value(copy), 42);
        assign(copy, first);
        append(first, 100);
        proof_equal(length(copy), 2);
    }
    {
        custom_record first(10);
        custom_record copy(first);
        append(first, 99);
        proof_equal(length(first), 2);
        proof_equal(length(copy), 1);
        proof_equal(first_value(copy), 10 + BODY_DELTA);
        custom_record target(20);
        assign(target, first);
        append(first, 100);
        proof_equal(length(target), 2);
        proof_equal(first_value(target), 10 + BODY_DELTA);
    }
    shared_lifetime(30);
    rvalue_copy();
}
