#pragma once
#include "value.hpp"
namespace scpp::api_generation
{
    value resolve_api_binding(const value &metadata, const value &leaves,
        const value &names, const value &request);
}
