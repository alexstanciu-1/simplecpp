#pragma once

#include "scpp/index_write.hpp"

namespace scpp {

// Private-to-lowering traversal. No source callback runs between probe and mutation,
// and no returned reference is exposed as a source-language native binding.
template <typename T, typename K> requires detail::is_int_t_v<K>
const T &index_path_probe(const vector_t<T> &receiver, const K &key) {
	return receiver.at(key);
}

template <typename V, typename K>
const V &index_path_probe(const hash_t<V, K> &receiver, const K &key) {
	return receiver.at(key); // Typed intermediates must already exist.
}

inline const hash_t<mixed_t> *index_path_mixed_table(const mixed_t &receiver) {
	if (receiver.kind() == mixed_t::kind_t::null_v) return nullptr;
	if (receiver.kind() != mixed_t::kind_t::table_v
		&& receiver.kind() != mixed_t::kind_t::shared_table_v)
		throw std::runtime_error("nested write: expected mixed table; weak paths are deferred");
	const auto *table = receiver.table_if();
	if (!table) throw std::runtime_error("nested write: absent mixed table carrier");
	return table;
}

inline const mixed_t &index_path_probe(const mixed_t &receiver, const mixed_t &key) {
	require_index_key(key);
	const auto *table = index_path_mixed_table(receiver);
	if (table) return (*table)[key]; // Const lookup returns null on miss, without copying.
	static const mixed_t absent{null};
	return absent;
}

inline const detail::dynamic_value &index_path_probe(const detail::dynamic_value &receiver, const mixed_t &key) {
	require_index_key(key);
	const auto &child = receiver.get_table().at(key);
	(void)child.get_table(); // Dynamic intermediates must be constructed containers.
	return child;
}

template <typename T, typename K> requires detail::is_int_t_v<K>
T &index_path_descend(vector_t<T> &receiver, const K &key) {
	return receiver.at(key);
}

template <typename V, typename K>
V &index_path_descend(hash_t<V, K> &receiver, const K &key) {
	return receiver.at(key);
}

inline mixed_t &index_path_descend(mixed_t &receiver, const mixed_t &key) {
	(void)index_path_mixed_table(receiver);
	return receiver[key]; // Existing runtime owner performs null bootstrap and COW.
}

inline detail::dynamic_value &index_path_descend(detail::dynamic_value &receiver, const mixed_t &key) {
	(void)index_path_probe(receiver, key);
	return receiver.get_table().at(key);
}

// Check the leaf before ANY path creation or detachment. Value conversion has
// already completed. Allocation/copy failures during commit are not transactions.
template <typename T, typename K> requires detail::is_int_t_v<K>
void index_path_check_store(const vector_t<T> &receiver, const K &key, const T &) {
	(void)receiver.at(key);
}

template <typename V, typename K>
void index_path_check_store(const hash_t<V, K> &, const K &, const V &) {}

inline void index_path_check_value(const mixed_t &receiver, const mixed_t &) {
	(void)index_path_mixed_table(receiver);
}

inline void index_path_check_store(const mixed_t &receiver, const mixed_t &key, const mixed_t &value) {
	require_index_key(key);
	index_path_check_value(receiver, value);
}

inline void index_path_check_store(const detail::dynamic_value &receiver, const mixed_t &key, const detail::dynamic_value &) {
	require_index_key(key);
	(void)receiver.get_table();
}

template <typename T>
void index_path_check_append(const vector_t<T> &, const T &) {}

template <typename V, typename K> requires detail::is_int_t_v<K>
void index_path_check_append(const hash_t<V, K> &, const V &) {}

inline void index_path_check_append(const mixed_t &receiver, const mixed_t &value) {
	index_path_check_value(receiver, value);
}

inline void index_path_check_append(const detail::dynamic_value &receiver, const detail::dynamic_value &) {
	(void)receiver.get_table();
}

// Saved-key access is internal representation borrowing, never source evaluation.
template <typename R, typename KeyAt>
void index_path_commit_adapted(R &root, std::size_t prefix_count, const KeyAt &key_at,
	const R &value, bool append) {
	R *target = &root;
	for (std::size_t index = 0; index < prefix_count; ++index)
		target = &index_path_descend(*target, key_at(index));
	if (append) index_append(*target, value);
	else index_store(*target, key_at(prefix_count), value);
}

template <typename R, typename KeyAt>
void index_path_write_adapted(R &root, std::size_t count, const KeyAt &key_at,
	const R &value, bool append) {
	if (!append && count == 0) throw std::logic_error("indexed store requires a saved key");
	const std::size_t prefix_count = append ? count : count - 1;
	const R *target = &root;
	for (std::size_t index = 0; index < prefix_count; ++index)
		target = &index_path_probe(*target, key_at(index));
	if (append) index_path_check_append(*target, value);
	else index_path_check_store(*target, key_at(prefix_count), value);
	// A public caller may pass the root itself as RHS. Preserve its pre-commit value.
	if (&root == &value) {
		const R snapshot(value);
		index_path_commit_adapted(root, prefix_count, key_at, snapshot, append);
	} else index_path_commit_adapted(root, prefix_count, key_at, value, append);
}

} // namespace scpp
