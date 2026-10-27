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
        fields_only(registry, "function_set constants", "conditions");
        metadata.at("function_sets").at(registry.at("function_set").text());
        if (!registry.has("constants")) return;
        if (registry.at("constants").type!=value::kind::object)
            throw std::runtime_error("Condition constants require a type-to-boolean map");
        const auto functions = expand_function_sets(metadata);
        for (const auto &[source, constant] : registry.at("constants").fields) {
            if (!metadata.at("types").has(source) || constant.type!=value::kind::boolean)
                throw std::runtime_error("Constant condition requires an explicit leaf and boolean value");
            if (functions.has(registry.at("function_set").text()+"_"+source))
                throw std::runtime_error("Conflicting constant and callable condition owners");
        }
    }

    value callable_condition(const value &metadata, const std::string &canonical)
    {
        const auto functions = expand_function_sets(metadata);
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
        return value();
    }

    static value condition(const value &metadata, const value &names,
        const std::string &source, unsigned depth)
    {
        if (depth > 128) throw std::runtime_error("Condition delegation nesting limit exceeded");
        naming types(metadata_naming_registry(metadata, metadata.at("types"), names));
        const auto canonical = types.resolve(source).first;
        value out = value::object(); out["source"] = canonical; out["result_type"] = "boolean";
        const auto &registry = metadata.at("conditions");
        if (registry.has("constants") && registry.at("constants").has(canonical)) {
            out["kind"] = "constant_after_evaluation";
            out["value"] = registry.at("constants").at(canonical);
            out["operand_access"] = "none";
            out["evaluation"] = "evaluate_source_once";
            return out;
        }
        const auto callable = callable_condition(metadata, canonical);
        if (callable.type != value::kind::null) return callable;
        const auto projection = project_carrier(metadata, canonical, names);
        if (projection.type == value::kind::null || !projection.has("states"))
            throw std::runtime_error("Unsupported condition operand");
        // Explicit constant observations read no receiver; observing carrier
        // state need not manufacture an owned payload just to return that constant.
        const bool constant_payload = registry.has("constants")
            && registry.at("constants").has(projection.at("target").text());
        const bool scoped_payload = projection.has("condition_observation")
            && callable_condition(metadata, projection.at("target").text()).type != value::kind::null;
        if (!constant_payload && !scoped_payload) require_carrier_payload(projection);
        out["payload_access"] = constant_payload ? "none" : scoped_payload ? "scoped_operation" : "owned_value";
        if (scoped_payload)
            out["payload_expression"] = "${value}." + projection.at("condition_observation").text() + "()";
        // Admission precedes state dispatch, so absence cannot grant truthiness
        // to a payload with no condition contract. Conditions can delegate through
        // an admitted nested carrier; arithmetic/casts retain their one-layer rule.
        out["payload_condition"] = condition(metadata, names, projection.at("target").text(), depth + 1);
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
        return condition(metadata, names, request.at("source").text(), 0);
    }
}
