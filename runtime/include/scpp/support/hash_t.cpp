#include "scpp/hash_t.hpp"
#include "scpp/mixed_t.hpp"

namespace scpp {

// Non-inserting dynamic read helpers remain intentionally null-returning in v1.
// Typed-destination bridging layered on top of those reads is governed by
// specs/dynamic_types.md sections 1.2 and 1.3, not by hash_t alone.

mixed_t hash_t<mixed_t, mixed_t>::_find_val(const int_t<> &key) const {
	auto [f, p] = find_int(hash_detail::dyn_keys::pack(key));
	if (!f) return mixed_t{null_t{}};
	return p->clone();
}

mixed_t hash_t<mixed_t, mixed_t>::_find_val(const string_t &key) const {
	auto [f, p] = find_int(hash_detail::dyn_keys::pack(key));
	if (!f) return mixed_t{null_t{}};
	return p->clone();
}

mixed_t hash_t<mixed_t, mixed_t>::_find_val(const mixed_t &key) const {
	auto [f, p] = find_int(hash_detail::dyn_keys::pack(key));
	if (!f) return mixed_t{null_t{}};
	return p->clone();
}

// Explicit instantiations one per whitelisted T_VALUE.
// Keeps template code compiled once, not per translation unit.
template class hash_t<null_t>;
template class hash_t<bool_t>;
template class hash_t<int_t<>>;
template class hash_t<float_t>;
template class hash_t<string_t>;
template class hash_t<mixed_t, mixed_t>;

// Table-of-table ownership variants.
template class hash_t<std::shared_ptr<hash_t<mixed_t>>>;
template class hash_t<std::unique_ptr<hash_t<mixed_t>>>;
template class hash_t<std::weak_ptr<hash_t<mixed_t>>>;

} // namespace scpp
