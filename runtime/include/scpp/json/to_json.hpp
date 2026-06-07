#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/detail.hpp"
#include "scpp/float_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/string_t.hpp"
#include "scpp/vector_t.hpp"

#include <type_traits>

namespace scpp::json {

namespace to_json_detail {

template <typename T>
struct is_supported : std::false_type {};

template <> struct is_supported<null_t> : std::true_type {};
template <> struct is_supported<bool_t> : std::true_type {};
template <> struct is_supported<int_t> : std::true_type {};
template <> struct is_supported<float_t> : std::true_type {};
template <> struct is_supported<string_t> : std::true_type {};
template <> struct is_supported<mixed_t> : std::true_type {};

template <typename T>
struct is_supported<nullable<T>> : std::bool_constant<is_supported<T>::value> {};

template <typename T>
struct is_supported<vector_t<T>> : std::bool_constant<is_supported<T>::value> {};

template <typename T, typename K>
struct is_supported<hash_t<T, K>> : std::bool_constant<
	is_supported<T>::value && (std::is_same_v<detail::remove_cvref_t<K>, string_t> || std::is_same_v<detail::remove_cvref_t<K>, int_t>)
> {};

template <typename T>
inline constexpr bool is_supported_v = is_supported<detail::remove_cvref_t<T>>::value;

template <typename T>
[[nodiscard]] inline mixed_t convert(const T &value);

template <>
[[nodiscard]] inline mixed_t convert<null_t>(const null_t &value) {
	return mixed_t(value);
}

template <>
[[nodiscard]] inline mixed_t convert<bool_t>(const bool_t &value) {
	return mixed_t(value);
}

template <>
[[nodiscard]] inline mixed_t convert<int_t>(const int_t &value) {
	return mixed_t(value);
}

template <>
[[nodiscard]] inline mixed_t convert<float_t>(const float_t &value) {
	return mixed_t(value);
}

template <>
[[nodiscard]] inline mixed_t convert<string_t>(const string_t &value) {
	return mixed_t(value);
}

template <>
[[nodiscard]] inline mixed_t convert<mixed_t>(const mixed_t &value) {
	return value.clone();
}

template <typename T>
[[nodiscard]] inline mixed_t convert(const nullable<T> &value) {
	if (!value.has_value().native_value()) {
		return mixed_t(null_t{});
	}
	return convert(value.value());
}

template <typename T>
[[nodiscard]] inline mixed_t convert(const vector_t<T> &value) {
	auto out = shared_table_();
	for (const auto &entry : value.native_value()) {
		(void) out->append(convert(entry));
	}
	return mixed_t(std::move(out));
}

template <typename T, typename K>
[[nodiscard]] inline mixed_t convert(const hash_t<T, K> &value) {
	auto out = shared_table_();
	for (auto it = value.begin_entries(); it != value.end_entries(); ++it) {
		const auto entry = *it;
		if constexpr (std::is_same_v<detail::remove_cvref_t<K>, string_t>) {
			out->set(entry.key(), convert(entry.value_ref()));
		} else if constexpr (std::is_same_v<detail::remove_cvref_t<K>, int_t>) {
			out->set(entry.key(), convert(entry.value_ref()));
		} else {
			static_assert(detail::always_false_v<K>, "scpp::json::to_json supports hash_t<T, string_t> and hash_t<T, int_t> keys only");
		}
	}
	return mixed_t(std::move(out));
}

template <typename T>
[[nodiscard]] inline mixed_t convert(const T &value) {
	static_assert(is_supported_v<T>, "scpp::json::to_json is not defined for this source type");

	if constexpr (std::is_same_v<detail::remove_cvref_t<T>, null_t>) {
		return convert<null_t>(value);
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, bool_t>) {
		return convert<bool_t>(value);
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, int_t>) {
		return convert<int_t>(value);
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, float_t>) {
		return convert<float_t>(value);
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, string_t>) {
		return convert<string_t>(value);
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, mixed_t>) {
		return convert<mixed_t>(value);
	} else if constexpr (detail::is_specialization_of_v<T, nullable>) {
		return convert(value);
	} else if constexpr (detail::is_specialization_of_v<T, vector_t>) {
		return convert(value);
	} else if constexpr (detail::is_specialization_of_v<T, hash_t>) {
		return convert(value);
	} else {
		static_assert(detail::always_false_v<T>, "scpp::json::to_json is not defined for this source type");
	}
}

} // namespace to_json_detail

template <typename T>
[[nodiscard]] inline mixed_t to_json(const T &value) {
	return to_json_detail::convert(value);
}

} // namespace scpp::json
