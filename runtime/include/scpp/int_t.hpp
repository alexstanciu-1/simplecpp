#pragma once

#include "scpp/detail.hpp"

namespace scpp {

class float_t;

// Semantic integer wrapper over a fixed native representation.
//
// Enforces:
// - integer values remain inside scpp::int_t at API boundaries
// - native arithmetic escapes are explicit via generated operator helpers
template <typename Rep>
class int_t final {
	static_assert(std::is_integral_v<Rep>, "scpp::int_t<Rep> requires an integral representation");
	static_assert(!std::is_same_v<Rep, bool>, "scpp::int_t<Rep> does not support bool as a representation");

private:
	Rep value_;

public:
	constexpr int_t() noexcept
		: value_(0) {
	}

	template <typename Value>
		requires (std::is_integral_v<detail::remove_cvref_t<Value>> && !std::is_same_v<detail::remove_cvref_t<Value>, bool>)
	explicit constexpr int_t(Value value) noexcept
		: value_(static_cast<Rep>(value)) {
	}

	[[nodiscard]] constexpr Rep native_value() const noexcept {
		return value_;
	}
};

} // namespace scpp
