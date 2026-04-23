#pragma once

#include "lang/php/support/php_common.hpp"

namespace scpp::detail {

template <typename T>
struct coalesce_wrapper_info {
	static constexpr bool value = false;
};

template <typename T>
struct coalesce_wrapper_info<nullable<T>> {
	static constexpr bool value = true;
	using inner_type = T;
};

template <typename T>
struct coalesce_wrapper_info<result_or_false<T>> {
	static constexpr bool value = true;
	using inner_type = T;
};

template <typename T>
struct coalesce_wrapper_info<result<T>> {
	static constexpr bool value = true;
	using inner_type = T;
};

template <typename T>
inline constexpr bool coalesce_wrapper_info_v = coalesce_wrapper_info<std::remove_cvref_t<T>>::value;

template <typename T>
using coalesce_wrapper_inner_t = typename coalesce_wrapper_info<std::remove_cvref_t<T>>::inner_type;

template <typename T>
struct coalesce_guarded_result_info {
	static constexpr bool value = false;
};

template <typename T>
struct coalesce_guarded_result_info<result_or_false<T>> {
	static constexpr bool value = true;
	using inner_type = T;
};

template <typename T>
struct coalesce_guarded_result_info<result_or_bool<T>> {
	static constexpr bool value = true;
	using inner_type = T;
};

template <typename T>
struct coalesce_guarded_result_info<result<T>> {
	static constexpr bool value = true;
	using inner_type = T;
};

template <typename T>
inline constexpr bool coalesce_guarded_result_info_v = coalesce_guarded_result_info<std::remove_cvref_t<T>>::value;

template <typename Left, typename Right>
struct coalesce_result;

template <typename T>
struct coalesce_result<T, T> {
	using type = T;
};

template <>
struct coalesce_result<mixed_t, mixed_t> {
	using type = mixed_t;
};

template <typename T, typename Right>
requires (
	!coalesce_wrapper_info_v<Right>
	&& !std::is_same_v<std::remove_cvref_t<Right>, mixed_t>
	&& std::is_same_v<T, std::remove_cvref_t<Right>>
)
struct coalesce_result<nullable<T>, Right> {
	using type = T;
};

template <typename Left, typename T>
requires (
	!coalesce_wrapper_info_v<Left>
	&& !std::is_same_v<std::remove_cvref_t<Left>, mixed_t>
	&& std::is_same_v<std::remove_cvref_t<Left>, T>
)
struct coalesce_result<Left, nullable<T>> {
	using type = Left;
};

template <typename Left, typename Right>
requires (
	coalesce_wrapper_info_v<Left>
	&& coalesce_wrapper_info_v<Right>
	&& std::is_same_v<coalesce_wrapper_inner_t<Left>, coalesce_wrapper_inner_t<Right>>
)
struct coalesce_result<Left, Right> {
	using type = coalesce_wrapper_inner_t<Left>;
};

template <typename Left, typename Right>
requires (
	!coalesce_wrapper_info_v<Left>
	&& coalesce_wrapper_info_v<Right>
	&& !std::is_same_v<std::remove_cvref_t<Left>, mixed_t>
	&& std::is_same_v<std::remove_cvref_t<Left>, coalesce_wrapper_inner_t<Right>>
)
struct coalesce_result<Left, Right> {
	using type = Left;
};

template <typename Left, typename Right>
requires (
	coalesce_wrapper_info_v<Left>
	&& !coalesce_wrapper_info_v<Right>
	&& !std::is_same_v<std::remove_cvref_t<Right>, mixed_t>
	&& std::is_same_v<coalesce_wrapper_inner_t<Left>, std::remove_cvref_t<Right>>
)
struct coalesce_result<Left, Right> {
	using type = Right;
};

template <typename Right>
requires (!std::is_same_v<std::remove_cvref_t<Right>, mixed_t>)
struct coalesce_result<mixed_t, Right> {
	using type = mixed_t;
};

template <typename T>
struct coalesce_result<nullable<T>, mixed_t> {
	using type = mixed_t;
};

template <typename Left>
requires (
	!coalesce_wrapper_info_v<Left>
	&& !std::is_same_v<std::remove_cvref_t<Left>, mixed_t>
)
struct coalesce_result<Left, mixed_t> {
	using type = mixed_t;
};

template <typename Left>
requires (coalesce_wrapper_info_v<Left>)
struct coalesce_result<Left, mixed_t> {
	using type = mixed_t;
};

template <typename Value>
inline bool_t coalesce_has_usable_value(const Value &value) {
	using value_t = std::remove_cvref_t<Value>;
	if constexpr (::scpp::detail::is_specialization_of_v<value_t, nullable>) {
		return value.has_value();
	} else if constexpr (::scpp::detail::is_specialization_of_v<value_t, result_or_false>) {
		return value.has_value();
	} else if constexpr (::scpp::detail::is_specialization_of_v<value_t, result>) {
		return value.has_value();
	} else if constexpr (std::is_same_v<value_t, mixed_t>) {
		return bool_t(value.kind() != mixed_t::kind_t::null_v);
	} else {
		return bool_t(true);
	}
}

template <typename Value>
inline void coalesce_require_selected_value_domain(const Value &value) {
	using value_t = std::remove_cvref_t<Value>;
	if constexpr (coalesce_wrapper_info_v<value_t>) {
		if (!value.has_value().native_value()) {
			throw std::runtime_error("scpp::coalesce_eval(): ?? selected a branch with no usable value domain");
		}
	}
}

template <typename Result, typename Value>
inline Result normalize_coalesce_branch(Value &&value) {
	using result_t = std::remove_cvref_t<Result>;
	using value_t = std::remove_cvref_t<Value>;
	coalesce_require_selected_value_domain(value);
	if constexpr (std::is_same_v<result_t, value_t>) {
		return std::forward<Value>(value);
	} else if constexpr (std::is_same_v<result_t, mixed_t>) {
		if constexpr (::scpp::detail::is_specialization_of_v<value_t, nullable>) {
			using inner_t = coalesce_wrapper_inner_t<value_t>;
			return mixed_t(cast<inner_t>(value));
		} else if constexpr (::scpp::detail::is_specialization_of_v<value_t, result_or_false> || ::scpp::detail::is_specialization_of_v<value_t, result>) {
			return mixed_t(value.value());
		}
		return mixed_t(std::forward<Value>(value));
	} else if constexpr (coalesce_guarded_result_info_v<result_t>) {
		using inner_t = typename coalesce_guarded_result_info<result_t>::inner_type;
		if constexpr (std::is_same_v<inner_t, value_t>) {
			return result_t(std::forward<Value>(value));
		} else {
			return cast<result_t>(std::forward<Value>(value));
		}
	} else {
		return cast<result_t>(std::forward<Value>(value));
	}
}

} // namespace scpp::detail

namespace scpp {

template <typename LeftFn, typename RightFn>
inline auto coalesce_eval(LeftFn &&left_fn, RightFn &&right_fn) {
	auto &&left = left_fn();
	using left_t = std::remove_cvref_t<decltype(left)>;
	using right_t = std::remove_cvref_t<decltype(right_fn())>;
	if constexpr (
		::scpp::detail::is_specialization_of_v<left_t, result_or_bool>
		|| ::scpp::detail::is_specialization_of_v<right_t, result_or_bool>
	) {
		throw std::runtime_error("scpp::coalesce_eval(): ?? does not support result_or_bool<T>; convert it explicitly first");
		return mixed_t();
	} else {
		using result_t = typename detail::coalesce_result<left_t, right_t>::type;
		if (static_cast<bool>(detail::coalesce_has_usable_value(left))) {
			return detail::normalize_coalesce_branch<result_t>(left);
		}
		return detail::normalize_coalesce_branch<result_t>(right_fn());
	}
}

} // namespace scpp
