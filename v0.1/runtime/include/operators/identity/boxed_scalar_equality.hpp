#pragma once

#include "scpp/generated/operators.hpp"

namespace scpp::detail {

template <typename Box>
bool is_boxed_scalar(const Box &value) {
	using kind = typename Box::kind_t;
	return value.kind() == kind::null_v || value.kind() == kind::bool_v
		|| value.kind() == kind::int_v || value.kind() == kind::float_v || value.kind() == kind::string_v;
}

template <typename Box>
bool_t boxed_scalar_equal(const Box &left, const Box &right) {
	using kind = typename Box::kind_t;
	if (!is_boxed_scalar(left) || !is_boxed_scalar(right))
		throw std::runtime_error("scalar equality requires scalar payloads");
	// Null is an explicit presence comparison, not a truth conversion.
	if (left.kind() == kind::null_v || right.kind() == kind::null_v)
		return bool_t{left.kind() == right.kind()};
	if (left.kind() == kind::bool_v && right.kind() == kind::bool_v)
		return left.get_bool() == right.get_bool();
	if (left.kind() == kind::string_v && right.kind() == kind::string_v)
		return left.get_string() == right.get_string();
	const bool left_numeric = left.kind() == kind::int_v || left.kind() == kind::float_v;
	const bool right_numeric = right.kind() == kind::int_v || right.kind() == kind::float_v;
	if (!left_numeric || !right_numeric)
		throw std::runtime_error("equality: incompatible scalar kinds");
	if (left.kind() == kind::int_v && right.kind() == kind::int_v) return left.get_int() == right.get_int();
	if (left.kind() == kind::int_v) return left.get_int() == right.get_float();
	if (right.kind() == kind::int_v) return left.get_float() == right.get_int();
	return left.get_float() == right.get_float();
}

inline bool_t operator==(const dynamic_value &left, const dynamic_value &right) {
	const auto *left_table = left.table_if();
	const auto *right_table = right.table_if();
	if (left_table || right_table) return bool_t{left_table && left_table == right_table};
	return boxed_scalar_equal(left, right);
}

inline bool_t operator!=(const dynamic_value &left, const dynamic_value &right) {
	return bool_t{!(left == right).native_value()};
}

} // namespace scpp::detail
