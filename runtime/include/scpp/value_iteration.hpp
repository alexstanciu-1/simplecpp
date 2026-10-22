#pragma once

#include "scpp/foreach.hpp"
#include "scpp/dynamic_t.hpp"
#include "scpp/support/dynamic_value.hpp"
#include <stdexcept>

namespace scpp {

// Direct vector/hash traversal preserves remaining entries before structural writes.
// Boxed-source bindings still need replacement/COW integration below.
template <typename T>
auto value_iteration(const vector_t<T> &source) {
	return source.value_range();
}

template <typename V, typename K>
auto value_iteration(const hash_t<V, K> &source) {
	return source.value_range();
}

inline auto value_iteration(const mixed_t &source) {
	if (source.kind() != mixed_t::kind_t::table_v
		&& source.kind() != mixed_t::kind_t::shared_table_v)
		throw std::runtime_error("foreach: mixed requires a table; cross-family and weak iteration are deferred");
	const auto *table = source.table_if();
	if (!table) throw std::runtime_error("foreach: absent mixed table");
	return foreach_range(*table);
}

inline auto value_iteration(const detail::dynamic_value &source) {
	return detail::const_entry_range(source.get_table());
}

} // namespace scpp
