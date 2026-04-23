#pragma once

#include "operators/probe/probe.hpp"

#include <utility>

namespace scpp::detail {

// Centralizes the Prism++ emptiness contract for plain one-value checks.
// How: this follows the current supported scalar/wrapper families, including the deliberate non-empty treatment of string "0".
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

inline bool_t empty_scalar(const bool_t &value) {
	return bool_t(!value.native_value());
}

inline bool_t empty_scalar(const int_t &value) {
	return bool_t(value.native_value() == 0);
}

inline bool_t empty_scalar(const float_t &value) {
	return bool_t(value.native_value() == 0.0);
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

inline bool_t empty_from_probe(const probe_state state, const mixed_t *value) {
	if (state == probe_state::invalid || state == probe_state::missing || state == probe_state::present_null) {
		return bool_t(true);
	}
	if (value == nullptr) {
		return bool_t(false);
	}
	if (value->is_bool().native_value()) {
		return empty_scalar(value->bool_value());
	}
	if (value->is_int().native_value()) {
		return empty_scalar(value->int_value());
	}
	if (value->is_float().native_value()) {
		return empty_scalar(value->float_value());
	}
	if (const auto *string_value = value->string_if()) {
		return empty_scalar(*string_value);
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

} // namespace scpp::detail

namespace scpp {

template <typename T>
inline bool_t empty(const vector_t<T> &value) {
	return value.empty();
}

template <typename T>
inline bool_t empty(const hash_t<T> &value) {
	return value.empty();
}

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

inline bool_t empty(const mixed_t &value) {
	const auto probe = detail::probe_value(value);
	return detail::empty_from_probe(probe.state, probe.value);
}

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

} // namespace scpp
