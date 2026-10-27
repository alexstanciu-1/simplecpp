#pragma once

#include "scpp/native_value_errors.hpp"
#include <limits>
#include <type_traits>

namespace scpp::detail {

// The count is bounded by the declared left representation, before C++ integer
// promotion. See compact_layout_types.md: checked integer shift counts.
template <typename LeftRep, typename RightRep>
inline constexpr void require_shift_count(RightRep count) {
	constexpr int width = std::numeric_limits<std::make_unsigned_t<LeftRep>>::digits;
	if constexpr (std::is_signed_v<RightRep>) {
		if (count < 0) {
			throw ValueError("Shift count must be nonnegative and less than the left operand bit width");
		}
	}
	if (static_cast<std::make_unsigned_t<RightRep>>(count) >= width) {
		throw ValueError("Shift count must be nonnegative and less than the left operand bit width");
	}
}

}
