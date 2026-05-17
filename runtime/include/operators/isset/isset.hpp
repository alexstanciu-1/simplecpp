#pragma once

#include "operators/probe/probe.hpp"

#include <tuple>
#include <type_traits>
#include <utility>

namespace scpp {

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
template <typename T, typename K>
	requires std::same_as<K, int_t>
inline bool_t isset(const hash_t<T, K> &value, const int_t &key) {
	if (!value.has(key).native_value()) {
		return bool_t(false);
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		return detail::isset_from_probe(detail::probe_value(value.at(key)).state);
	}
	return bool_t(true);
}

template <typename T, typename K>
	requires std::same_as<K, string_t>
inline bool_t isset(const hash_t<T, K> &value, const string_t &key) {
	if (!value.has(key).native_value()) {
		return bool_t(false);
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		return detail::isset_from_probe(detail::probe_value(value.at(key)).state);
	}
	return bool_t(true);
}

template <typename T, typename K>
	requires std::same_as<K, string_t>
inline bool_t isset(const hash_t<T, K> &value, const char *key) {
	return isset(value, string_t{key});
}

template <typename T, typename K>
	requires std::same_as<K, int_t>
inline bool_t isset(const hash_t<T, K> &value, const int key) {
	return isset(value, int_t{static_cast<std::int64_t>(key)});
}

template <typename T, typename K>
	requires (!std::same_as<K, int_t> && !std::same_as<K, string_t> && !std::same_as<K, mixed_t>)
inline bool_t isset(const hash_t<T, K> &value, const K &key) {
	if (!value.has(key).native_value()) {
		return bool_t(false);
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		return detail::isset_from_probe(detail::probe_value(value.at(key)).state);
	}
	return bool_t(true);
}

inline bool_t isset(const hash_t<mixed_t, mixed_t> &value, const int_t &key) {
	if (!value.has(key).native_value()) {
		return bool_t(false);
	}
	return detail::isset_from_probe(detail::probe_value(value.at(key)).state);
}

inline bool_t isset(const hash_t<mixed_t, mixed_t> &value, const string_t &key) {
	if (!value.has(key).native_value()) {
		return bool_t(false);
	}
	return detail::isset_from_probe(detail::probe_value(value.at(key)).state);
}

inline bool_t isset(const hash_t<mixed_t, mixed_t> &value, const char *key) {
	return isset(value, string_t{key});
}

inline bool_t isset(const hash_t<mixed_t, mixed_t> &value, const int key) {
	return isset(value, int_t{static_cast<std::int64_t>(key)});
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

template <typename T, typename Key>
requires (!detail::is_countable_lookup_target<std::remove_cvref_t<T>>::value)
	&& requires(const T &wrapped, const Key &key) { ::scpp::isset(wrapped, key); }
inline bool_t isset(const nullable<T> &value, const Key &key) {
	if (!value.has_value().native_value()) {
		return bool_t(false);
	}
	return ::scpp::isset(value.value(), key);
}

template <typename T, typename Key>
requires requires(const T &wrapped, const Key &key) { ::scpp::isset(wrapped, key); }
inline bool_t isset(const shared_p<T> &value, const Key &key) {
	if (!value.has_value().native_value()) {
		return bool_t(false);
	}
	return ::scpp::isset(*value.get(), key);
}

template <typename T, typename Key>
requires requires(const T &wrapped, const Key &key) { ::scpp::isset(wrapped, key); }
inline bool_t isset(const unique_p<T> &value, const Key &key) {
	if (!value.has_value().native_value()) {
		return bool_t(false);
	}
	return ::scpp::isset(*value.get(), key);
}

// Implements the lowered isset contract across the currently supported runtime value categories.
// How: behavior is defined here once so language adapters can lower into stable helpers instead of ad-hoc code.
inline bool_t isset() {
	return bool_t(true);
}

// Implements one-value isset semantics used by the variadic isset helper.
// How: behavior is defined here once so language adapters can lower into stable helpers instead of ad-hoc code.
inline bool_t isset_one(null_t) {
	return bool_t(false);
}

inline bool_t isset_one(nullopt_t) {
	return bool_t(false);
}

inline bool_t isset_one(nullptr_t) {
	return bool_t(false);
}

inline bool_t isset_one(const mixed_t &value) {
	return detail::isset_from_probe(detail::probe_value(value).state);
}

template <typename T>
inline bool_t isset_one(const nullable<T> &value) {
	return value.has_value();
}

template <typename T>
inline bool_t isset_one(const result_or_false<T> &value) {
	return detail::isset_from_probe(detail::probe_value(value).state);
}

template <typename T>
inline bool_t isset_one(const result_or_bool<T> &value) {
	return detail::isset_from_probe(detail::probe_value(value).state);
}

template <typename T>
inline bool_t isset_one(const result<T> &value) {
	return detail::isset_from_probe(detail::probe_value(value).state);
}

template <typename T>
inline bool_t isset_one(const shared_p<T> &value) {
	return value.has_value();
}

template <typename T>
inline bool_t isset_one(const unique_p<T> &value) {
	return value.has_value();
}

template <typename T>
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
inline bool_t isset(Args &&...args) {
	bool result = true;
	((result = result && isset_one(std::forward<Args>(args)).native_value()), ...);
	return bool_t(result);
}

} // namespace scpp
