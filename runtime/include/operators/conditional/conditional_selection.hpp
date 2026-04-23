#pragma once

#include "operators/conditional/condition_truthiness.hpp"

namespace scpp::detail {

template <typename T>
struct condition_guarded_result_info {
	static constexpr bool value = false;
};

template <typename T>
struct condition_guarded_result_info<result_or_false<T>> {
	static constexpr bool value = true;
	using inner_type = T;
};

template <typename T>
struct condition_guarded_result_info<result_or_bool<T>> {
	static constexpr bool value = true;
	using inner_type = T;
};

template <typename T>
struct condition_guarded_result_info<result<T>> {
	static constexpr bool value = true;
	using inner_type = T;
};

template <typename T>
inline constexpr bool condition_guarded_result_info_v = condition_guarded_result_info<std::remove_cvref_t<T>>::value;

template <typename Then, typename Else>
struct condition_ternary_result;

template <typename T>
struct condition_ternary_result<T, T> {
	using type = T;
};

template <>
struct condition_ternary_result<mixed_t, mixed_t> {
	using type = mixed_t;
};

template <typename T>
struct condition_ternary_result<nullable<T>, T> {
	using type = nullable<T>;
};

template <typename T>
struct condition_ternary_result<T, nullable<T>> {
	using type = nullable<T>;
};

template <typename Else>
requires (!std::is_same_v<std::remove_cvref_t<Else>, mixed_t>)
struct condition_ternary_result<mixed_t, Else> {
	using type = mixed_t;
};

template <typename Then>
requires (!std::is_same_v<std::remove_cvref_t<Then>, mixed_t>)
struct condition_ternary_result<Then, mixed_t> {
	using type = mixed_t;
};

template <typename T>
struct condition_ternary_result<result_or_false<T>, T> {
	using type = result_or_false<T>;
};

template <typename T>
struct condition_ternary_result<T, result_or_false<T>> {
	using type = result_or_false<T>;
};

template <typename T>
struct condition_ternary_result<result_or_bool<T>, T> {
	using type = result_or_bool<T>;
};

template <typename T>
struct condition_ternary_result<T, result_or_bool<T>> {
	using type = result_or_bool<T>;
};

template <typename T>
struct condition_ternary_result<result<T>, T> {
	using type = result<T>;
};

template <typename T>
struct condition_ternary_result<T, result<T>> {
	using type = result<T>;
};

template <typename Result, typename Value>
inline Result normalize_ternary_branch(Value &&value) {
	using result_t = std::remove_cvref_t<Result>;
	using value_t = std::remove_cvref_t<Value>;
	if constexpr (std::is_same_v<result_t, value_t>) {
		return std::forward<Value>(value);
	} else if constexpr (std::is_same_v<result_t, mixed_t>) {
		if constexpr (condition_nullable_info_v<value_t>) {
			using inner_t = typename condition_nullable_info<value_t>::inner_type;
			return mixed_t(cast<inner_t>(value));
		}
		return mixed_t(std::forward<Value>(value));
	} else if constexpr (condition_nullable_info_v<result_t>) {
		using inner_t = typename condition_nullable_info<result_t>::inner_type;
		if constexpr (std::is_same_v<inner_t, value_t>) {
			return result_t(std::forward<Value>(value));
		} else {
			static_assert(::scpp::detail::always_false_v<result_t, value_t>, "unsupported ternary_eval branch combination");
		}
	} else if constexpr (condition_guarded_result_info_v<result_t>) {
		using inner_t = typename condition_guarded_result_info<result_t>::inner_type;
		if constexpr (std::is_same_v<inner_t, value_t>) {
			return result_t(std::forward<Value>(value));
		} else {
			static_assert(::scpp::detail::always_false_v<result_t, value_t>, "unsupported ternary_eval branch combination");
		}
	} else {
		static_assert(::scpp::detail::always_false_v<result_t, value_t>, "unsupported ternary_eval branch combination");
	}
}

} // namespace scpp::detail

namespace scpp {

template <typename CondFn, typename ThenFn, typename ElseFn>
inline auto ternary_eval(CondFn &&cond_fn, ThenFn &&then_fn, ElseFn &&else_fn) {
	auto &&cond = cond_fn();
	using then_t = std::remove_cvref_t<decltype(then_fn())>;
	using else_t = std::remove_cvref_t<decltype(else_fn())>;
	using result_t = typename detail::condition_ternary_result<then_t, else_t>::type;
	if (static_cast<bool>(detail::condition_truthy_impl(cond, "ternary_eval", "?:"))) {
		return detail::normalize_ternary_branch<result_t>(then_fn());
	}
	return detail::normalize_ternary_branch<result_t>(else_fn());
}

} // namespace scpp
