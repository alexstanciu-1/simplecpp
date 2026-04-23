#pragma once

#include "lang/php/support/php_common.hpp"

namespace scpp {

// Implements weak reference creation for shared-owned objects.
// How: weak observers are modeled directly with weak_p so generated code does not need a second wrapper family.
template <typename T>
inline weak_p<T> weakref(const shared_p<T> &value) {
	return weak_p<T>(value);
}

// Implements weak reference readback.
// How: locking a weak observer yields a shared handle, and empty state is represented by a null shared_p sentinel.
template <typename T>
inline shared_p<T> weakref_get(const weak_p<T> &value) {
	return value.lock();
}

} // namespace scpp
