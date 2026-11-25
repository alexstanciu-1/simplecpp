#pragma once

#include "scpp/vector_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/dynamic_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/support/dynamic_value.hpp"
#include <stdexcept>

namespace scpp {

// Copy out before any receiver temporary dies. No interior reference escapes.
template <typename T>
T checked_index_read(const vector_t<T> &receiver, const int_t<> &key) {
	return receiver.at(key);
}

template <typename V, typename K>
V checked_index_read(const hash_t<V, K> &receiver, const K &key) {
	return receiver.at(key);
}

inline void require_index_key(const mixed_t &key) {
	if (key.kind() != mixed_t::kind_t::int_v && key.kind() != mixed_t::kind_t::string_v)
		throw std::runtime_error("indexed read: key must be int or string");
}

inline detail::dynamic_value null_index_read(const detail::dynamic_value &receiver, const mixed_t &key) {
	require_index_key(key);
	if (receiver.is_null().native_value()) return detail::dynamic_value{};
	return receiver.get_table().read_or_default(key);
}

// Keep the permissive legacy get() API unchanged; v0.2 rejects wrong receiver kinds.
inline mixed_t null_index_read(const mixed_t &receiver, const mixed_t &key) {
	require_index_key(key);
	switch (receiver.kind()) {
	case mixed_t::kind_t::null_v:
		return mixed_t{null};
	case mixed_t::kind_t::weak_table_v: {
		auto held = receiver.weak_table_if()->lock();
		return held ? held->_find_val(key) : mixed_t{null};
	}
	case mixed_t::kind_t::table_v:
	case mixed_t::kind_t::shared_table_v: {
		const auto *table = receiver.table_if();
		return table ? table->_find_val(key) : mixed_t{null};
	}
	default:
		throw std::runtime_error("indexed read: receiver is not a table");
	}
}

} // namespace scpp
