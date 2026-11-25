#pragma once
#include "value.hpp"

namespace scpp::api_generation
{
    using type_bindings = std::map<std::string, value>;
    value merged_type_leaves(const value &input);
    void validate_capabilities(const value &metadata, const value &record);
    bool capabilities_selected(const value &metadata, const value &record, const type_bindings &types);
    value effective_capabilities(const value &metadata, const value &record, const type_bindings &types);
    value type_capabilities(const value &metadata, const value &leaves, const value &names, const std::string &source);
    void validate_import_capabilities(const value &metadata, const value &leaves, const value &names, const value &imports);
}
