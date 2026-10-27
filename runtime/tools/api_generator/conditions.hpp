#pragma once
#include "value.hpp"

namespace scpp::api_generation
{
    void validate_conditions(const value &metadata);
    value resolve_condition(const value &metadata, const value &request, const value &names);
}
