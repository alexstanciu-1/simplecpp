#pragma once

#include "scpp/index_write_path.hpp"
#include "lang/php/operators/isset/isset.hpp"
#include "lang/php/operators/empty/empty.hpp"
#include <cstdint>
#include <type_traits>

namespace scpp {

inline bool keyed_invalid(bool removing) {
	if (removing) throw std::runtime_error("unset: invalid keyed receiver or key");
	return false;
}

template <typename T>
bool keyed_ready(const T *receiver, bool) { return receiver != nullptr; }

inline bool keyed_ready(const mixed_t *receiver, bool removing) {
	if (!receiver || receiver->kind() == mixed_t::kind_t::null_v) return false;
	if (receiver->kind() == mixed_t::kind_t::weak_table_v)
		throw std::runtime_error("keyed path: weak mixed paths are deferred");
	if (receiver->kind() != mixed_t::kind_t::table_v
		&& receiver->kind() != mixed_t::kind_t::shared_table_v) return keyed_invalid(removing);
	return receiver->table_if() != nullptr;
}

inline bool keyed_ready(const detail::dynamic_value *receiver, bool removing) {
	if (!receiver || receiver->is_null().native_value()) return false;
	return receiver->table_if() ? true : keyed_invalid(removing);
}

template <typename T, typename K> requires detail::is_int_t_v<K>
const T *keyed_lookup(const vector_t<T> *receiver, const K &key, bool) {
	if (!receiver || key.native_value() < 0
		|| static_cast<std::uint64_t>(key.native_value()) >= receiver->size()) return nullptr;
	return &receiver->at(key);
}

template <typename T>
const T *keyed_lookup(const vector_t<T> *receiver, const mixed_t &key, bool removing) {
	if (!receiver) return nullptr;
	if (key.kind() != mixed_t::kind_t::int_v) { keyed_invalid(removing); return nullptr; }
	return keyed_lookup(receiver, key.int_value(), removing);
}

template <typename V, typename K>
const V *keyed_lookup(const hash_t<V, K> *receiver, const K &key, bool) {
	if (!receiver || !receiver->has(key).native_value()) return nullptr;
	return &receiver->at(key);
}

template <typename V, typename K> requires (!std::is_same_v<K, mixed_t>)
const V *keyed_lookup(const hash_t<V, K> *receiver, const mixed_t &key, bool removing) {
	if (!receiver) return nullptr;
	if constexpr (detail::default_index_integer<K>) {
		if (key.kind() == mixed_t::kind_t::int_v) return keyed_lookup(receiver, K{key.int_value().native_value()}, removing);
	} else if constexpr (std::is_same_v<K, string_t>) {
		if (key.kind() == mixed_t::kind_t::string_v) return keyed_lookup(receiver, *key.string_if(), removing);
	}
	keyed_invalid(removing);
	return nullptr;
}

inline bool keyed_valid_key(const mixed_t &key, bool removing) {
	if (key.kind() == mixed_t::kind_t::int_v || key.kind() == mixed_t::kind_t::string_v) return true;
	return keyed_invalid(removing);
}

inline const mixed_t *keyed_lookup(const mixed_t *receiver, const mixed_t &key, bool removing) {
	if (!keyed_ready(receiver, removing) || !keyed_valid_key(key, removing)) return nullptr;
	return keyed_lookup(receiver->table_if(), key, removing);
}

inline const detail::dynamic_value *keyed_lookup(const detail::dynamic_value *receiver, const mixed_t &key, bool removing) {
	if (!keyed_ready(receiver, removing) || !keyed_valid_key(key, removing)) return nullptr;
	const auto &table = receiver->get_table();
	return table.has(key).native_value() ? &table.at(key) : nullptr;
}

// Interior pointers live only within one lookup/resolution call, never across a key expression.
template <typename R, typename K>
auto keyed_child(const R *receiver, const K &key, bool removing) {
	return keyed_lookup(receiver, key, removing);
}

template <typename R, typename K>
bool_t keyed_probe(const R *receiver, const K &key, bool emptiness) {
	const auto *entry = keyed_lookup(receiver, key, false);
	if (!entry) {
		if (emptiness) detail::throw_invalid_empty();
		return bool_t{false};
	}
	return emptiness ? ::scpp::php::empty(*entry) : ::scpp::php::isset(*entry);
}

template <typename K>
const K &keyed_required_key(const K &key) { return key; }

template <typename K, typename Input>
requires (detail::is_int_t_v<K> && detail::is_int_t_v<Input> && std::same_as<detail::int_rep_t<K>, detail::int_rep_t<Input>> && !std::same_as<K, Input>)
K keyed_required_key(const Input &key) { return K{key.native_value()}; }

template <typename K> requires (!std::is_same_v<K, mixed_t>)
K keyed_required_key(const mixed_t &key) {
	if constexpr (detail::default_index_integer<K>) {
		if (key.kind() == mixed_t::kind_t::int_v) return K{key.int_value().native_value()};
	} else if constexpr (std::is_same_v<K, string_t>) {
		if (key.kind() == mixed_t::kind_t::string_v) return *key.string_if();
	}
	throw std::runtime_error("unset: invalid key kind");
}

template <typename T, typename K>
T &keyed_descend(vector_t<T> &receiver, const K &key) {
	return receiver.at(keyed_required_key<int_t<>>(key));
}

template <typename V, typename K, typename Input>
V &keyed_descend(hash_t<V, K> &receiver, const Input &key) {
	return receiver.at(keyed_required_key<K>(key));
}

inline mixed_t &keyed_descend(mixed_t &receiver, const mixed_t &key) {
	// Called only after a complete non-creating recheck; the child already exists.
	return receiver[key]; // The existing mutable owner performs COW before descent.
}

inline detail::dynamic_value &keyed_descend(detail::dynamic_value &receiver, const mixed_t &key) {
	return receiver.get_table().at(key);
}

template <typename V, typename K, typename Input>
void keyed_erase(hash_t<V, K> &receiver, const Input &key) {
	(void)receiver.remove(keyed_required_key<K>(key));
}

inline void keyed_erase(mixed_t &receiver, const mixed_t &key) { (void)receiver.remove(key); }

inline void keyed_erase(detail::dynamic_value &receiver, const mixed_t &key) {
	(void)receiver.get_table().remove(key);
}

} // namespace scpp
