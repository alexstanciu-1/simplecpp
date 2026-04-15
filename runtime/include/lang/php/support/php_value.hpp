#pragma once

#include "lang/php/support/php_common.hpp"

namespace scpp::php {

// Implements PHP strict identity for two null sentinels.
// How: strict identity treats identical null sentinels as equal without consulting wrapper operator overloads.
inline bool_t identical(null_t, null_t) {
	return bool_t(true);
}

// Implements PHP strict identity between null and nullable<T> when the nullable is empty.
// How: this is the one cross-type exception to the exact-type identity rule currently adopted by the runtime.
template <typename T>
inline bool_t identical(null_t, const nullable<T> &right) {
	return bool_t(!right.has_value().native_value());
}

// Implements PHP strict identity between nullable<T> and null when the nullable is empty.
// How: this is the symmetric form of the null-vs-nullable exception.
template <typename T>
inline bool_t identical(const nullable<T> &left, null_t) {
	return bool_t(!left.has_value().native_value());
}

// Implements PHP strict identity for two nullable values of the same exact type.
// How: empty state matches empty state; present values recurse into the same identity helper for the contained exact type.
template <typename T>
inline bool_t identical(const nullable<T> &left, const nullable<T> &right) {
	if (!left.has_value().native_value() && !right.has_value().native_value()) {
		return bool_t(true);
	}
	if (left.has_value().native_value() != right.has_value().native_value()) {
		return bool_t(false);
	}
	return identical(left.value(), right.value());
}

// Implements PHP strict identity between null and shared ownership wrappers.
// How: an empty shared handle represents PHP null at the runtime comparison layer.
template <typename T>
inline bool_t identical(null_t, const shared_p<T> &right) {
	return bool_t(!right.has_value().native_value());
}

// Implements PHP strict identity between shared ownership wrappers and null.
// How: an empty shared handle represents PHP null at the runtime comparison layer.
template <typename T>
inline bool_t identical(const shared_p<T> &left, null_t) {
	return bool_t(!left.has_value().native_value());
}

// Implements PHP strict identity between null and unique ownership wrappers.
// How: an empty unique handle represents PHP null at the runtime comparison layer.
template <typename T>
inline bool_t identical(null_t, const unique_p<T> &right) {
	return bool_t(!right.has_value().native_value());
}

// Implements PHP strict identity between unique ownership wrappers and null.
// How: an empty unique handle represents PHP null at the runtime comparison layer.
template <typename T>
inline bool_t identical(const unique_p<T> &left, null_t) {
	return bool_t(!left.has_value().native_value());
}

// Implements PHP strict identity for shared ownership wrappers using object identity.
// How: aliases are identical only when they point at the exact same managed object.
template <typename T>
inline bool_t identical(const shared_p<T> &left, const shared_p<T> &right) {
	return bool_t(left.get() == right.get());
}

// Implements PHP strict identity between a raw object pointer and a shared wrapper.
// How: enum instance methods compare $this against canonical shared case handles by underlying address.
template <typename T>
inline bool_t identical(const T *left, const shared_p<T> &right) {
	return bool_t(left == right.get());
}

// Implements PHP strict identity between a shared wrapper and a raw object pointer.
// How: enum instance methods compare canonical shared case handles against $this by underlying address.
template <typename T>
inline bool_t identical(const shared_p<T> &left, const T *right) {
	return bool_t(left.get() == right);
}

// Implements PHP strict identity for unique ownership wrappers using object identity.
// How: the comparison observes the managed object address rather than any pointed-to value.
template <typename T>
inline bool_t identical(const unique_p<T> &left, const unique_p<T> &right) {
	return bool_t(left.get() == right.get());
}

// Implements PHP strict identity between a dynamic mixed_t and null.
// How: mixed_t is a tagged PHP value container, so strict identity must inspect the active kind rather than reject the comparison as a generic cross-type mismatch.
inline bool_t identical(const mixed_t &left, null_t) {
	return bool_t(left.kind() == mixed_t::kind_t::null_v);
}

// Implements PHP strict identity between null and a dynamic mixed_t.
// How: this is the symmetric form of the mixed_t-null identity rule so overload resolution never falls through to the generic cross-type false path.
inline bool_t identical(null_t, const mixed_t &right) {
	return bool_t(right.kind() == mixed_t::kind_t::null_v);
}

// Implements PHP strict identity for same-type runtime values not needing special object/null handling.
// How: the helper keeps strict comparison in the PHP helper layer and delegates exact-type value equality to the runtime operator surface.
template <typename T>
inline bool_t identical(const T &left, const T &right) {
	return bool_t(left == right);
}

// Implements PHP strict identity for differing runtime value categories.
// How: the helper returns false because strict identity currently requires exact type equality except for null vs nullable<T>.
template <typename Left, typename Right>
requires (!std::is_same_v<std::remove_cvref_t<Left>, std::remove_cvref_t<Right>>)
inline bool_t identical(const Left &, const Right &) {
	return bool_t(false);
}

// Implements PHP strict non-identity as the inverse of the strict identity helper.
// How: one source of truth avoids drift between special-case identical overloads and their negated form.
template <typename Left, typename Right>
inline bool_t not_identical(const Left &left, const Right &right) {
	return !identical(left, right);
}

// Implements PHP-style concatenation assignment for wrapped strings.
// How: the helper mutates the left-hand side in place through string_t::append and returns the updated wrapper by reference.
inline string_t &concat_assign(string_t &left, const string_t &right) {
	left.append(right);
	return left;
}

namespace detail {

enum class probe_state : std::uint8_t {
	invalid,
	missing,
	present_null,
	present_value,
};

template <typename T>
struct probe_result final {
	probe_state state;
	const T *value;
};

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

// Centralizes the narrowed Prism++ emptiness contract for plain one-value checks.
// How: this stays intentionally smaller than PHP falsiness; only null, empty string, and empty countables are empty.
inline bool_t empty_scalar(null_t) {
	return bool_t(true);
}

inline bool_t empty_scalar(nullopt_t) {
	return bool_t(true);
}

inline bool_t empty_scalar(nullptr_t) {
	return bool_t(true);
}

inline bool_t empty_scalar(const string_t &value) {
	return value.empty();
}

inline bool_t empty_scalar(const bool_t &) {
	return bool_t(false);
}

inline bool_t empty_scalar(const int_t &) {
	return bool_t(false);
}

inline bool_t empty_scalar(const float_t &) {
	return bool_t(false);
}

template <typename T>
inline bool_t empty_scalar(const nullable<T> &value) {
	if (!value.has_value().native_value()) {
		return bool_t(true);
	}
	return empty_scalar(value.value());
}

template <typename T>
inline bool_t empty_scalar(const result_or_false<T> &value) {
	if (!value.has_value().native_value()) {
		return bool_t(true);
	}
	return empty_scalar(value.value());
}

template <typename T>
inline bool_t empty_scalar(const result_or_bool<T> &value) {
	if (!value.has_value().native_value()) {
		return bool_t(!value.is_true().native_value());
	}
	return empty_scalar(value.value());
}

template <typename T>
inline bool_t empty_scalar(const result<T> &value) {
	if (!value.has_value().native_value()) {
		return bool_t(true);
	}
	return empty_scalar(value.value());
}

template <typename T>
inline bool_t empty_scalar(const shared_p<T> &value) {
	return bool_t(!value.has_value().native_value());
}

template <typename T>
inline bool_t empty_scalar(const unique_p<T> &value) {
	return bool_t(!value.has_value().native_value());
}

template <typename T>
inline bool_t empty_scalar(const weak_p<T> &value) {
	return bool_t(value.expired().native_value());
}

template <typename T>
requires (
	!std::is_same_v<std::remove_cvref_t<T>, null_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, nullopt_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, nullptr_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, mixed_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, string_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, bool_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, int_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, float_t>
	&& !requires (const std::remove_cvref_t<T> &value) {
		value.has_value();
	}
	&& !requires (const std::remove_cvref_t<T> &value) {
		value.expired();
	}
)
inline bool_t empty_scalar(T &&) {
	return bool_t(false);
}

// Centralizes the narrowed Prism++ probe contract for mixed_t values.
// How: missing and invalid are only possible for keyed probes; one-value probes classify null vs non-null without exposing storage internals.
inline probe_result<mixed_t> probe_value(const mixed_t &value) {
	if (value.kind() == mixed_t::kind_t::null_v) {
		return {probe_state::present_null, &value};
	}
	return {probe_state::present_value, &value};
}

template <typename T>
inline probe_result<T> probe_value(const T &value) {
	return {probe_state::present_value, &value};
}

inline probe_result<null_t> probe_value(null_t) {
	return {probe_state::present_null, nullptr};
}

inline probe_result<nullopt_t> probe_value(nullopt_t) {
	return {probe_state::present_null, nullptr};
}

inline probe_result<nullptr_t> probe_value(nullptr_t) {
	return {probe_state::present_null, nullptr};
}

template <typename T>
inline probe_result<nullable<T>> probe_value(const nullable<T> &value) {
	if (!value.has_value().native_value()) {
		return {probe_state::present_null, &value};
	}
	return {probe_state::present_value, &value};
}

template <typename T>
inline probe_result<result_or_false<T>> probe_value(const result_or_false<T> &value) {
	if (!value.has_value().native_value()) {
		return {probe_state::present_null, &value};
	}
	return {probe_state::present_value, &value};
}

template <typename T>
inline probe_result<result_or_bool<T>> probe_value(const result_or_bool<T> &value) {
	if (!value.has_value().native_value()) {
		return value.is_true().native_value() ? probe_result<result_or_bool<T>>{probe_state::present_value, &value} : probe_result<result_or_bool<T>>{probe_state::present_null, &value};
	}
	return {probe_state::present_value, &value};
}

template <typename T>
inline probe_result<result<T>> probe_value(const result<T> &value) {
	if (!value.has_value().native_value()) {
		return {probe_state::present_null, &value};
	}
	return {probe_state::present_value, &value};
}

template <typename T>
inline probe_result<shared_p<T>> probe_value(const shared_p<T> &value) {
	if (!value.has_value().native_value()) {
		return {probe_state::present_null, &value};
	}
	return {probe_state::present_value, &value};
}

template <typename T>
inline probe_result<unique_p<T>> probe_value(const unique_p<T> &value) {
	if (!value.has_value().native_value()) {
		return {probe_state::present_null, &value};
	}
	return {probe_state::present_value, &value};
}

template <typename T>
inline probe_result<weak_p<T>> probe_value(const weak_p<T> &value) {
	if (value.expired().native_value()) {
		return {probe_state::present_null, &value};
	}
	return {probe_state::present_value, &value};
}

inline bool_t isset_from_probe(probe_state state) {
	return bool_t(state == probe_state::present_value);
}

inline bool_t empty_from_probe(const probe_state state, const mixed_t *value) {
	if (state == probe_state::invalid || state == probe_state::missing || state == probe_state::present_null) {
		return bool_t(true);
	}
	if (value == nullptr) {
		return bool_t(false);
	}
	if (const auto *string_value = value->string_if()) {
		return string_value->empty();
	}
	if (const auto *table_value = value->table_if()) {
		return table_value->empty();
	}
	if (const auto *shared_table_value = value->shared_table_if()) {
		return shared_table_value->get()->empty();
	}
	if (const auto *weak_table_value = value->weak_table_if()) {
		auto locked = weak_table_value->lock();
		if (static_cast<bool>(locked)) {
			return locked.get()->empty();
		}
		return bool_t(true);
	}
	return bool_t(false);
}

// Centralizes integer-key normalization for countable helper overloads.
// How: callers can accept either native ints or int_t without duplicating negative-index handling logic.
inline bool vector_has_index(const std::size_t size, const int_t &key) {
	const auto native = key.native_value();
	if (native < 0) {
		return false;
	}
	return static_cast<std::size_t>(native) < size;
}

inline bool vector_has_index(const std::size_t size, const int key) {
	return key >= 0 && static_cast<std::size_t>(key) < size;
}

template <typename T>
struct is_countable_lookup_target : std::false_type {};

template <typename T>
struct is_countable_lookup_target<vector_t<T>> : std::true_type {};

template <typename T>
struct is_countable_lookup_target<hash_t<T>> : std::true_type {};

template <>
struct is_countable_lookup_target<mixed_t> : std::true_type {};

} // namespace detail

// Implements PHP count() for the currently supported vector wrapper subset.
// How: returns the runtime vector size widened into the standard int_t wrapper used by generated code.
template <typename T>
inline int_t count(const vector_t<T> &value) {
	return int_t(static_cast<std::int64_t>(value.size()));
}

// Implements PHP count() for any concrete hash_t payload.
// How: count() is a cardinality query on the wrapper itself, so the element payload type does not affect the logical size.
template <typename T>
inline int_t count(const hash_t<T> &value) {
	return int_t(static_cast<std::int64_t>(value.size()));
}

// Implements PHP count() for dynamic values that currently hold an array/hash payload.
// How: generated code may still keep arrays inside mixed_t, so count() unwraps exactly one dynamic layer and rejects non-countable payloads explicitly.
inline int_t count(const mixed_t &value) {
	return count(detail::countable_hash_or_throw(value, "count"));
}

// Implements PHP empty() for the current vector wrapper subset.
// How: emptiness is derived from the stable wrapper cardinality instead of exposing STL semantics directly to generated code.
template <typename T>
inline bool_t empty(const vector_t<T> &value) {
	return value.empty();
}

// Implements PHP empty() for any concrete hash_t payload.
// How: emptiness is derived from the wrapper cardinality, independent of payload type.
template <typename T>
inline bool_t empty(const hash_t<T> &value) {
	return value.empty();
}

// Implements the narrowed Prism++ empty() contract for one-value scalar and wrapper inputs.
// How: only null, empty string, and empty countables are empty; numeric zero, false, and "0" are intentionally not empty.
inline bool_t empty(null_t value) {
	return detail::empty_scalar(value);
}

inline bool_t empty(nullopt_t value) {
	return detail::empty_scalar(value);
}

inline bool_t empty(nullptr_t value) {
	return detail::empty_scalar(value);
}

inline bool_t empty(const bool_t &value) {
	return detail::empty_scalar(value);
}

inline bool_t empty(const int_t &value) {
	return detail::empty_scalar(value);
}

inline bool_t empty(const float_t &value) {
	return detail::empty_scalar(value);
}

inline bool_t empty(const string_t &value) {
	return detail::empty_scalar(value);
}

template <typename T>
inline bool_t empty(const nullable<T> &value) {
	return detail::empty_scalar(value);
}

template <typename T>
inline bool_t empty(const result_or_false<T> &value) {
	return detail::empty_scalar(value);
}

template <typename T>
inline bool_t empty(const result_or_bool<T> &value) {
	return detail::empty_scalar(value);
}

template <typename T>
inline bool_t empty(const result<T> &value) {
	return detail::empty_scalar(value);
}

template <typename T>
inline bool_t empty(const shared_p<T> &value) {
	return detail::empty_scalar(value);
}

template <typename T>
inline bool_t empty(const unique_p<T> &value) {
	return detail::empty_scalar(value);
}

template <typename T>
inline bool_t empty(const weak_p<T> &value) {
	return detail::empty_scalar(value);
}

// Implements PHP empty() for dynamic values under the narrowed Prism++ contract.
// How: mixed_t no longer reuses the strict countable contract; it treats missing/null/string-empty/empty-countable as empty and everything else as non-empty.
inline bool_t empty(const mixed_t &value) {
	const auto probe = detail::probe_value(value);
	return detail::empty_from_probe(probe.state, probe.value);
}

// Implements container-key isset() for vector wrappers.
// How: index existence is reduced to a bounds check, then mixed_t payloads get the extra null-sensitive check required by Prism++.
template <typename T>
inline bool_t isset(const vector_t<T> &value, const int_t &key) {
	if (!detail::vector_has_index(value.size(), key)) {
		return bool_t(false);
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		return detail::isset_from_probe(detail::probe_value(value.at(key)).state);
	}
	return bool_t(true);
}

template <typename T>
inline bool_t isset(const vector_t<T> &value, const int key) {
	return isset(value, int_t{static_cast<std::int64_t>(key)});
}

// Implements container-key isset() for hash wrappers.
// How: mixed_t payloads stay null-sensitive, while typed payloads treat key presence as value presence because they cannot represent PHP null by default construction.
template <typename T>
inline bool_t isset(const hash_t<T> &value, const int_t &key) {
	if (!value.has(key).native_value()) {
		return bool_t(false);
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		return detail::isset_from_probe(detail::probe_value(value.at(key)).state);
	}
	return bool_t(true);
}

template <typename T>
inline bool_t isset(const hash_t<T> &value, const string_t &key) {
	if (!value.has(key).native_value()) {
		return bool_t(false);
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		return detail::isset_from_probe(detail::probe_value(value.at(key)).state);
	}
	return bool_t(true);
}

template <typename T>
inline bool_t isset(const hash_t<T> &value, const char *key) {
	return isset(value, string_t{key});
}

template <typename T>
inline bool_t isset(const hash_t<T> &value, const int key) {
	return isset(value, int_t{static_cast<std::int64_t>(key)});
}

// Implements container-key empty() for vector wrappers.
// How: missing or invalid indices are empty, and mixed_t payloads reuse the narrowed one-value empty contract without mutating the container.
template <typename T>
inline bool_t empty(const vector_t<T> &value, const int_t &key) {
	if (!detail::vector_has_index(value.size(), key)) {
		return bool_t(true);
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		const auto probe = detail::probe_value(value.at(key));
		return detail::empty_from_probe(probe.state, probe.value);
	}
	return empty(value.at(key));
}

template <typename T>
inline bool_t empty(const vector_t<T> &value, const int key) {
	return empty(value, int_t{static_cast<std::int64_t>(key)});
}

// Implements container-key empty() for hash wrappers.
// How: missing keys are empty, mixed_t payloads keep null-sensitive and narrowed-string/countable behavior, and typed payloads defer to one-value empty() only when the key exists.
template <typename T>
inline bool_t empty(const hash_t<T> &value, const int_t &key) {
	if (!value.has(key).native_value()) {
		return bool_t(true);
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		const auto probe = detail::probe_value(value.at(key));
		return detail::empty_from_probe(probe.state, probe.value);
	}
	return empty(value.at(key));
}

template <typename T>
inline bool_t empty(const hash_t<T> &value, const string_t &key) {
	if (!value.has(key).native_value()) {
		return bool_t(true);
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		const auto probe = detail::probe_value(value.at(key));
		return detail::empty_from_probe(probe.state, probe.value);
	}
	return empty(value.at(key));
}

template <typename T>
inline bool_t empty(const hash_t<T> &value, const char *key) {
	return empty(value, string_t{key});
}

template <typename T>
inline bool_t empty(const hash_t<T> &value, const int key) {
	return empty(value, int_t{static_cast<std::int64_t>(key)});
}

// Implements container-key isset() for hash-compatible mixed_t carriers.
// How: invalid key kinds and non-countable bases are non-throwing here by policy, while present-null still resolves to false through the shared probe rules.
inline bool_t isset(const mixed_t &value, const mixed_t &key) {
	if (key.kind() == mixed_t::kind_t::int_v) {
		return isset(value, key.int_value());
	}
	if (key.kind() == mixed_t::kind_t::string_v) {
		return isset(value, *key.string_if());
	}
	return bool_t(false);
}

inline bool_t isset(const mixed_t &value, const int_t &key) {
	const auto *table = value.table_if();
	if (table == nullptr) {
		if (const auto *shared_table = value.shared_table_if()) {
			return isset(*shared_table->get(), key);
		}
		if (const auto *weak_table = value.weak_table_if()) {
			auto locked = weak_table->lock();
			if (static_cast<bool>(locked)) {
				return isset(*locked.get(), key);
			}
		}
		return bool_t(false);
	}
	return isset(*table, key);
}

inline bool_t isset(const mixed_t &value, const string_t &key) {
	const auto *table = value.table_if();
	if (table == nullptr) {
		if (const auto *shared_table = value.shared_table_if()) {
			return isset(*shared_table->get(), key);
		}
		if (const auto *weak_table = value.weak_table_if()) {
			auto locked = weak_table->lock();
			if (static_cast<bool>(locked)) {
				return isset(*locked.get(), key);
			}
		}
		return bool_t(false);
	}
	return isset(*table, key);
}

inline bool_t isset(const mixed_t &value, const char *key) {
	return isset(value, string_t{key});
}

inline bool_t isset(const mixed_t &value, const int key) {
	return isset(value, int_t{static_cast<std::int64_t>(key)});
}

// Implements container-key empty() for hash-compatible mixed_t carriers.
// How: invalid key kinds and non-countable bases are empty by policy, while valid lookups stay non-mutating and reuse the narrowed one-value empty contract.
inline bool_t empty(const mixed_t &value, const mixed_t &key) {
	if (key.kind() == mixed_t::kind_t::int_v) {
		return empty(value, key.int_value());
	}
	if (key.kind() == mixed_t::kind_t::string_v) {
		return empty(value, *key.string_if());
	}
	return bool_t(true);
}

inline bool_t empty(const mixed_t &value, const int_t &key) {
	const auto *table = value.table_if();
	if (table == nullptr) {
		if (const auto *shared_table = value.shared_table_if()) {
			return empty(*shared_table->get(), key);
		}
		if (const auto *weak_table = value.weak_table_if()) {
			auto locked = weak_table->lock();
			if (static_cast<bool>(locked)) {
				return empty(*locked.get(), key);
			}
		}
		return bool_t(true);
	}
	return empty(*table, key);
}

inline bool_t empty(const mixed_t &value, const string_t &key) {
	const auto *table = value.table_if();
	if (table == nullptr) {
		if (const auto *shared_table = value.shared_table_if()) {
			return empty(*shared_table->get(), key);
		}
		if (const auto *weak_table = value.weak_table_if()) {
			auto locked = weak_table->lock();
			if (static_cast<bool>(locked)) {
				return empty(*locked.get(), key);
			}
		}
		return bool_t(true);
	}
	return empty(*table, key);
}

inline bool_t empty(const mixed_t &value, const char *key) {
	return empty(value, string_t{key});
}

inline bool_t empty(const mixed_t &value, const int key) {
	return empty(value, int_t{static_cast<std::int64_t>(key)});
}

using ::scpp::to_dynamic;
using ::scpp::to_hash;

// Implements PHP by-value copy semantics for mixed runtime values.
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


// Implements the lowered isset contract across the currently supported runtime value categories.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset() {
	return bool_t(true);
}

// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(null_t) {
	return bool_t(false);
}

// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(nullopt_t) {
	return bool_t(false);
}

// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(nullptr_t) {
	return bool_t(false);
}

// Implements one-value isset semantics used by the variadic isset helper.
// How: mixed_t must preserve the null-sensitive contract for lowered array/property reads that return a dynamic value.
inline bool_t isset_one(const mixed_t &value) {
	return detail::isset_from_probe(detail::probe_value(value).state);
}

template <typename T>
// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(const nullable<T> &value) {
	return value.has_value();
}

template <typename T>
inline bool_t isset_one(const result_or_false<T> &value) {
	return value.has_value();
}

template <typename T>
inline bool_t isset_one(const result_or_bool<T> &value) {
	return bool_t(value.has_value().native_value() || value.is_true().native_value());
}

template <typename T>
inline bool_t isset_one(const result<T> &value) {
	return value.has_value();
}

template <typename T>
// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(const shared_p<T> &value) {
	return value.has_value();
}

template <typename T>
// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(const unique_p<T> &value) {
	return value.has_value();
}

template <typename T>
// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(const weak_p<T> &value) {
	return bool_t(!value.expired().native_value());
}

template <typename T>
requires (
	!std::is_same_v<std::remove_cvref_t<T>, null_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, nullopt_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, nullptr_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, mixed_t>
	&& !requires (const std::remove_cvref_t<T> &value) {
		value.has_value();
	}
	&& !requires (const std::remove_cvref_t<T> &value) {
		value.expired();
	}
)
// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(T &&) {
	return bool_t(true);
}

template <typename... Args>
requires (
	!(
		sizeof...(Args) == 2
		&& detail::is_countable_lookup_target<std::remove_cvref_t<std::tuple_element_t<0, std::tuple<Args...>>>>::value
	)
)
// Implements the lowered isset contract across the currently supported runtime value categories.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline bool_t isset(Args &&...args) {
	bool result = true;
	((result = result && isset_one(std::forward<Args>(args)).native_value()), ...);
	return bool_t(result);
}


namespace detail {

template <typename T>
struct conditional_nullable_info {
	static constexpr bool value = false;
};

template <typename T>
struct conditional_nullable_info<nullable<T>> {
	static constexpr bool value = true;
	using inner_type = T;
};

template <typename T>
inline constexpr bool conditional_nullable_info_v = conditional_nullable_info<std::remove_cvref_t<T>>::value;

template <typename Left, typename Right>
struct coalesce_result;

template <typename T>
struct coalesce_result<T, T> {
	using type = T;
};

template <typename T, typename Right>
requires (!conditional_nullable_info_v<Right>)
struct coalesce_result<nullable<T>, Right> {
	using type = T;
};

template <typename Then, typename Else>
struct ternary_result;

template <typename T>
struct ternary_result<T, T> {
	using type = T;
};

template <typename T>
struct ternary_result<nullable<T>, T> {
	using type = nullable<T>;
};

template <typename T>
struct ternary_result<T, nullable<T>> {
	using type = nullable<T>;
};

template <typename Value>
inline bool_t ternary_condition_truthy(Value &&value) {
	using value_t = std::remove_cvref_t<Value>;
	if constexpr (conditional_nullable_info_v<value_t>) {
		if (!value.has_value().native_value()) {
			return bool_t(false);
		}
		using inner_t = typename conditional_nullable_info<value_t>::inner_type;
		return bool_t(cast<bool>(cast<inner_t>(value)));
	}
	return bool_t(cast<bool>(std::forward<Value>(value)));
}

template <typename Result, typename Value>
inline Result normalize_ternary_branch(Value &&value) {
	using result_t = std::remove_cvref_t<Result>;
	using value_t = std::remove_cvref_t<Value>;
	if constexpr (std::is_same_v<result_t, value_t>) {
		return std::forward<Value>(value);
	} else if constexpr (
		conditional_nullable_info_v<result_t>
		&& std::is_same_v<typename conditional_nullable_info<result_t>::inner_type, value_t>
	) {
		return result_t(std::forward<Value>(value));
	} else {
		static_assert(::scpp::detail::always_false_v<result_t, value_t>, "unsupported php::ternary_eval branch combination");
	}
}

} // namespace detail

// Implements runtime-directed lowering for PHP null coalescing so code generation can stay structurally simple and type-blind.
// How: the helper evaluates the left operand once, resolves a strict supported result type matrix at compile time, and only evaluates the fallback when needed.
template <typename LeftFn, typename RightFn>
inline auto coalesce_eval(LeftFn &&left_fn, RightFn &&right_fn) {
	auto &&left = left_fn();
	using left_t = std::remove_cvref_t<decltype(left)>;
	using right_t = std::remove_cvref_t<decltype(right_fn())>;
	using result_t = typename detail::coalesce_result<left_t, right_t>::type;
	if (static_cast<bool>(isset(left))) {
		return cast<result_t>(left);
	}
	return cast<result_t>(right_fn());
}

// Implements runtime-directed lowering for PHP ternary / elvis expressions so branch compatibility is enforced consistently in one place.
// How: the helper evaluates the condition once, applies wrapper-aware truthiness for supported inputs, and normalizes supported branch pairs through an explicit compile-time matrix.
template <typename CondFn, typename ThenFn, typename ElseFn>
inline auto ternary_eval(CondFn &&cond_fn, ThenFn &&then_fn, ElseFn &&else_fn) {
	auto &&cond = cond_fn();
	using then_t = std::remove_cvref_t<decltype(then_fn())>;
	using else_t = std::remove_cvref_t<decltype(else_fn())>;
	using result_t = typename detail::ternary_result<then_t, else_t>::type;
	if (static_cast<bool>(detail::ternary_condition_truthy(cond))) {
		return detail::normalize_ternary_branch<result_t>(then_fn());
	}
	return detail::normalize_ternary_branch<result_t>(else_fn());
}

// Implements the lowered unset helper for the currently supported mutable wrappers.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void unset() {
}

namespace detail {

// Deleted fallback used to keep unset semantics explicit at the runtime boundary.
// How: unsupported/custom types fail at compile time instead of silently inventing semantics.
template <typename T>
inline void apply_unset(T &) = delete;

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


// Reads one numeric memory field from /proc/self/status when available.
// How: Linux exposes resident and peak resident process memory in kilobytes through VmRSS and VmHWM.
[[nodiscard]] inline std::int64_t read_proc_status_kb(const char *field_name) {
	std::ifstream input("/proc/self/status");
	if (!input.is_open()) {
		return static_cast<std::int64_t>(-1);
	}

	std::string line;
	while (std::getline(input, line)) {
		if (line.rfind(field_name, 0) != 0) {
			continue;
		}

		std::istringstream stream(line.substr(std::char_traits<char>::length(field_name)));
		std::int64_t value_kb = 0;
		std::string unit;
		if (stream >> value_kb >> unit) {
			return value_kb;
		}
		return static_cast<std::int64_t>(-1);
	}

	return static_cast<std::int64_t>(-1);
}

// Returns the current resident process memory in bytes when the platform exposes it.
// How: Linux uses VmRSS; unsupported platforms fall back to zero because the runtime does not track allocator-internal usage yet.
[[nodiscard]] inline std::int64_t process_memory_usage_bytes() {
#if defined(__linux__)
	const std::int64_t value_kb = read_proc_status_kb("VmRSS:");
	if (value_kb >= 0) {
		return value_kb * static_cast<std::int64_t>(1024);
	}
#endif
	return static_cast<std::int64_t>(0);
}

// Returns the peak resident process memory in bytes when the platform exposes it.
// How: Linux prefers VmHWM; Unix-like fallbacks use getrusage where ru_maxrss is defined in kilobytes on Linux and bytes on macOS/BSD.
[[nodiscard]] inline std::int64_t process_peak_memory_usage_bytes() {
#if defined(__linux__)
	const std::int64_t value_kb = read_proc_status_kb("VmHWM:");
	if (value_kb >= 0) {
		return value_kb * static_cast<std::int64_t>(1024);
	}
#endif
#if defined(__unix__) || defined(__APPLE__)
	struct rusage usage {};
	if (getrusage(RUSAGE_SELF, &usage) == 0) {
		#if defined(__APPLE__)
		return static_cast<std::int64_t>(usage.ru_maxrss);
		#else
		return static_cast<std::int64_t>(usage.ru_maxrss) * static_cast<std::int64_t>(1024);
		#endif
	}
#endif
	return static_cast<std::int64_t>(0);
}

} // namespace detail

// Implements the lowered unset helper for the currently supported mutable wrappers.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
template <typename... Args>
inline void unset(Args &...args) {
	(detail::apply_unset(args), ...);
}

// Implements PHP memory_get_usage() in a process-level, benchmark-oriented form.
// How: the runtime currently reports resident process memory in bytes rather than Zend allocator internals.
[[nodiscard]] inline int_t memory_get_usage() {
	return int_t(detail::process_memory_usage_bytes());
}

// Implements PHP memory_get_usage(true|false) with the current prototype semantics.
// How: the bool parameter is accepted for PHP surface compatibility, but both branches currently return the same process-level byte count.
[[nodiscard]] inline int_t memory_get_usage(bool_t) {
	return int_t(detail::process_memory_usage_bytes());
}

// Implements PHP memory_get_peak_usage() in a process-level, benchmark-oriented form.
// How: the runtime currently reports peak resident process memory in bytes rather than Zend allocator internals.
[[nodiscard]] inline int_t memory_get_peak_usage() {
	return int_t(detail::process_peak_memory_usage_bytes());
}

// Implements PHP memory_get_peak_usage(true|false) with the current prototype semantics.
// How: the bool parameter is accepted for PHP surface compatibility, but both branches currently return the same process-level byte count.
[[nodiscard]] inline int_t memory_get_peak_usage(bool_t) {
	return int_t(detail::process_peak_memory_usage_bytes());
}

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

// Implements PHP-style weak reference creation for shared-owned objects.
// How: weak observers are modeled directly with weak_p so generated code does not need a second wrapper family.
template <typename T>
inline weak_p<T> weakref(const shared_p<T> &value) {
	return weak_p<T>(value);
}

// Implements PHP-style weak reference readback.
// How: locking a weak observer yields a shared handle, and empty state is represented by a null shared_p sentinel.
template <typename T>
inline shared_p<T> weakref_get(const weak_p<T> &value) {
	return value.lock();
}

} // namespace scpp::php

namespace scpp::php {

template <typename T>
// Implements PHP strict identity between an enum instance pointer and a compact enum value.
// How: enum instance methods compare `$this` against canonical case values by dereferencing the current instance.
inline ::scpp::bool_t identical(const T *left, const T &right) {
	return left == nullptr ? ::scpp::bool_t(false) : ::scpp::bool_t((*left) == right);
}

template <typename T>
// Implements PHP strict identity between a compact enum value and an enum instance pointer.
// How: the helper supports comparisons where the canonical case value appears on the left side.
inline ::scpp::bool_t identical(const T &left, const T *right) {
	return right == nullptr ? ::scpp::bool_t(false) : ::scpp::bool_t(left == (*right));
}

} // namespace scpp::php
