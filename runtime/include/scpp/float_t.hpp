#pragma once

#include "scpp/detail.hpp"
#include "scpp/int_t.hpp"

namespace scpp {

// Semantic 64-bit floating-point wrapper.
//
// Enforces:
// - floating-point values remain inside scpp::float_t at API boundaries
// - configured widening from int_t stays explicit in one constructor
class float_t final {
private:
	double value_;

public:
	constexpr float_t() noexcept
		: value_(0.0) {
	}

	explicit constexpr float_t(double value) noexcept
		: value_(value) {
	}

	constexpr float_t(const int_t &value) noexcept
		: value_(static_cast<double>(value.native_value())) {
	}

	[[nodiscard]] constexpr double native_value() const noexcept {
		return value_;
	}
};

} // namespace scpp
