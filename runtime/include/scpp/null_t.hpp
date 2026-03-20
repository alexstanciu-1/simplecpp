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
	constexpr null_t() noexcept = default;
};

// Global sentinel instance used by generated code.
inline constexpr null_t null {};

} // namespace scpp
