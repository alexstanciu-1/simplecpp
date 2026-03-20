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
	constexpr nullptr_t() noexcept = default;
};

// Global sentinel instance. Named null_ptr because `nullptr` is a C++ keyword.
inline constexpr nullptr_t null_ptr {};

} // namespace scpp
