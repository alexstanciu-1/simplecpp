#include "selections.hpp"
#include "carriers.hpp"
#include "names.hpp"
#include "conditions.hpp"
#include "capabilities.hpp"

namespace scpp::api_generation
{
    static bool contains(const value &list, const std::string &text)
    {
        for (const auto &item : list.items) if (item.text()==text) return true;
        return false;
    }

    static void validate_coalesce_policy(const value &metadata)
    {
        const auto &policy = metadata.at("selections").at("coalesce");
        fields_only(policy, "payloads carriers forms require_selected payload_rules", "coalesce policy");
        if (policy.at("payloads").type!=value::kind::array || policy.at("payloads").items.empty()
            || policy.at("forms").type!=value::kind::array || policy.at("forms").items.empty()
            || policy.at("carriers").type!=value::kind::object)
            throw std::runtime_error("Invalid coalesce domains");
        naming types(metadata_naming_registry(metadata, metadata.at("types"), value::object()));
        for (const auto &payload : policy.at("payloads").items) types.resolve(payload.text());
        for (const auto &form : policy.at("forms").items)
            if (form.text()!="plain_plain" && form.text()!="carrier_plain" && form.text()!="same_carrier")
                throw std::runtime_error("Unknown coalesce join form");
        if (policy.has("payload_rules")) {
            if (policy.at("payload_rules").type!=value::kind::object)
                throw std::runtime_error("Coalesce payload rules require a map");
            for (const auto &[payload, rule] : policy.at("payload_rules").fields) {
                if (!contains(policy.at("payloads"),payload)) throw std::runtime_error("Unknown coalesce payload rule");
                fields_only(rule,"forms plain_usable_expression plain_selected_usable_expression", "coalesce payload rule");
                if (rule.at("forms").type!=value::kind::array || rule.at("forms").items.empty())
                    throw std::runtime_error("Coalesce payload rule requires join forms");
                for (const auto &form : rule.at("forms").items)
                    if (!contains(policy.at("forms"),form.text())) throw std::runtime_error("Unknown coalesce payload join form");
                for (const auto *field : {"plain_usable_expression", "plain_selected_usable_expression"}) {
                    const auto expression=rule.at(field).text();
                    if (expression.empty()) throw std::runtime_error("Empty coalesce observation expression");
                    for (auto at=expression.find("${"); at!=std::string::npos; at=expression.find("${",at+8))
                        if (expression.substr(at,8)!="${value}") throw std::runtime_error("Unknown coalesce observation marker");
                }
            }
        }
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

    static void validate_logical_policy(const value &metadata)
    {
        const auto &policy = metadata.at("selections").at("logical");
        fields_only(policy, "operand_groups carrier_payloads operations", "logical policy");
        if (policy.at("operand_groups").type != value::kind::array || policy.at("operand_groups").items.empty()
            || policy.at("carrier_payloads").type != value::kind::array
            || policy.at("operations").type != value::kind::object || policy.at("operations").fields.empty())
            throw std::runtime_error("Invalid logical domains");
        for (const auto &group : policy.at("operand_groups").items) {
            if (group.type != value::kind::array || group.items.empty())
                throw std::runtime_error("Logical operand group must be nonempty");
            for (const auto &type : group.items)
                if (callable_condition(metadata, type.text()).type == value::kind::null)
                    throw std::runtime_error("Logical operand requires a callable condition");
        }
        for (const auto &type : policy.at("carrier_payloads").items) {
            bool found = false;
            for (const auto &group : policy.at("operand_groups").items) found = found || contains(group, type.text());
            if (!found) throw std::runtime_error("Logical carrier payload is outside operand domains");
        }
        for (const auto &[name, operation] : policy.at("operations").fields) {
            fields_only(operation, "short_circuit", "logical operation");
            if (operation.at("short_circuit").type != value::kind::boolean)
                throw std::runtime_error("Logical short circuit requires boolean");
        }
    }

    value resolve_logical(const value &metadata, const value &request, const value &names)
    {
        fields_only(request, "operation left right", "logical request");
        validate_carriers(metadata);
        validate_logical_policy(metadata);
        const auto &policy = metadata.at("selections").at("logical");
        const auto &operation = policy.at("operations").at(request.at("operation").text());
        naming types(metadata_naming_registry(metadata, metadata.at("types"), names));
        value out = value::object();
        out["kind"] = "lazy_logical";
        out["operation"] = request.at("operation");
        out["result_type"] = "boolean";
        out["short_circuit"] = operation.at("short_circuit");
        out["evaluation"] = "left_once_then_conditional_right_once";
        out["failure"] = "throw_preserve_prior_effects";
        for (const auto *side : {"left", "right"}) {
            const auto canonical = types.resolve(request.at(side).text()).first;
            auto projection = project_carrier(metadata, canonical, names);
            if (projection.type == value::kind::null) {
                projection = value::object(); projection["kind"] = "identity";
                projection["source"] = canonical; projection["target"] = canonical;
            } else {
                if (!contains(policy.at("carrier_payloads"), projection.at("target").text()))
                    throw std::runtime_error("Unsupported logical carrier payload");
                require_carrier_payload(projection);
            }
            value operand = value::object(); operand["projection"] = projection;
            operand["condition"] = callable_condition(metadata, projection.at("target").text());
            out[side] = operand;
        }
        bool admitted = false;
        for (const auto &group : policy.at("operand_groups").items)
            admitted = admitted || (contains(group, out.at("left").at("projection").at("target").text())
                && contains(group, out.at("right").at("projection").at("target").text()));
        if (!admitted) throw std::runtime_error("Unsupported logical operand pair");
        out["steps"] = value::array();
        for (const auto *step : {"evaluate_left_once", "project_left", "observe_left",
                "return_short_circuit_or_continue", "evaluate_right_once", "project_right", "observe_right", "return_boolean"})
            out["steps"].items.emplace_back(step);
        return out;
    }

    void validate_selections(const value &metadata)
    {
        if (!metadata.has("selections")) return;
        const auto &registry = metadata.at("selections");
        fields_only(registry, "coalesce conditional logical", "selections");
        if (registry.has("logical")) validate_logical_policy(metadata);
        if (registry.has("coalesce")) validate_coalesce_policy(metadata);
        if (!registry.has("conditional")) return;
        const auto &policy = registry.at("conditional");
        fields_only(policy, "payloads carriers forms", "conditional selection policy");
        if (policy.at("payloads").type!=value::kind::array || policy.at("payloads").items.empty()
            || policy.at("carriers").type!=value::kind::array || policy.at("forms").type!=value::kind::array)
            throw std::runtime_error("Invalid conditional selection domains");
        naming types(metadata_naming_registry(metadata, metadata.at("types"), value::object()));
        for (const auto &payload : policy.at("payloads").items) types.resolve(payload.text());
        for (const auto &family : policy.at("carriers").items)
            metadata.at("families").at(family.text()).at("carrier");
        for (const auto &form : policy.at("forms").items)
            if (form.text()!="plain_plain" && form.text()!="carrier_plain"
                && form.text()!="plain_carrier" && form.text()!="same_carrier")
                throw std::runtime_error("Unknown conditional selection join form");
    }

    value resolve_conditional(const value &metadata, const value &request, const value &names)
    {
        fields_only(request, "form condition then else", "conditional request");
        const auto form = request.at("form").text();
        if (form!="ternary" && form!="elvis") throw std::runtime_error("Unknown conditional form");
        if ((form=="ternary")!=request.has("condition"))
            throw std::runtime_error("Only full ternary has an independent condition");
        validate_carriers(metadata);
        validate_selections(metadata);
        const auto &policy = metadata.at("selections").at("conditional");
        naming types(metadata_naming_registry(metadata, metadata.at("types"), names));
        value branches = value::object();
        for (const auto *side : {"then", "else"}) {
            const auto canonical = types.resolve(request.at(side).text()).first;
            auto branch = project_carrier(metadata, canonical, names);
            if (branch.type==value::kind::null) {
                branch = value::object(); branch["source"] = canonical; branch["target"] = canonical;
                branch["kind"] = "identity";
            } else if (!contains(policy.at("carriers"), branch.at("family").text()))
                throw std::runtime_error("Unsupported conditional carrier");
            if (!contains(policy.at("payloads"), branch.at("target").text()))
                throw std::runtime_error("Unsupported conditional payload");
            const auto capabilities = type_capabilities(metadata, metadata.at("types"), names, canonical);
            if (!capabilities.has("capabilities") || !contains(capabilities.at("capabilities"), "copy_storage"))
                throw std::runtime_error("Conditional owned result requires copying");
            branches[side] = branch;
        }
        const auto &left = branches.at("then"), &right = branches.at("else");
        const bool left_plain = left.get("kind")=="identity", right_plain = right.get("kind")=="identity";
        const auto join = left_plain && right_plain ? "plain_plain" : !left_plain && right_plain ? "carrier_plain"
            : left_plain && !right_plain ? "plain_carrier" : left.at("source").text()==right.at("source").text() ? "same_carrier" : "unsupported";
        if (left.at("target").text()!=right.at("target").text() || !contains(policy.at("forms"), join))
            throw std::runtime_error("Unsupported conditional join");
        const auto &destination = left_plain ? right : left;
        const auto result = destination.at("source").text();
        value out = value::object(); out["kind"] = "lazy_conditional"; out["form"] = form; out["result_type"] = result;
        for (const auto *side : {"then", "else"}) {
            const auto source = branches.at(side).at("source").text();
            value conversion = value::object(); conversion["kind"] = "identity";
            conversion["source"] = source; conversion["target"] = result;
            if (source!=result) conversion = carrier_payload_construction(destination);
            out[std::string(side)+"_conversion"] = conversion;
        }
        value condition_request = value::object();
        condition_request["source"] = form=="ternary" ? request.at("condition") : request.at("then");
        out["condition"] = resolve_condition(metadata, condition_request, names);
        out["steps"] = value::array();
        if (form=="ternary") {
            for (const auto *step : {"evaluate_condition_once", "convert_condition", "select_one_branch",
                    "evaluate_selected_branch_once", "convert_selected_branch", "return_owned_result"}) out["steps"].items.emplace_back(step);
        } else {
            for (const auto *step : {"evaluate_then_snapshot_once", "convert_saved_condition", "if_true_return_saved_then",
                    "evaluate_else_once", "convert_else", "return_owned_result"}) out["steps"].items.emplace_back(step);
        }
        out["failure"] = "throw_preserve_prior_effects";
        return out;
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
            branch["selected_usable_expression"] = branch.at("usable_expression");
            if (branch.get("kind")=="identity" && policy.has("payload_rules")
                && policy.at("payload_rules").has(canonical)) {
                const auto &rule=policy.at("payload_rules").at(canonical);
                branch["usable_expression"]=rule.at("plain_usable_expression");
                branch["selected_usable_expression"]=rule.at("plain_selected_usable_expression");
            }
            out[side] = branch;
        }
        const auto &left = out.at("left"), &right = out.at("right");
        const auto form = left.get("kind")=="identity" && right.get("kind")=="identity" ? "plain_plain"
            : left.get("kind")!="identity" && right.get("kind")=="identity" ? "carrier_plain"
            : left.get("kind")!="identity" && right.get("kind")!="identity"
                && left.at("source").text()==right.at("source").text() ? "same_carrier" : "unsupported";
        if (left.at("target").text()!=right.at("target").text() || !contains(policy.at("forms"), form))
            throw std::runtime_error("Unsupported coalesce join");
        if (policy.has("payload_rules") && policy.at("payload_rules").has(left.at("target").text()))
            if (!contains(policy.at("payload_rules").at(left.at("target").text()).at("forms"),form))
                throw std::runtime_error("Unsupported coalesce payload join");
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
