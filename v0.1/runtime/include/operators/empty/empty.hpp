#pragma once

#include "operators/probe/probe.hpp"
#include "scpp/runtime_error.hpp"
#include <utility>

namespace scpp::detail {

// Absence, unsupported kinds and invalid keyed paths are not empty collections.
[[noreturn]] inline bool_t throw_invalid_empty() {
	throw runtime_error("empty requires an existing collection or string",
		"invalid_empty_operand", "scpp::empty", "empty");
}

template <typename T>
inline bool_t empty_scalar(const T &) { return throw_invalid_empty(); }

inline bool_t empty_scalar(const string_t &value) { return value.empty(); }

inline bool_t empty_scalar(const dynamic_value &value) {
	if (value.kind() == dynamic_value::kind_t::string_v) return value.get_string().empty();
	if (const auto *table = value.table_if()) return table->empty();
	return throw_invalid_empty();
}

inline bool_t empty_scalar(const mixed_t &value) {
	if (const auto *text = value.string_if()) return text->empty();
	if (const auto *table = value.table_if()) return table->empty();
	return throw_invalid_empty();
}

inline bool_t empty_from_probe(probe_state state, const mixed_t *value) {
	if (state != probe_state::present_value || value == nullptr) return throw_invalid_empty();
	return empty_scalar(*value);
}

} // namespace scpp::detail

namespace scpp {

// Only explicit collection overloads confer emptiness; wrappers never delegate.
template <typename T>
inline bool_t empty(const T &value) { return detail::empty_scalar(value); }

template <typename T>
inline bool_t empty(const vector_t<T> &value) { return value.empty(); }

template <typename T, std::size_t N>
inline bool_t empty(const fixed_array_t<T, N> &value) { return value.empty(); }

template <typename T, typename K>
inline bool_t empty(const hash_t<T, K> &value) { return value.empty(); }

template <typename T>
inline bool_t empty(const vector_t<T> &value, const int_t<> &key) {
	if (!detail::vector_has_index(value.size(), key)) {
		return detail::throw_invalid_empty();
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		const auto probe = detail::probe_value(value.at(key));
		return detail::empty_from_probe(probe.state, probe.value);
	}
	return empty(value.at(key));
}

template <typename T>
inline bool_t empty(const vector_t<T> &value, const int key) {
	return empty(value, int_t<>{static_cast<std::int64_t>(key)});
}

template <typename T, typename K>
	requires std::same_as<K, int_t<>>
inline bool_t empty(const hash_t<T, K> &value, const int_t<> &key) {
	if (!value.has(key).native_value()) {
		return detail::throw_invalid_empty();
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		const auto probe = detail::probe_value(value.at(key));
		return detail::empty_from_probe(probe.state, probe.value);
	}
	return empty(value.at(key));
}

template <typename T, typename K>
	requires std::same_as<K, string_t>
inline bool_t empty(const hash_t<T, K> &value, const string_t &key) {
	if (!value.has(key).native_value()) {
		return detail::throw_invalid_empty();
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		const auto probe = detail::probe_value(value.at(key));
		return detail::empty_from_probe(probe.state, probe.value);
	}
	return empty(value.at(key));
}

template <typename T, typename K>
	requires std::same_as<K, string_t>
inline bool_t empty(const hash_t<T, K> &value, const char *key) {
	return empty(value, string_t{key});
}

template <typename T, typename K>
	requires std::same_as<K, int_t<>>
inline bool_t empty(const hash_t<T, K> &value, const int key) {
	return empty(value, int_t<>{static_cast<std::int64_t>(key)});
}

template <typename T, typename K>
	requires (!std::same_as<K, int_t<>> && !std::same_as<K, string_t> && !std::same_as<K, mixed_t>)
inline bool_t empty(const hash_t<T, K> &value, const K &key) {
	if (!value.has(key).native_value()) {
		return detail::throw_invalid_empty();
	}
	if constexpr (std::is_same_v<T, mixed_t>) {
		const auto probe = detail::probe_value(value.at(key));
		return detail::empty_from_probe(probe.state, probe.value);
	}
	return empty(value.at(key));
}

inline bool_t empty(const hash_t<mixed_t, mixed_t> &value, const int_t<> &key) {
	if (!value.has(key).native_value()) {
		return detail::throw_invalid_empty();
	}
	const auto probe = detail::probe_value(value.at(key));
	return detail::empty_from_probe(probe.state, probe.value);
}

inline bool_t empty(const hash_t<mixed_t, mixed_t> &value, const string_t &key) {
	if (!value.has(key).native_value()) {
		return detail::throw_invalid_empty();
	}
	const auto probe = detail::probe_value(value.at(key));
	return detail::empty_from_probe(probe.state, probe.value);
}

inline bool_t empty(const hash_t<mixed_t, mixed_t> &value, const char *key) {
	return empty(value, string_t{key});
}

inline bool_t empty(const hash_t<mixed_t, mixed_t> &value, const int key) {
	return empty(value, int_t<>{static_cast<std::int64_t>(key)});
}

inline bool_t empty(const mixed_t &value, const mixed_t &key) {
	if (key.kind() == mixed_t::kind_t::int_v) {
		return empty(value, key.int_value());
	}
	if (key.kind() == mixed_t::kind_t::string_v) {
		return empty(value, *key.string_if());
	}
	return detail::throw_invalid_empty();
}

inline bool_t empty(const mixed_t &value, const int_t<> &key) {
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
		return detail::throw_invalid_empty();
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
		return detail::throw_invalid_empty();
	}
	return empty(*table, key);
}

inline bool_t empty(const mixed_t &value, const char *key) {
	return empty(value, string_t{key});
}

inline bool_t empty(const mixed_t &value, const int key) {
	return empty(value, int_t<>{static_cast<std::int64_t>(key)});
}

inline bool_t empty(const detail::dynamic_value &value, const mixed_t &key) {
	const auto *table = value.table_if();
	if (!table || (key.kind() != mixed_t::kind_t::int_v && key.kind() != mixed_t::kind_t::string_v)) return detail::throw_invalid_empty();
	if (!table->has(key).native_value()) return detail::throw_invalid_empty();
	return detail::empty_scalar(table->at(key));
}

inline bool_t empty(const detail::dynamic_value &value, int key) {
	return ::scpp::empty(value, mixed_t{int_t<>{static_cast<std::int64_t>(key)}});
}

} // namespace scpp
