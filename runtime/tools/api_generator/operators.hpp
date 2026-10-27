#pragma once
#include "value.hpp"

namespace scpp::api_generation
{
    // Resolve admission and common operand types; no source evaluation or execution.
    value resolve_operator(const value &metadata, const value &request);
    value resolve_compound(const value &metadata, const value &request);
    value operator_functions(const value &metadata);
}
