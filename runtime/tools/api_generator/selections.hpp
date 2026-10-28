#pragma once
#include "value.hpp"

namespace scpp::api_generation
{
    value resolve_logical(const value &metadata, const value &request, const value &names);
    void validate_selections(const value &metadata);
    value resolve_conditional(const value &metadata, const value &request, const value &names);
    value resolve_coalesce(const value &metadata, const value &request, const value &names);
}
