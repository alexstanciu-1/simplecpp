#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/result.hpp"
#include "scpp/result_or_bool.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/weak_p.hpp"

#include <cstddef>
#include <cstdint>
#include <type_traits>

namespace scpp::detail {

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

template <typename T, typename K>
struct is_countable_lookup_target<hash_t<T, K>> : std::true_type {};

template <>
struct is_countable_lookup_target<mixed_t> : std::true_type {};

} // namespace scpp::detail
