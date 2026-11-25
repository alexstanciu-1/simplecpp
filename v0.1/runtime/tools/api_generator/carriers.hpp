#pragma once
#include "value.hpp"

namespace scpp::api_generation
{
    void validate_carriers(const value &metadata);
    // Returns null for a noncarrier; payload/state facts use the shared naming owner.
    void require_carrier_payload(const value &projection);
    value carrier_payload_construction(const value &projection);
    value project_carrier(const value &metadata, const std::string &source, const value &names);
}
