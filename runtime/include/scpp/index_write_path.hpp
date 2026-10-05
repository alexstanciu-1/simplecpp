#pragma once

#include "scpp/index_write.hpp"

namespace scpp {

// Private-to-lowering traversal. No source callback runs between probe and mutation,
// and no returned reference is exposed as a source-language native binding.
template <typename T>
const T &index_path_probe(const vector_t<T> &receiver, const int_t<> &key) {
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
		throw std::runtime_error("nested write: expected mixed table; cross-family/weak paths are deferred");
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

inline const dynamic_t<> &index_path_probe(const dynamic_t<> &receiver, const mixed_t &key) {
	require_index_key(key);
	if (!receiver.get()) throw std::runtime_error("nested write: absent dynamic handle");
	const auto &entry = static_cast<const hash_t<mixed_t> &>(*receiver).at(key);
	const auto *child = entry.dynamic_if();
	if (!child || !child->get())
		throw std::runtime_error("nested write: expected constructed dynamic child; cross-family paths are deferred");
	return *child;
}

template <typename T>
T &index_path_descend(vector_t<T> &receiver, const int_t<> &key) {
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

inline dynamic_t<> &index_path_descend(dynamic_t<> &receiver, const mixed_t &key) {
	(void)index_path_probe(receiver, key);
	return *receiver->at(key).dynamic_if();
}

// Check the leaf before ANY path creation or detachment. Value conversion has
// already completed. Allocation/copy failures during commit are not transactions.
template <typename T>
void index_path_check_store(const vector_t<T> &receiver, const int_t<> &key, const T &) {
	(void)receiver.at(key);
}

template <typename V, typename K>
void index_path_check_store(const hash_t<V, K> &, const K &, const V &) {}

inline void index_path_check_value(const mixed_t &receiver, const mixed_t &value) {
	(void)index_path_mixed_table(receiver);
	if (value.kind() == mixed_t::kind_t::dynamic_v)
		throw std::runtime_error("nested write: dynamic value in mixed is deferred");
}

inline void index_path_check_value(const dynamic_t<> &receiver, const mixed_t &value) {
	if (!receiver.get()) throw std::runtime_error("nested write: absent dynamic handle");
	if (value.kind() == mixed_t::kind_t::table_v
		|| value.kind() == mixed_t::kind_t::shared_table_v
		|| value.kind() == mixed_t::kind_t::weak_table_v)
		throw std::runtime_error("nested write: mixed table in dynamic is deferred");
}

inline void index_path_check_store(const mixed_t &receiver, const mixed_t &key, const mixed_t &value) {
	require_index_key(key);
	index_path_check_value(receiver, value);
}

inline void index_path_check_store(const dynamic_t<> &receiver, const mixed_t &key, const mixed_t &value) {
	require_index_key(key);
	index_path_check_value(receiver, value);
}

template <typename T>
void index_path_check_append(const vector_t<T> &, const T &) {}

template <typename V>
void index_path_check_append(const hash_t<V, int_t<>> &, const V &) {}

inline void index_path_check_append(const mixed_t &receiver, const mixed_t &value) {
	index_path_check_value(receiver, value);
}

inline void index_path_check_append(const dynamic_t<> &receiver, const mixed_t &value) {
	index_path_check_value(receiver, value);
}

} // namespace scpp
