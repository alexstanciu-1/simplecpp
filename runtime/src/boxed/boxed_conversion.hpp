#pragma once
#include "mixed_value.hpp"
#include "dynamic_value.hpp"
namespace scpp::detail {
// Explicit scalar/null boundary only. Containers always reject, including empty ones.
dynamic_value dynamic_from_mixed_scalar(const mixed_value& value);
mixed_value mixed_from_dynamic_scalar(const dynamic_value& value);
}
