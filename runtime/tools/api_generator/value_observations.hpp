#pragma once
#include "value.hpp"
namespace scpp::api_generation
{
    value describe_value_observation(const value &metadata, const value &leaves,
        const value &aliases, const std::string &type, const std::string &observation);
}
