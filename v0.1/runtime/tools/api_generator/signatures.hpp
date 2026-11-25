#pragma once
#include "value.hpp"

namespace scpp::api_generation
{
    // Physical signature shape; source admission and pack lowering stay prepared.
    value resolve_signature(const value &metadata, const value &leaves,
        const value &names, const value &request);
}
