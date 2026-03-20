#pragma once

#include "scpp/detail.hpp"

namespace scpp {

// Sentinel representing an explicitly empty optional value.
//
// Purpose:
// - mirrors the role of std::nullopt inside the scpp namespace
// - allows nullable<T> construction/reset/comparison without exposing std::nullopt directly
class nullopt_t final {
public:
	constexpr nullopt_t() noexcept = default;
};

// Global sentinel instance used by generated code.
inline constexpr nullopt_t nullopt {};

} // namespace scpp
