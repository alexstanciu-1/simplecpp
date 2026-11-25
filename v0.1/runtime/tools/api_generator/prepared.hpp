#pragma once
#include "value.hpp"

namespace scpp::api_generation
{
    // Trusted compiler evidence, validated independently of physical imports/traits.
    value validate_prepared_request(const value &input);
}
