#pragma once
#include "value.hpp"

namespace scpp::api_generation
{
    void validate_selections(const value &metadata);
    value resolve_coalesce(const value &metadata, const value &request, const value &names);
}
