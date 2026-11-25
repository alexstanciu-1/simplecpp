#pragma once

#include "operators/arithmetic/boxed_numeric.hpp"

namespace scpp::detail {

// Ordering is numeric-only, as for mixed. No string parsing or container ordering.
inline bool_t operator<(const dynamic_value &left, const dynamic_value &right) {
	return boxed_numeric_result<bool_t>(left, right, std::less<>{}, "<");
}
inline bool_t operator<=(const dynamic_value &left, const dynamic_value &right) {
	return boxed_numeric_result<bool_t>(left, right, std::less_equal<>{}, "<=");
}
inline bool_t operator>(const dynamic_value &left, const dynamic_value &right) {
	return boxed_numeric_result<bool_t>(left, right, std::greater<>{}, ">");
}
inline bool_t operator>=(const dynamic_value &left, const dynamic_value &right) {
	return boxed_numeric_result<bool_t>(left, right, std::greater_equal<>{}, ">=");
}

} // namespace scpp::detail
