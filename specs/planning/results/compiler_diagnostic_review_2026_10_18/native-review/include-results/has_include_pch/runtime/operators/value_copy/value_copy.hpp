#pragma once

#include "lang/php/support/php_common.hpp"

namespace scpp {

// Implements by-value copy semantics for mixed runtime values.
// How: scalars and strings already copy by value through mixed_t::clone, while nested arrays detach by copying the underlying table into a fresh unique-owned mixed_t.
inline mixed_t value_copy(const mixed_t &value) {
	if (value.table_if() != nullptr) {
		return mixed_t{unique<hash_t<mixed_t>>(*value.table_if())};
	}
	if (value.shared_table_if() != nullptr) {
		return mixed_t{unique<hash_t<mixed_t>>(*value.shared_table_if()->get())};
	}
	if (value.weak_table_if() != nullptr) {
		auto locked = value.weak_table_if()->lock();
		if (static_cast<bool>(locked)) {
			return mixed_t{unique<hash_t<mixed_t>>(*locked.get())};
		}
		return mixed_t{null_t{}};
	}
	return value.clone();
}

} // namespace scpp
