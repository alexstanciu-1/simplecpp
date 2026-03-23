#pragma once

#include "scpp/detail.hpp"

namespace scpp {

// Sentinel representing the runtime's pointer-null concept.
//
// Purpose:
// - decouples scpp pointer null semantics from direct use of the C++ keyword nullptr
// - participates in configured equality with null_t and nullopt_t
class nullptr_t final {
public:
	// Implements `nullptr_t` as part of the runtime surface consumed by generated Simple C++ code.
	// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
	constexpr nullptr_t() noexcept = default;
};

// Global sentinel instance. Named null_ptr because `nullptr` is a C++ keyword.
inline constexpr nullptr_t null_ptr {};

} // namespace scpp
