#pragma once

#include "scpp/foreach.hpp"
#include "scpp/dynamic_t.hpp"
#include <stdexcept>

namespace scpp {

// Non-mutating value iteration. The caller retains the source for the whole loop.
// Mutation/replacement (including aliases) is deferred, not snapshot or live semantics.
template <typename T>
auto value_iteration(const vector_t<T> &source) {
	return foreach_range(source);
}

template <typename V, typename K>
auto value_iteration(const hash_t<V, K> &source) {
	return foreach_range(source);
}

inline auto value_iteration(const mixed_t &source) {
	if (source.kind() != mixed_t::kind_t::table_v
		&& source.kind() != mixed_t::kind_t::shared_table_v)
		throw std::runtime_error("foreach: mixed requires a table; cross-family and weak iteration are deferred");
	const auto *table = source.table_if();
	if (!table) throw std::runtime_error("foreach: absent mixed table");
	return foreach_range(*table);
}

inline auto value_iteration(const dynamic_t<> &source) {
	if (!source.get()) throw std::runtime_error("foreach: absent dynamic handle");
	return foreach_range(static_cast<const hash_t<mixed_t>&>(*source));
}

} // namespace scpp
