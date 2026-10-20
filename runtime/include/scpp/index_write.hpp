#pragma once

#include "scpp/index_read.hpp"

namespace scpp {

// Validation occurs before RHS evaluation; numeric-string normalization stays in hash storage.
template<class K> requires detail::is_int_t_v<K>
void require_write_key(const K &) {}
inline void require_write_key(const string_t &) {}
inline void require_write_key(const mixed_t &key) { require_index_key(key); }

template <typename T, typename K> requires detail::is_int_t_v<K>
void index_store(vector_t<T> &receiver, const K &key, const T &value) {
	receiver.at(key) = value;
}

template <typename V, typename K>
void index_store(hash_t<V, K> &receiver, const K &key, const V &value) {
	receiver.set(key, value);
}

inline void index_store(detail::dynamic_value &receiver, const mixed_t &key, const detail::dynamic_value &value) {
	require_index_key(key);
	receiver.get_table().set(key, value);
}

inline void index_store(mixed_t &receiver, const mixed_t &key, const mixed_t &value) {
	require_index_key(key);
	// Existing mutable access owns null bootstrap and copy-on-write, without escaping its slot.
	receiver[key] = value;
}

template <typename T>
void index_append(vector_t<T> &receiver, const T &value) { (void)receiver.append(value); }

template <typename V, typename K> requires detail::is_int_t_v<K>
void index_append(hash_t<V, K> &receiver, const V &value) { (void)receiver.append(value); }

inline void index_append(mixed_t &receiver, const mixed_t &value) { receiver.append(value); }

inline void index_append(detail::dynamic_value &receiver, const detail::dynamic_value &value) {
	(void)receiver.get_table().append(value);
}

} // namespace scpp
