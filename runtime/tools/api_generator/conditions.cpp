#include "conditions.hpp"
#include "carriers.hpp"
#include "function_sets.hpp"
#include "names.hpp"

namespace scpp::api_generation
{
    void validate_conditions(const value &metadata)
    {
        if (!metadata.has("conditions")) return;
        const auto &registry = metadata.at("conditions");
        fields_only(registry, "function_set", "conditions");
        metadata.at("function_sets").at(registry.at("function_set").text());
    }

    static value condition(const value &metadata, const value &functions, const value &names,
        const std::string &source, unsigned depth)
    {
        if (depth > 128) throw std::runtime_error("Condition delegation nesting limit exceeded");
        naming types(metadata_naming_registry(metadata, metadata.at("types"), names));
        const auto canonical = types.resolve(source).first;
        const auto identity = metadata.at("conditions").at("function_set").text() + "_" + canonical;
        value out = value::object(); out["source"] = canonical; out["result_type"] = "boolean";
        if (functions.has(identity)) {
            const auto &function = functions.at(identity), &signature = function.at("operation");
            const auto &parameters = signature.at("parameters").items;
            if (signature.get("returns") != "boolean" || parameters.size() != 1
                || parameters[0].get("type") != canonical || parameters[0].get("passing") != "const_ref")
                throw std::runtime_error("Condition function must borrow its operand and return boolean");
            out["kind"] = "call"; out["api"] = identity; out["header"] = function.at("header");
            out["function"] = function.at("namespace").text() + "::" + signature.at("name").text();
            out["evaluation"] = "evaluate_source_once";
            return out;
        }
        const auto projection = project_carrier(metadata, canonical, names);
        if (projection.type == value::kind::null || !projection.has("states"))
            throw std::runtime_error("Unsupported condition operand");
        // Admission precedes state dispatch, so absence cannot grant truthiness
        // to a payload with no condition contract. Conditions can delegate through
        // an admitted nested carrier; arithmetic/casts retain their one-layer rule.
        out["payload_condition"] = condition(metadata, functions, names, projection.at("target").text(), depth + 1);
        out["kind"] = "carrier_condition"; out["projection"] = projection;
        out["states"] = value::array();
        for (const auto &state : projection.at("states").items) {
            if (!state.has("condition")) throw std::runtime_error("Carrier state has no condition policy");
            value branch = value::object(); branch["name"] = state.at("name");
            if (state.has("test")) branch["test_expression"] = "${value}." + state.at("test").text() + "()";
            branch["condition"] = state.at("condition");
            out["states"].items.push_back(branch);
        }
        out["evaluation"] = "evaluate_source_once_then_select_state";
        out["failure"] = "throw_without_mutating_source";
        return out;
    }

    value resolve_condition(const value &metadata, const value &request, const value &names)
    {
        fields_only(request, "source", "condition request");
        validate_conditions(metadata);
        validate_carriers(metadata);
        return condition(metadata, expand_function_sets(metadata), names, request.at("source").text(), 0);
    }
}
