#pragma once
#include "allocation.hpp"

namespace scpp_provider {
using storage_address = void*;
// Native storage mechanics only. The compiler constructs and ends element lifetimes.
struct element_storage {
    allocation memory;
    std::int64_t capacity = 0;
    std::int64_t count = 0;
    std::int64_t stride = 0;
    element_storage() = default;
    element_storage(const element_storage&) = delete;
};
inline void storage_allocate(element_storage& owner, std::int64_t capacity,
                             std::int64_t stride, std::int64_t alignment) {
    if (capacity <= 0 || stride <= 0 || alignment <= 0 || stride % alignment
        || capacity > std::numeric_limits<std::int64_t>::max() / stride)
        throw std::invalid_argument("invalid or overflowing element storage extent");
    allocation_acquire(owner.memory, capacity * stride, alignment);
    owner.capacity = capacity;
    owner.stride = stride;
    owner.count = 0;
}
inline void* storage_next(element_storage& owner) {
    if (!owner.memory.address || owner.count >= owner.capacity)
        throw std::out_of_range("element storage is full or empty");
    return static_cast<unsigned char*>(owner.memory.address) + owner.count * owner.stride;
}
inline void storage_commit(element_storage& owner) {
    (void)storage_next(owner);
    ++owner.count;
}
inline void* storage_at(const element_storage& owner, std::int64_t index) {
    if (!owner.memory.address || index < 0 || index >= owner.count)
        throw std::out_of_range("element storage index is outside the live prefix");
    return static_cast<unsigned char*>(owner.memory.address) + index * owner.stride;
}
inline void storage_pop(element_storage& owner) {
    if (!owner.memory.address || owner.count <= 0)
        throw std::out_of_range("element storage has no live last element");
    --owner.count;
}
inline std::int64_t storage_count(const element_storage& owner) { return owner.count; }
inline void storage_release(element_storage& owner) {
    if (owner.count) throw std::logic_error("cannot release storage containing live elements");
    allocation_release(owner.memory);
    owner.capacity = 0;
    owner.stride = 0;
}
inline void storage_transfer(element_storage& source, element_storage& destination) {
    allocation_transfer(source.memory, destination.memory);
    destination.capacity = source.capacity;
    destination.count = source.count;
    destination.stride = source.stride;
    source.capacity = 0;
    source.count = 0;
    source.stride = 0;
}
}
