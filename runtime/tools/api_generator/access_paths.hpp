#pragma once
#include "value.hpp"

namespace scpp::api_generation
{
    // Physical path composition only; source legality stays in prepared facts.
    value describe_access_path(const value &metadata, const value &leaves,
        const value &names, const value &request);
}
