#include "api_bindings.hpp"
#include "names.hpp"
#include "function_sets.hpp"
#include <set>

namespace scpp::api_generation
{
    value resolve_api_binding(const value &metadata, const value &leaves,
        const value &names, const value &request)
    {
        fields_only(request, "binding types", "API binding request");
        const auto identity = request.at("binding").text();
        const auto &recipe = metadata.at("api_bindings").at(identity);
        fields_only(recipe, "parameters api", "API binding recipe");
        if (recipe.at("parameters").type != value::kind::array
            || request.at("types").type != value::kind::object)
            throw std::runtime_error("API binding requires parameter array and type map");
        naming types(metadata_naming_registry(metadata, leaves, names));
        value bindings = value::object();
        std::set<std::string> parameters;
        for (const auto &entry : recipe.at("parameters").items) {
            const auto parameter = entry.text();
            authored_name(parameter);
            if (!parameters.insert(parameter).second) throw std::runtime_error("Duplicate API binding parameter");
            const auto canonical = types.resolve(request.at("types").at(parameter).text()).first;
            bindings[parameter] = canonical;
        }
        for (const auto &[parameter, type] : request.at("types").fields)
            if (!parameters.contains(parameter)) throw std::runtime_error("Unexpected API binding parameter: " + parameter);
        naming functions(function_naming_registry(metadata, leaves, names));
        const auto requested = instantiate_function(recipe.at("api"), identity, bindings).text();
        if (!metadata.at("function_families").has(parse_type(requested).name))
            throw std::runtime_error("API binding must select a function family");
        const auto api = functions.bind(requested).type.canonical();
        const auto function = functions.resolve(api).second;
        value out = value::object();
        out["binding"] = identity; out["types"] = bindings;
        out["api"] = api; out["function"] = function;
        return out;
    }
}
