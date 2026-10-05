#pragma once

#include "scpp/support/dynamic_value.hpp"

namespace scpp {

// Public boxed dynamic family. Containers share identity; scalar bindings copy values.
// The old typed shared-hash spelling is deliberately not a compatibility surface.
template <typename... Args> requires (sizeof...(Args) == 0)
using dynamic_t = detail::dynamic_value;

} // namespace scpp
