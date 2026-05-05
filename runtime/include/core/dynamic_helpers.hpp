#pragma once

#include "scpp/dynamic_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/mixed_t.hpp"
#include <memory>

namespace scpp {

// Creates a shared dynamic-object carrier by copying one hash payload into shared storage.
// How: dynamic_t stays semantically distinct even though v1 storage is backed by hash_t<mixed_t>.
template <typename T_VALUE, typename T_KEY>
inline dynamic_t<T_VALUE, T_KEY> to_dynamic(const hash_t<T_VALUE, T_KEY> &value) {
	return dynamic_t<T_VALUE, T_KEY>(std::make_shared<hash_t<T_VALUE, T_KEY>>(value));
}

// Materializes one dynamic-object payload into a plain hash copy.
// How: the copy is explicit so array/hash semantics are not implied by shared dynamic storage.
template <typename T_VALUE, typename T_KEY>
inline hash_t<T_VALUE, T_KEY> to_hash(const dynamic_t<T_VALUE, T_KEY> &value) {
	if (!static_cast<bool>(value)) {
		return hash_t<T_VALUE, T_KEY>{};
	}
	return *value;
}

} // namespace scpp
