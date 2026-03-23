#pragma once

#include "scpp/detail.hpp"

namespace scpp {

// Sentinel representing the generic runtime notion of "null".
//
// Purpose:
// - acts as the common null sentinel in generated code
// - participates in configured sentinel equality with nullopt_t and nullptr_t
class null_t final {
public:
	// Implements `null_t` as part of the runtime surface consumed by generated Simple C++ code.
	// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
	constexpr null_t() noexcept = default;
};

// Global sentinel instance used by generated code.
inline constexpr null_t null {};

} // namespace scpp
