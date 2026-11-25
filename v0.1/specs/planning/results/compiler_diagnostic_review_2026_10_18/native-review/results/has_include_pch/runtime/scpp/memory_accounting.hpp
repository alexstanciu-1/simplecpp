#pragma once

#include "scpp/hash_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/vector_t.hpp"

#include <cstddef>

namespace scpp::memory {

// Approximate container storage helpers for trend-oriented compiler probes.
//
// These report wrapper-owned storage and capacity, not exact allocator overhead,
// not shared global pools, and not recursively owned child object graphs.
[[nodiscard]] inline std::size_t string_byte_length(const string_t &value) noexcept {
	return value.byte_size();
}

[[nodiscard]] inline std::size_t string_byte_capacity(const string_t &value) noexcept {
	return value.byte_capacity();
}

[[nodiscard]] inline std::size_t estimated_bytes(const string_t &value) noexcept {
	return value.estimated_storage_bytes();
}

template <typename T>
[[nodiscard]] std::size_t vector_count(const vector_t<T> &value) noexcept {
	return value.size();
}

template <typename T>
[[nodiscard]] std::size_t vector_capacity(const vector_t<T> &value) noexcept {
	return value.capacity();
}

template <typename T>
[[nodiscard]] std::size_t estimated_bytes(const vector_t<T> &value) noexcept {
	return value.estimated_storage_bytes();
}

template <typename T_VALUE, typename T_KEY>
[[nodiscard]] std::size_t hash_count(const hash_t<T_VALUE, T_KEY> &value) noexcept {
	return value.size();
}

template <typename T_VALUE, typename T_KEY>
[[nodiscard]] std::size_t hash_capacity(const hash_t<T_VALUE, T_KEY> &value) noexcept {
	return value.capacity();
}

template <typename T_VALUE, typename T_KEY>
[[nodiscard]] std::size_t hash_key_capacity(const hash_t<T_VALUE, T_KEY> &value) noexcept {
	return value.key_capacity();
}

template <typename T_VALUE, typename T_KEY>
[[nodiscard]] std::size_t hash_index_capacity(const hash_t<T_VALUE, T_KEY> &value) noexcept {
	return value.index_capacity();
}

template <typename T_VALUE, typename T_KEY>
[[nodiscard]] std::size_t estimated_bytes(const hash_t<T_VALUE, T_KEY> &value) noexcept {
	return value.estimated_storage_bytes();
}

} // namespace scpp::memory
