#include "selections.hpp"
#include "carriers.hpp"
#include "names.hpp"

namespace scpp::api_generation
{
    static bool contains(const value &list, const std::string &text)
    {
        for (const auto &item : list.items) if (item.text()==text) return true;
        return false;
    }

    void validate_selections(const value &metadata)
    {
        if (!metadata.has("selections")) return;
        const auto &registry = metadata.at("selections");
        fields_only(registry, "coalesce", "selections");
        const auto &policy = registry.at("coalesce");
        fields_only(policy, "payloads carriers forms require_selected", "coalesce policy");
        if (policy.at("payloads").type!=value::kind::array || policy.at("payloads").items.empty()
            || policy.at("forms").type!=value::kind::array || policy.at("forms").items.empty()
            || policy.at("carriers").type!=value::kind::object)
            throw std::runtime_error("Invalid coalesce domains");
        naming types(metadata_naming_registry(metadata, metadata.at("types"), value::object()));
        for (const auto &payload : policy.at("payloads").items) types.resolve(payload.text());
        for (const auto &form : policy.at("forms").items)
            if (form.text()!="plain_plain" && form.text()!="carrier_plain" && form.text()!="same_carrier")
                throw std::runtime_error("Unknown coalesce join form");
        for (const auto &[family, payload_state] : policy.at("carriers").fields) {
            const auto &states = metadata.at("families").at(family).at("carrier").at("states");
            unsigned matches = 0;
            for (const auto &state : states.items) if (state.at("name").text()==payload_state.text()) {
                if (!state.has("test") || !state.has("reconstruct") || state.at("reconstruct").get("kind")!="payload")
                    throw std::runtime_error("Coalesce usability requires an observed payload state");
                ++matches;
            }
            if (matches!=1) throw std::runtime_error("Coalesce requires one payload state");
        }
        const auto &function = metadata.at("functions").at(policy.at("require_selected").text());
        const auto &signature = function.at("operation");
        const auto &parameters = signature.at("parameters").items;
        if (signature.get("returns")!="void" || parameters.size()!=1
            || parameters[0].get("type")!="boolean" || parameters[0].get("passing")!="const_ref")
            throw std::runtime_error("Coalesce selected-value check must borrow boolean and return void");
    }

    value resolve_coalesce(const value &metadata, const value &request, const value &names)
    {
        fields_only(request, "left right", "coalesce request");
        validate_carriers(metadata);
        validate_selections(metadata);
        const auto &policy = metadata.at("selections").at("coalesce");
        naming types(metadata_naming_registry(metadata, metadata.at("types"), names));
        value out = value::object();
        for (const auto *side : {"left", "right"}) {
            const auto canonical = types.resolve(request.at(side).text()).first;
            auto branch = project_carrier(metadata, canonical, names);
            if (branch.type==value::kind::null) {
                branch = value::object(); branch["source"] = canonical; branch["target"] = canonical;
                branch["kind"] = "identity"; branch["usable_expression"] = "true";
            } else {
                const auto family = branch.at("family").text();
                if (!policy.at("carriers").has(family)) throw std::runtime_error("Unsupported coalesce carrier");
                require_carrier_payload(branch);
                for (const auto &state : branch.at("states").items)
                    if (state.at("name").text()==policy.at("carriers").at(family).text())
                        branch["usable_expression"] = "${value}." + state.at("test").text() + "()";
            }
            if (!contains(policy.at("payloads"), branch.at("target").text()))
                throw std::runtime_error("Unsupported coalesce payload");
            out[side] = branch;
        }
        const auto &left = out.at("left"), &right = out.at("right");
        const auto form = left.get("kind")=="identity" && right.get("kind")=="identity" ? "plain_plain"
            : left.get("kind")!="identity" && right.get("kind")=="identity" ? "carrier_plain"
            : left.get("kind")!="identity" && right.get("kind")!="identity"
                && left.at("source").text()==right.at("source").text() ? "same_carrier" : "unsupported";
        if (left.at("target").text()!=right.at("target").text() || !contains(policy.at("forms"), form))
            throw std::runtime_error("Unsupported coalesce join");
        const auto identity = policy.at("require_selected").text();
        const auto &function = metadata.at("functions").at(identity);
        out["require_selected"] = value::object();
        out["require_selected"]["api"] = identity;
        out["require_selected"]["header"] = function.at("header");
        out["require_selected"]["function"] = function.at("namespace").text()+"::"+function.at("operation").at("name").text();
        out["kind"] = "lazy_coalesce"; out["result_type"] = left.at("target");
        out["steps"] = value::array();
        for (const auto *step : {"evaluate_left_once", "observe_left_usability", "if_usable_return_owned_left",
                "evaluate_right_once", "require_right_usability", "return_owned_right"}) out["steps"].items.emplace_back(step);
        out["failure"] = "throw_preserve_prior_effects";
        return out;
    }
}
