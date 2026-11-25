#pragma once

#include <utility>
#include "scpp/index_write_path.hpp"

namespace scpp {

// Unlike ordinary boxed reads/writes, every segment must exist. No creation or
// mutable borrow occurs during the initial read or the pre-commit validation.
template <typename T>
const T &index_existing_at(const vector_t<T> &receiver, const int_t<> &key) {
	return receiver.at(key);
}

template <typename V, typename K>
const V &index_existing_at(const hash_t<V, K> &receiver, const K &key) {
	return receiver.at(key);
}

inline const mixed_t &index_existing_at(const mixed_t &receiver, const mixed_t &key) {
	require_index_key(key);
	const auto *table = index_path_mixed_table(receiver);
	if (!table) throw std::runtime_error("indexed compound update requires an existing table");
	return table->at(key);
}

inline const detail::dynamic_value &index_existing_at(const detail::dynamic_value &receiver, const mixed_t &key) {
	require_index_key(key);
	return receiver.get_table().at(key);
}

template <typename R, typename K>
const auto &index_existing_probe(const R &receiver, const K &key) {
	return index_existing_at(receiver, key);
}

template <typename R, typename K, typename... Rest> requires (sizeof...(Rest) > 0)
const auto &index_existing_probe(const R &receiver, const K &key, const Rest &...rest) {
	return index_existing_probe(index_existing_at(receiver, key), rest...);
}

// Value return is intentional: no interior reference survives source RHS code.
template <typename R, typename... Keys>
auto index_existing_read(const R &receiver, const Keys &...keys) {
	return index_existing_probe(receiver, keys...);
}

template <typename R, typename V, typename K>
void index_existing_commit(R &receiver, const V &value, const K &key) {
	index_store(receiver, key, value);
}

template <typename R, typename V, typename K, typename... Rest> requires (sizeof...(Rest) > 0)
void index_existing_commit(R &receiver, const V &value, const K &key, const Rest &...rest) {
	index_existing_commit(index_path_descend(receiver, key), value, rest...);
}

template <typename R, typename V, typename... Keys>
void index_existing_store(R &receiver, const V &value, const Keys &...keys) {
	// Re-resolve ALL segments before any detachment or write. No source callbacks
	// occur between validation and commit; allocation failure is not a transaction.
	(void)index_existing_probe(std::as_const(receiver), keys...);
	index_existing_commit(receiver, value, keys...);
}

} // namespace scpp
