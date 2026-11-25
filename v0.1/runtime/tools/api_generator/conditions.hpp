#pragma once
#include "value.hpp"

namespace scpp::api_generation
{
    value callable_condition(const value &metadata, const std::string &canonical);
    value scoped_condition(const value &metadata, const std::string &source, const value &names);
    void validate_conditions(const value &metadata);
    value resolve_condition(const value &metadata, const value &request, const value &names);
}
