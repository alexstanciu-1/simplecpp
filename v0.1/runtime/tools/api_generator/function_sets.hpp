#pragma once
#include "value.hpp"
namespace scpp::api_generation
{
    value instantiate_function(value recipe, const std::string &name, const value &bindings);
    value expand_function_sets(const value &metadata);
}
