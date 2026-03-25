#pragma once

#include "scpp/detail.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/value_p.hpp"
#include "scpp/weak_p.hpp"

namespace scpp {

// Default managed creation helper.
//
// Enforces:
// - allocation policy goes through runtime helpers instead of raw new
// - current v1 policy lowers create() to shared ownership
// - future ownership policy changes can be centralized here
template <typename T, typename... TArgs>
// Constructs an owned runtime handle from forwarded constructor arguments.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
shared_p<T> create(TArgs &&...args) {
	return shared_p<T>(std::make_shared<T>(std::forward<TArgs>(args)...));
}

// Explicit shared creation helper.
// Use this when shared ownership is intended by the source semantics.
template <typename T, typename... TArgs>
// Implements `shared` as part of the runtime surface consumed by generated Simple C++ code.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
shared_p<T> shared(TArgs &&...args) {
	return shared_p<T>(std::make_shared<T>(std::forward<TArgs>(args)...));
}

// Explicit unique creation helper.
// Use this when single ownership is required.
template <typename T, typename... TArgs>
// Implements `unique` as part of the runtime surface consumed by generated Simple C++ code.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
unique_p<T> unique(TArgs &&...args) {
	return unique_p<T>(std::make_unique<T>(std::forward<TArgs>(args)...));
}

// Weak reference derivation helper.
// This helper must not allocate and can only be created from shared ownership.
template <typename T>
// Implements `weak` as part of the runtime surface consumed by generated Simple C++ code.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
weak_p<T> weak(const shared_p<T> &value) {
	return weak_p<T>(value);
}

// Explicit inline value-construction helper.
//
// This is the opt-in path for generated or hand-written code that wants inline
// storage semantics instead of heap-backed object handles.
template <typename T, typename... TArgs>
value_p<T> value(TArgs &&...args) {
	return value_p<T>(std::forward<TArgs>(args)...);
}

} // namespace scpp
