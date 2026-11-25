#pragma once

#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/runtime_error.hpp"
#include <cmath>

namespace scpp {

// Operands already have the selected same-signedness representation. Compare
// directly: subtraction could overflow or wrap before its sign is observed.
template <typename Rep>
[[nodiscard]] inline int_t<> numeric_compare(const int_t<Rep> &left, const int_t<Rep> &right) {
    const auto a = left.native_value();
    const auto b = right.native_value();
    return int_t<>(a < b ? -1 : a > b ? 1 : 0);
}

// The frontend promotes canonical integer inputs before this three-result comparison.
// Unordered values cannot be represented by -1/0/1 and must not appear equal.
[[nodiscard]] inline int_t<> numeric_compare(const float_t &left, const float_t &right) {
	const auto a = left.native_value();
	const auto b = right.native_value();
	if (std::isnan(a) || std::isnan(b)) {
		throw runtime_error("numeric spaceship cannot order NaN",
			"unordered_comparison", "scpp::numeric_compare", "<=>");
	}
	return int_t<>(a < b ? -1 : a > b ? 1 : 0);
}

} // namespace scpp
