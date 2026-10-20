#pragma once

#include "lang/php/support/php_common.hpp"

namespace scpp {

// Temporary lifetime-audit helper.
// How: exposes the visible strong-owner count for shared/weak wrappers so tests can prove whether a hidden strong alias still exists.
template <typename T>
[[nodiscard]] inline long debug_use_count(const shared_p<T> &value) {
	return value.debug_use_count();
}

template <typename T>
[[nodiscard]] inline long debug_use_count(const weak_p<T> &value) {
	return value.debug_use_count();
}

} // namespace scpp
