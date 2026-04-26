#pragma once

#include "lang/php/support/php_common.hpp"

namespace scpp {

// Implements the lowered unset helper for the zero-argument case.
// How: generated code may emit empty unset expansions, which are semantic no-ops.
inline void unset() {
}

namespace detail {

// Deleted fallback used to keep unset semantics explicit at the runtime boundary.
// How: unsupported/custom types fail at compile time instead of silently inventing semantics.
template <typename T>
inline void apply_unset(T &) = delete;

// Deleted keyed-unset fallback used to keep unsupported keyed targets on the
// compile-rejected side after lowering succeeds.
template <typename TBase, typename TKey>
inline void apply_unset_keyed(TBase &, const TKey &) = delete;

// Implements one-value unset semantics used by the variadic unset helper.
// How: nullable wrappers drop back to the empty state immediately.
template <typename T>
inline void apply_unset(nullable<T> &value) {
	value.reset();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: shared ownership wrappers release the current managed object immediately.
template <typename T>
inline void apply_unset(shared_p<T> &value) {
	value.reset();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: unique ownership wrappers release the current managed object immediately.
template <typename T>
inline void apply_unset(unique_p<T> &value) {
	value.reset();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: weak wrappers forget the current observation target immediately.
template <typename T>
inline void apply_unset(weak_p<T> &value) {
	value.reset();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: the wrapped string owns its storage and clears it through the dedicated runtime hook.
inline void apply_unset(string_t &value) {
	value._unset_();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: the wrapped vector owns its storage and clears it through the dedicated runtime hook.
template <typename T>
inline void apply_unset(vector_t<T> &value) {
	value._unset_();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: integer wrappers reset to the runtime zero state.
inline void apply_unset(int_t &value) {
	value = int_t();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: floating-point wrappers reset to the runtime zero state.
inline void apply_unset(float_t &value) {
	value = float_t();
}

// Implements one-value unset semantics used by the variadic unset helper.
// How: boolean wrappers reset to false.
inline void apply_unset(bool_t &value) {
	value = bool_t();
}

} // namespace detail

// Implements the lowered unset helper for the currently supported mutable wrappers.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
template <typename... Args>
inline void unset(Args &...args) {
	(detail::apply_unset(args), ...);
}

// Implements keyed unset lowering for supported associative containers.
// How: hash-backed targets erase by key, while unsupported keyed targets stay
// compile-rejected through the deleted detail fallback.
template <typename TValue, typename TKey>
inline void unset_keyed(hash_t<TValue> &target, const TKey &key) {
	target.remove(key);
}

template <typename TBase, typename TKey>
inline void unset_keyed(TBase &target, const TKey &key) {
	detail::apply_unset_keyed(target, key);
}

} // namespace scpp
