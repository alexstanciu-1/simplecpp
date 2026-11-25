#pragma once
#include <stddef.h>
#include <cstddef>
#include <stdint.h>
#include <new>
#include <stdexcept>

namespace scpp::detail {
// Shared untyped allocation mechanics; concrete generated code owns every element lifetime.
inline size_t vector_capacity_limit(size_t element_size) noexcept {
    return static_cast<size_t>(PTRDIFF_MAX) / element_size;
}
inline size_t vector_growth(size_t capacity, size_t required, size_t element_size) {
    const auto limit = vector_capacity_limit(element_size);
    if (required > limit) throw std::length_error("vector_too_large");
    const auto grown = capacity == 0 ? (limit < 4 ? limit : 4)
        : (capacity > limit / 2 ? limit : capacity * 2);
    return grown < required ? required : grown;
}
inline unsigned char* vector_allocate(size_t capacity, size_t element_size, size_t alignment) {
    if (capacity > vector_capacity_limit(element_size)) throw std::length_error("vector_too_large");
    if (capacity == 0) return nullptr;
    const auto bytes = capacity * element_size;
    return static_cast<unsigned char*>(alignment > __STDCPP_DEFAULT_NEW_ALIGNMENT__
        ? ::operator new(bytes, static_cast<std::align_val_t>(alignment)) : ::operator new(bytes));
}
inline void vector_deallocate(unsigned char* data, size_t alignment) noexcept {
    if (alignment > __STDCPP_DEFAULT_NEW_ALIGNMENT__) ::operator delete(data, static_cast<std::align_val_t>(alignment));
    else ::operator delete(data);
}
inline size_t vector_index(int64_t index, size_t count) {
    if (index < 0 || static_cast<uint64_t>(index) >= count) throw std::out_of_range("sequence_index_out_of_range");
    return static_cast<size_t>(index);
}
}
