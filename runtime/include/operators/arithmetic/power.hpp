#pragma once

#include "scpp/int_t.hpp"
#include "scpp/runtime_error.hpp"

#include <cstdint>
#include <limits>

namespace scpp {
namespace detail {

// Unsigned magnitudes represent abs(INT64_MIN) without negating a signed minimum.
[[nodiscard]] inline std::uint64_t power_product(
	std::uint64_t left, std::uint64_t right, std::uint64_t limit) {
	if (right != 0 && left > limit / right) {
		throw runtime_error("integer power exceeds the signed 64-bit range",
			"power_overflow", "scpp::pow", "**");
	}
	return left * right;
}

} // namespace detail

// Exact canonical-int power. Domain and overflow checks precede unsafe arithmetic.
[[nodiscard]] inline int_t<> pow(const int_t<> &base, const int_t<> &exponent) {
	const auto signed_exponent = exponent.native_value();
	if (signed_exponent < 0) {
		throw runtime_error("integer power requires a nonnegative exponent",
			"power_negative_exponent", "scpp::pow", "**");
	}
	const auto signed_base = base.native_value();
	auto remaining = static_cast<std::uint64_t>(signed_exponent);
	const bool negative = signed_base < 0 && (remaining & 1U) != 0;
	const auto maximum = static_cast<std::uint64_t>(std::numeric_limits<std::int64_t>::max());
	const auto limit = negative ? maximum + 1U : maximum;
	auto factor = signed_base < 0
		? std::uint64_t{0} - static_cast<std::uint64_t>(signed_base)
		: static_cast<std::uint64_t>(signed_base);
	std::uint64_t result = 1;
	while (remaining != 0) {
		if ((remaining & 1U) != 0) {
			result = detail::power_product(result, factor, limit);
		}
		remaining >>= 1U;
		// Do not square an unused factor: MAX ** 1 and MIN ** 1 are valid.
		if (remaining != 0) {
			factor = detail::power_product(factor, factor, limit);
		}
	}
	if (negative && result == maximum + 1U) {
		return int_t<>(std::numeric_limits<std::int64_t>::min());
	}
	const auto magnitude = static_cast<std::int64_t>(result);
	return int_t<>(negative ? -magnitude : magnitude);
}

} // namespace scpp
