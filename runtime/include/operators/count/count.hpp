#pragma once

#include "lang/php/support/php_common.hpp"

namespace scpp {

namespace detail {

// Formalizes the runtime countable contract for hash-compatible mixed_t carriers.
// How: only one unwrap step is performed, so nested mixed_t values that themselves hold hashes are handled by the caller explicitly without accidental recursive unwrapping.
inline const hash_t<mixed_t> &countable_hash_or_throw(const mixed_t &value, const char *operation) {
	if (const auto *table = value.table_if()) {
		return *table;
	}
	if (const auto *shared_table = value.shared_table_if()) {
		return *shared_table->get();
	}
	if (const auto *weak_table = value.weak_table_if()) {
		auto locked = weak_table->lock();
		if (static_cast<bool>(locked)) {
			return *locked;
		}
		throw std::runtime_error(std::string("php::") + operation + "(mixed_t) expects live hash-compatible mixed_t");
	}
	throw std::runtime_error(std::string("php::") + operation + "(mixed_t) expects hash-compatible mixed_t");
}

} // namespace detail

// Implements count() for the currently supported vector wrapper subset.
// How: returns the runtime vector size widened into the standard int_t<> wrapper used by generated code.
template <typename T>
inline int_t<> count(const vector_t<T> &value) {
	return int_t<>(static_cast<std::int64_t>(value.size()));
}

// Implements count() for any concrete hash_t payload.
// How: count() is a cardinality query on the wrapper itself, so the element payload type does not affect the logical size.
template <typename T, typename K>
inline int_t<> count(const hash_t<T, K> &value) {
	return int_t<>(static_cast<std::int64_t>(value.size()));
}

// Implements count() for dynamic values that currently hold an array/hash payload.
// How: generated code may still keep arrays inside mixed_t, so count() unwraps exactly one dynamic layer and rejects non-countable payloads explicitly.
inline int_t<> count(const mixed_t &value) {
	return count(detail::countable_hash_or_throw(value, "count"));
}

// Implements count() for the first-class shared dynamic-object handle surface.
// How: dynamic_t<> remains a thin shared handle around the existing hash-backed dynamic storage, so count() forwards directly to the pointed payload.
inline int_t<> count(const dynamic_t<> &value) {
	if (!static_cast<bool>(value)) {
		throw std::runtime_error("php::count(dynamic_t<>) expects a present dynamic handle");
	}
	return count(*value);
}

} // namespace scpp
