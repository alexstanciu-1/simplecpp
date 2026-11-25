#pragma once

#include "scpp/generated/operator_detail.hpp"
#include "operators/identity/boxed_ordering.hpp"

namespace scpp::detail {

template <typename Left, typename Right>
inline bool_t relational_less(const Left &left, const Right &right) {
	return generated_operator_detail::lt(left, right);
}
inline bool_t relational_less(const mixed_t &left, const mixed_t &right) {
	return left < right;
}
inline bool_t relational_less(const dynamic_value &left, const dynamic_value &right) {
	return left < right;
}

template <typename Left, typename Right>
inline bool_t relational_less_equal(const Left &left, const Right &right) {
	return generated_operator_detail::le(left, right);
}
inline bool_t relational_less_equal(const mixed_t &left, const mixed_t &right) {
	return left <= right;
}
inline bool_t relational_less_equal(const dynamic_value &left, const dynamic_value &right) {
	return left <= right;
}

template <typename Left, typename Right>
inline bool_t relational_greater(const Left &left, const Right &right) {
	return generated_operator_detail::lt(right, left);
}
inline bool_t relational_greater(const mixed_t &left, const mixed_t &right) {
	return left > right;
}
inline bool_t relational_greater(const dynamic_value &left, const dynamic_value &right) {
	return left > right;
}

template <typename Left, typename Right>
inline bool_t relational_greater_equal(const Left &left, const Right &right) {
	return generated_operator_detail::le(right, left);
}
inline bool_t relational_greater_equal(const mixed_t &left, const mixed_t &right) {
	return left >= right;
}
inline bool_t relational_greater_equal(const dynamic_value &left, const dynamic_value &right) {
	return left >= right;
}

} // namespace scpp::detail
