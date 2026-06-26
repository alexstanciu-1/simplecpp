#pragma once

#include "lang/php/support/php_common.hpp"
#include "scpp/runtime_error.hpp"

namespace scpp::detail {

template <typename T>
struct condition_nullable_info {
	static constexpr bool value = false;
};

template <typename T>
struct condition_nullable_info<nullable<T>> {
	static constexpr bool value = true;
	using inner_type = T;
};

template <typename T>
inline constexpr bool condition_nullable_info_v = condition_nullable_info<std::remove_cvref_t<T>>::value;

inline const char *condition_truthy_mixed_kind_name(const mixed_t::kind_t kind) {
	switch (kind) {
		case mixed_t::kind_t::null_v:
			return "null_t";
		case mixed_t::kind_t::bool_v:
			return "bool_t";
		case mixed_t::kind_t::int_v:
			return "int_t";
		case mixed_t::kind_t::float_v:
			return "float_t";
		case mixed_t::kind_t::string_v:
			return "string_t";
		case mixed_t::kind_t::table_v:
			return "hash_t";
		case mixed_t::kind_t::shared_table_v:
			return "shared_hash_t";
		case mixed_t::kind_t::dynamic_v:
			return "dynamic_t";
		case mixed_t::kind_t::weak_table_v:
			return "weak_hash_t";
	}
	return "unknown";
}

[[noreturn]] inline void throw_condition_truthy_mixed_kind_error(const mixed_t &value, const char *component, const char *operation) {
	throw ::scpp::runtime_error(
		"scpp::condition_truthy(mixed_t): mixed_t kind is not allowed in condition context",
		"condition_truthy_reject_mixed_kind",
		component,
		operation,
		{
			{"mixed_kind", condition_truthy_mixed_kind_name(value.kind())}
		}
	);
}

inline bool_t condition_truthy_string(const string_t &value) {
	const auto &native = value.native_value();
	return bool_t(!native.empty() && native != "0");
}

template <typename Value>
inline bool_t condition_truthy_impl(Value &&value, const char *component, const char *operation) {
	using value_t = std::remove_cvref_t<Value>;
	if constexpr (std::is_same_v<value_t, bool_t>) {
		return value;
	} else if constexpr (::scpp::detail::is_int_t_v<value_t>) {
		return bool_t(value.native_value() != 0);
	} else if constexpr (std::is_same_v<value_t, float_t>) {
		return bool_t(value.native_value() != 0.0);
	} else if constexpr (std::is_same_v<value_t, string_t>) {
		return condition_truthy_string(value);
	} else if constexpr (condition_nullable_info_v<value_t>) {
		if (!value.has_value().native_value()) {
			return bool_t(false);
		}
		using inner_t = typename condition_nullable_info<value_t>::inner_type;
		return condition_truthy_impl(cast<inner_t>(value), component, operation);
	} else if constexpr (::scpp::detail::is_specialization_of_v<value_t, result_or_false>) {
		if (!value.has_value().native_value()) {
			return bool_t(false);
		}
		return condition_truthy_impl(value.value(), component, operation);
	} else if constexpr (::scpp::detail::is_specialization_of_v<value_t, result_or_bool>) {
		if (!value.has_value().native_value()) {
			return value.is_true();
		}
		return condition_truthy_impl(value.value(), component, operation);
	} else if constexpr (::scpp::detail::is_specialization_of_v<value_t, result>) {
		if (!value.has_value().native_value()) {
			return bool_t(false);
		}
		return condition_truthy_impl(value.value(), component, operation);
	} else if constexpr (::scpp::detail::is_specialization_of_v<value_t, shared_p>) {
		return bool_t(value.has_value().native_value());
	} else if constexpr (::scpp::detail::is_specialization_of_v<value_t, unique_p>) {
		return bool_t(value.has_value().native_value());
	} else if constexpr (::scpp::detail::is_specialization_of_v<value_t, weak_p>) {
		return bool_t(!value.expired().native_value());
	} else if constexpr (std::is_same_v<value_t, mixed_t>) {
		switch (value.kind()) {
			case mixed_t::kind_t::null_v:
				return bool_t(false);
			case mixed_t::kind_t::bool_v:
				return value.bool_value();
			case mixed_t::kind_t::int_v:
				return bool_t(value.int_value().native_value() != 0);
			case mixed_t::kind_t::float_v:
				return bool_t(value.float_value().native_value() != 0.0);
			case mixed_t::kind_t::string_v:
				return condition_truthy_impl(*value.string_if(), component, operation);
			default:
				throw_condition_truthy_mixed_kind_error(value, component, operation);
		}
	} else {
		static_assert(::scpp::detail::always_false_v<value_t>, "unsupported scpp::condition_truthy input type");
	}
}

} // namespace scpp::detail

namespace scpp {

template <typename Value>
inline bool_t condition_truthy(Value &&value) {
	return detail::condition_truthy_impl(std::forward<Value>(value), "condition_truthy", "condition");
}

} // namespace scpp
