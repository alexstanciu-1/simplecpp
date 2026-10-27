#pragma once
#include "value.hpp"
namespace scpp::api_generation
{
    value resolve_conversion(const value &metadata, const value &request, const value &names = value::object());
    value conversion_functions(const value &metadata);
}
