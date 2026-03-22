#pragma once

#include "scpp/int_t.hpp"
#include <cstdint>
#include <limits>

namespace scpp::php {

// PHP compatibility constants consumed by generated code.
inline const int_t PHP_INT_MAX{static_cast<std::int64_t>(std::numeric_limits<std::int64_t>::max())};

} // namespace scpp::php
