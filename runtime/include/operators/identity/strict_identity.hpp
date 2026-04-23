#pragma once

#include "lang/php/support/php_common.hpp"

namespace scpp {

// Implements PHP/Prism++ strict identity for visible null sentinels.
// How: runtime-only null sentinels all normalize to the language-level null value before strict comparison.
template <typename Left, typename Right>
requires (
	(std::is_same_v<std::remove_cvref_t<Left>, null_t> || std::is_same_v<std::remove_cvref_t<Left>, nullopt_t> || std::is_same_v<std::remove_cvref_t<Left>, nullptr_t>)
	&& (std::is_same_v<std::remove_cvref_t<Right>, null_t> || std::is_same_v<std::remove_cvref_t<Right>, nullopt_t> || std::is_same_v<std::remove_cvref_t<Right>, nullptr_t>)
)
inline bool_t identical(Left, Right) {
	return bool_t(true);
}

template <typename T>
inline bool_t identical(const T &left, const T &right);

template <typename Left, typename Right>
requires (!std::is_same_v<std::remove_cvref_t<Left>, std::remove_cvref_t<Right>>)
inline bool_t identical(const Left &left, const Right &right);

template <typename Left, typename Right>
inline bool_t not_identical(const Left &left, const Right &right);

namespace detail {

inline bool_t identical_hash_contents(const hash_t<mixed_t> &left, const hash_t<mixed_t> &right) {
	if (left.size() != right.size()) {
		return bool_t(false);
	}

	auto left_it = left.begin_entries();
	auto right_it = right.begin_entries();
	const auto left_end = left.end_entries();
	const auto right_end = right.end_entries();
	for (; left_it != left_end && right_it != right_end; ++left_it, ++right_it) {
		const auto left_entry = *left_it;
		const auto right_entry = *right_it;
		if (!identical(left_entry.key(), right_entry.key()).native_value()) {
			return bool_t(false);
		}
		if (!identical(left_entry.value_ref(), right_entry.value_ref()).native_value()) {
			return bool_t(false);
		}
	}

	return bool_t(left_it == left_end && right_it == right_end);
}

inline bool_t php_is_null_value(null_t) {
	return bool_t(true);
}

inline bool_t php_is_null_value(nullopt_t) {
	return bool_t(true);
}

inline bool_t php_is_null_value(nullptr_t) {
	return bool_t(true);
}

inline bool_t php_is_null_value(const mixed_t &value) {
	return bool_t(value.kind() == mixed_t::kind_t::null_v);
}

inline bool_t php_is_null_value(const bool_t &) {
	return bool_t(false);
}

inline bool_t php_is_null_value(const int_t &) {
	return bool_t(false);
}

inline bool_t php_is_null_value(const float_t &) {
	return bool_t(false);
}

inline bool_t php_is_null_value(const string_t &) {
	return bool_t(false);
}

inline bool_t php_is_null_value(const false_sentinel_t &) {
	return bool_t(false);
}

inline bool_t php_is_null_value(const error_sentinel_t &) {
	return bool_t(false);
}

template <typename T>
inline bool_t php_is_null_value(const nullable<T> &value) {
	return bool_t(!value.has_value().native_value());
}

template <typename T>
inline bool_t php_is_null_value(const result_or_false<T> &) {
	return bool_t(false);
}

template <typename T>
inline bool_t php_is_null_value(const result_or_bool<T> &) {
	return bool_t(false);
}

template <typename T>
inline bool_t php_is_null_value(const result<T> &) {
	return bool_t(false);
}

template <typename T>
inline bool_t php_is_null_value(const shared_p<T> &value) {
	return bool_t(!value.has_value().native_value());
}

template <typename T>
inline bool_t php_is_null_value(const unique_p<T> &value) {
	return bool_t(!value.has_value().native_value());
}

template <typename T>
inline bool_t php_is_null_value(const weak_p<T> &value) {
	return bool_t(value.expired().native_value());
}

template <typename T>
requires (
	!std::is_same_v<std::remove_cvref_t<T>, null_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, nullopt_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, nullptr_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, mixed_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, bool_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, int_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, float_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, string_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, false_sentinel_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, error_sentinel_t>
	&& !::scpp::detail::is_specialization_of_v<std::remove_cvref_t<T>, nullable>
	&& !::scpp::detail::is_specialization_of_v<std::remove_cvref_t<T>, result_or_false>
	&& !::scpp::detail::is_specialization_of_v<std::remove_cvref_t<T>, result_or_bool>
	&& !::scpp::detail::is_specialization_of_v<std::remove_cvref_t<T>, result>
	&& !::scpp::detail::is_specialization_of_v<std::remove_cvref_t<T>, shared_p>
	&& !::scpp::detail::is_specialization_of_v<std::remove_cvref_t<T>, unique_p>
	&& !::scpp::detail::is_specialization_of_v<std::remove_cvref_t<T>, weak_p>
)
inline bool_t php_is_null_value(const T &) {
	return bool_t(false);
}

inline bool_t identical_same_kind_mixed(const mixed_t &left, const mixed_t &right) {
	switch (left.kind()) {
		case mixed_t::kind_t::null_v:
			return bool_t(true);
		case mixed_t::kind_t::bool_v:
			return bool_t(left.bool_value().native_value() == right.bool_value().native_value());
		case mixed_t::kind_t::int_v:
			return bool_t(left.int_value().native_value() == right.int_value().native_value());
		case mixed_t::kind_t::float_v:
			return bool_t(left.float_value().native_value() == right.float_value().native_value());
		case mixed_t::kind_t::string_v:
			return bool_t(left.get_string().native_value() == right.get_string().native_value());
		case mixed_t::kind_t::table_v:
		case mixed_t::kind_t::shared_table_v:
		case mixed_t::kind_t::dynamic_v:
			return identical_hash_contents(*left.table_if(), *right.table_if());
		case mixed_t::kind_t::weak_table_v:
			return left == right;
	}
	return bool_t(false);
}

} // namespace detail

inline bool_t identical(const mixed_t &left, const mixed_t &right) {
	if (left.kind() != right.kind()) {
		return bool_t(false);
	}
	return detail::identical_same_kind_mixed(left, right);
}

template <typename Right>
requires (std::is_same_v<std::remove_cvref_t<Right>, null_t> || std::is_same_v<std::remove_cvref_t<Right>, nullopt_t> || std::is_same_v<std::remove_cvref_t<Right>, nullptr_t>)
inline bool_t identical(const mixed_t &left, Right) {
	return detail::php_is_null_value(left);
}

template <typename Left>
requires (std::is_same_v<std::remove_cvref_t<Left>, null_t> || std::is_same_v<std::remove_cvref_t<Left>, nullopt_t> || std::is_same_v<std::remove_cvref_t<Left>, nullptr_t>)
inline bool_t identical(Left, const mixed_t &right) {
	return detail::php_is_null_value(right);
}

template <typename LeftMixed>
requires std::is_same_v<std::remove_cvref_t<LeftMixed>, mixed_t>
inline bool_t identical(const LeftMixed &left, const bool_t &right) {
	if (left.kind() != mixed_t::kind_t::bool_v) {
		return bool_t(false);
	}
	return identical(left.bool_value(), right);
}

template <typename RightMixed>
requires std::is_same_v<std::remove_cvref_t<RightMixed>, mixed_t>
inline bool_t identical(const bool_t &left, const RightMixed &right) {
	return identical(right, left);
}

template <typename LeftMixed>
requires std::is_same_v<std::remove_cvref_t<LeftMixed>, mixed_t>
inline bool_t identical(const LeftMixed &left, const int_t &right) {
	if (left.kind() != mixed_t::kind_t::int_v) {
		return bool_t(false);
	}
	return identical(left.int_value(), right);
}

template <typename RightMixed>
requires std::is_same_v<std::remove_cvref_t<RightMixed>, mixed_t>
inline bool_t identical(const int_t &left, const RightMixed &right) {
	return identical(right, left);
}

template <typename LeftMixed>
requires std::is_same_v<std::remove_cvref_t<LeftMixed>, mixed_t>
inline bool_t identical(const LeftMixed &left, const float_t &right) {
	if (left.kind() != mixed_t::kind_t::float_v) {
		return bool_t(false);
	}
	return identical(left.float_value(), right);
}

template <typename RightMixed>
requires std::is_same_v<std::remove_cvref_t<RightMixed>, mixed_t>
inline bool_t identical(const float_t &left, const RightMixed &right) {
	return identical(right, left);
}

template <typename LeftMixed>
requires std::is_same_v<std::remove_cvref_t<LeftMixed>, mixed_t>
inline bool_t identical(const LeftMixed &left, const string_t &right) {
	if (left.kind() != mixed_t::kind_t::string_v) {
		return bool_t(false);
	}
	return identical(*left.string_if(), right);
}

template <typename RightMixed>
requires std::is_same_v<std::remove_cvref_t<RightMixed>, mixed_t>
inline bool_t identical(const string_t &left, const RightMixed &right) {
	return identical(right, left);
}

template <typename T, typename U>
inline bool_t identical(const nullable<T> &left, const nullable<U> &right) {
	if (!left.has_value().native_value()) {
		return identical(null_t{}, right);
	}
	if (!right.has_value().native_value()) {
		return identical(left, null_t{});
	}
	return identical(left.value(), right.value());
}

template <typename T, typename Right>
inline bool_t identical(const nullable<T> &left, const Right &right) {
	if (!left.has_value().native_value()) {
		return identical(null_t{}, right);
	}
	return identical(left.value(), right);
}

template <typename Left, typename T>
inline bool_t identical(const Left &left, const nullable<T> &right) {
	if (!right.has_value().native_value()) {
		return identical(left, null_t{});
	}
	return identical(left, right.value());
}

inline bool_t identical(false_sentinel_t, false_sentinel_t) {
	return bool_t(true);
}

template <typename Right>
inline bool_t identical(false_sentinel_t, const Right &right) {
	return identical(bool_t(false), right);
}

template <typename Left>
inline bool_t identical(const Left &left, false_sentinel_t) {
	return identical(left, bool_t(false));
}

template <typename T, typename U>
inline bool_t identical(const result_or_false<T> &left, const result_or_false<U> &right) {
	if (!left.has_value().native_value()) {
		return identical(bool_t(false), right);
	}
	if (!right.has_value().native_value()) {
		return identical(left, bool_t(false));
	}
	return identical(left.value(), right.value());
}

template <typename T, typename Right>
inline bool_t identical(const result_or_false<T> &left, const Right &right) {
	if (!left.has_value().native_value()) {
		return identical(bool_t(false), right);
	}
	return identical(left.value(), right);
}

template <typename Left, typename T>
inline bool_t identical(const Left &left, const result_or_false<T> &right) {
	if (!right.has_value().native_value()) {
		return identical(left, bool_t(false));
	}
	return identical(left, right.value());
}

template <typename T, typename U>
inline bool_t identical(const result_or_bool<T> &left, const result_or_bool<U> &right) {
	if (!left.has_value().native_value()) {
		return identical(bool_t(left.is_true().native_value()), right);
	}
	if (!right.has_value().native_value()) {
		return identical(left, bool_t(right.is_true().native_value()));
	}
	return identical(left.value(), right.value());
}

template <typename T, typename Right>
inline bool_t identical(const result_or_bool<T> &left, const Right &right) {
	if (!left.has_value().native_value()) {
		return identical(bool_t(left.is_true().native_value()), right);
	}
	return identical(left.value(), right);
}

template <typename Left, typename T>
inline bool_t identical(const Left &left, const result_or_bool<T> &right) {
	if (!right.has_value().native_value()) {
		return identical(left, bool_t(right.is_true().native_value()));
	}
	return identical(left, right.value());
}

inline bool_t identical(const error_t &left, const error_t &right) {
	return bool_t(
		left.get_message().native_value() == right.get_message().native_value()
		&& left.get_line().native_value() == right.get_line().native_value()
		&& left.get_file().native_value() == right.get_file().native_value()
	);
}

inline bool_t identical(error_sentinel_t, error_sentinel_t) {
	return bool_t(true);
}

template <typename T, typename U>
inline bool_t identical(const result<T> &left, const result<U> &right) {
	if (left.has_value().native_value() && right.has_value().native_value()) {
		return identical(left.value(), right.value());
	}
	if (left.has_error().native_value() && right.has_error().native_value()) {
		return identical(*left.error(), *right.error());
	}
	return bool_t(false);
}

template <typename T>
inline bool_t identical(const result<T> &left, error_sentinel_t) {
	return bool_t(left.has_error().native_value());
}

template <typename T>
inline bool_t identical(error_sentinel_t, const result<T> &right) {
	return identical(right, error_sentinel_t{});
}

template <typename T, typename Right>
inline bool_t identical(const result<T> &left, const Right &right) {
	if constexpr (std::is_same_v<std::remove_cvref_t<Right>, error_sentinel_t>) {
		return left.has_error().native_value() ? bool_t(true) : bool_t(false);
	} else {
		return left.has_error().native_value() ? identical(*left.error(), right) : identical(left.value(), right);
	}
}

template <typename Left, typename T>
inline bool_t identical(const Left &left, const result<T> &right) {
	if constexpr (std::is_same_v<std::remove_cvref_t<Left>, error_sentinel_t>) {
		return right.has_error().native_value() ? bool_t(true) : bool_t(false);
	} else {
		return right.has_error().native_value() ? identical(left, *right.error()) : identical(left, right.value());
	}
}

template <typename NullLike, typename T>
requires (std::is_same_v<std::remove_cvref_t<NullLike>, null_t> || std::is_same_v<std::remove_cvref_t<NullLike>, nullopt_t> || std::is_same_v<std::remove_cvref_t<NullLike>, nullptr_t>)
inline bool_t identical(NullLike, const shared_p<T> &right) {
	return bool_t(!right.has_value().native_value());
}

template <typename T, typename NullLike>
requires (std::is_same_v<std::remove_cvref_t<NullLike>, null_t> || std::is_same_v<std::remove_cvref_t<NullLike>, nullopt_t> || std::is_same_v<std::remove_cvref_t<NullLike>, nullptr_t>)
inline bool_t identical(const shared_p<T> &left, NullLike) {
	return bool_t(!left.has_value().native_value());
}

template <typename NullLike, typename T>
requires (std::is_same_v<std::remove_cvref_t<NullLike>, null_t> || std::is_same_v<std::remove_cvref_t<NullLike>, nullopt_t> || std::is_same_v<std::remove_cvref_t<NullLike>, nullptr_t>)
inline bool_t identical(NullLike, const unique_p<T> &right) {
	return bool_t(!right.has_value().native_value());
}

template <typename T, typename NullLike>
requires (std::is_same_v<std::remove_cvref_t<NullLike>, null_t> || std::is_same_v<std::remove_cvref_t<NullLike>, nullopt_t> || std::is_same_v<std::remove_cvref_t<NullLike>, nullptr_t>)
inline bool_t identical(const unique_p<T> &left, NullLike) {
	return bool_t(!left.has_value().native_value());
}

template <typename T>
inline bool_t identical(const shared_p<T> &left, const shared_p<T> &right) {
	return bool_t(left.get() == right.get());
}

template <typename T>
inline bool_t identical(const T *left, const shared_p<T> &right) {
	return bool_t(left == right.get());
}

template <typename T>
inline bool_t identical(const shared_p<T> &left, const T *right) {
	return bool_t(left.get() == right);
}

template <typename T>
inline bool_t identical(const unique_p<T> &left, const unique_p<T> &right) {
	return bool_t(left.get() == right.get());
}

template <typename T>
inline bool_t identical(const T &left, const T &right) {
	return bool_t(left == right);
}

template <typename Left, typename Right>
requires (!std::is_same_v<std::remove_cvref_t<Left>, std::remove_cvref_t<Right>>)
inline bool_t identical(const Left &, const Right &) {
	return bool_t(false);
}

template <typename Left, typename Right>
inline bool_t not_identical(const Left &left, const Right &right) {
	return !identical(left, right);
}

template <typename T>
inline bool_t identical(const T *left, const T &right) {
	return left == nullptr ? bool_t(false) : bool_t((*left) == right);
}

template <typename T>
inline bool_t identical(const T &left, const T *right) {
	return right == nullptr ? bool_t(false) : bool_t(left == (*right));
}

} // namespace scpp
