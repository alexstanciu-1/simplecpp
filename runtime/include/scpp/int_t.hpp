#pragma once

#include "scpp/detail.hpp"

namespace scpp {

class float_t;

// Semantic signed 64-bit integer wrapper.
//
// Enforces:
// - integer values remain inside scpp::int_t at API boundaries
// - native arithmetic escapes are explicit via generated operator helpers
class int_t final {
private:
	std::int64_t value_;

public:
	constexpr int_t() noexcept
		: value_(0) {
	}

	explicit constexpr int_t(std::int64_t value) noexcept
		: value_(value) {
	}

	[[nodiscard]] constexpr std::int64_t native_value() const noexcept {
		return value_;
	}
};

} // namespace scpp
