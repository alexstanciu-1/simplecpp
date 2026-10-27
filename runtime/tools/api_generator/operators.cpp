#include "operators.hpp"
#include "names.hpp"
#include "function_sets.hpp"
#include "conversions.hpp"
#include "carriers.hpp"
#include "conditions.hpp"
#include <set>

namespace scpp::api_generation
{
    static std::vector<std::string> domains(const value &selector)
    {
        if (selector.type == value::kind::text) return {selector.text()};
        if (selector.type != value::kind::array || selector.items.empty())
            throw std::runtime_error("Operator domain selector must be text or a nonempty array");
        std::vector<std::string> out;
        for (const auto &item : selector.items) out.push_back(item.text());
        return out;
    }

    static bool contains(const value &values, const std::string &name)
    {
        if (values.type == value::kind::text) return values.text() == name;
        for (const auto &item : values.items) if (item.text() == name) return true;
        return false;
    }

    static const value &validated_registry(const value &metadata)
    {
        const auto &registry = metadata.at("global_operators");
        fields_only(registry, "types domains groups operations compounds operand_conversions carrier_projection carrier_comparisons carrier_literals", "global operators");
        for (const auto *field : {"types", "domains", "groups", "operations"}) {
            if (registry.at(field).type != value::kind::object)
                throw std::runtime_error("Operator registry requires objects");
        }
        for (const auto &[name, type] : registry.at("types").fields) {
            authored_name(name);
            fields_only(type, "domain operand", "operator type");
            registry.at("domains").at(type.at("domain").text());
        }
        for (const auto &[name, domain] : registry.at("domains").fields) {
            fields_only(domain, "maximum", "operator domain");
            const auto &maximum = registry.at("types").at(domain.at("maximum").text());
            if (maximum.at("domain").text() != name)
                throw std::runtime_error("Operator maximum belongs to a different domain");
        }
        for (const auto &[name, group] : registry.at("groups").fields) {
            fields_only(group, "rules", "operator group");
            if (group.at("rules").type != value::kind::array)
                throw std::runtime_error("Operator rules must be an array");
            std::set<std::pair<std::string, std::string>> pairs;
            for (const auto &rule : group.at("rules").items) {
                fields_only(rule, "left right selection domain reverse left_types right_types", "operator rule");
                const auto left = domains(rule.at("left")), right = domains(rule.at("right"));
                for (const auto *side : {"left", "right"}) {
                    const auto filter = std::string(side) + "_types";
                    if (!rule.has(filter)) continue;
                    std::set<std::string> admitted;
                    for (const auto &type : domains(rule.at(filter))) {
                        if (!contains(rule.at(side), registry.at("types").at(type).at("domain").text()))
                            throw std::runtime_error("Operator type restriction is outside its domain");
                        if (!admitted.insert(type).second)
                            throw std::runtime_error("Duplicate operator type restriction");
                    }
                }
                const auto selection = rule.at("selection").text();
                if (selection != "maximum" && selection != "same_or_maximum" && selection != "same_or_constant" && selection != "ordered_pair")
                    throw std::runtime_error("Unknown operator selection policy");
                if (rule.has("reverse") && rule.get("reverse") != "true" && rule.get("reverse") != "false") throw std::runtime_error("Invalid ordered pair argument direction");
                if (rule.has("reverse") && selection != "ordered_pair") throw std::runtime_error("Only ordered pairs may reverse saved arguments");
                if (selection == "same_or_constant" || selection == "ordered_pair") {
                    if (rule.has("domain")) throw std::runtime_error("Preserving selection cannot specify a promotion domain");
                } else registry.at("domains").at(rule.at("domain").text());
                for (const auto &l : left) for (const auto &r : right) {
                    registry.at("domains").at(l); registry.at("domains").at(r);
                    if (selection == "same_or_maximum" && (l != r || l != rule.at("domain").text()))
                        throw std::runtime_error("Same-type selection requires one domain");
                    if (!pairs.emplace(l, r).second) throw std::runtime_error("Ambiguous operator domain rule");
                }
            }
        }
        for (const auto &[name, operation] : registry.at("operations").fields) {
            authored_name(name); authored_name(operation.at("name").text());
            fields_only(operation, "form group name expression publish returns includes function_set pair_function_set operands distinct_result result_expression operand_passing operand_conversion_function_set compound_left_snapshot condition_operand carrier_operands carrier_unary", "global operation");
            const auto form = operation.get("form", "binary");
            if (form != "binary" && form != "unary") throw std::runtime_error("Unknown operator form");
            if (form == "unary") {
                fields_only(operation, "form name publish returns function_set operands result_expression operand_passing condition_operand carrier_unary", "unary operation");
                operation.at("function_set").text();
                operation.at("operands");
            } else registry.at("groups").at(operation.at("group").text());
            if (operation.has("carrier_unary")) {
                const auto &policy = operation.at("carrier_unary");
                fields_only(policy, "payloads result", "carrier unary policy");
                if (form!="unary" || operation.has("condition_operand"))
                    throw std::runtime_error("Carrier unary requires a dedicated unary payload operation");
                for (const auto &type : domains(policy.at("payloads")))
                    if (!contains(operation.at("operands"), type)) throw std::runtime_error("Carrier unary payload is outside scalar admission");
                const auto mode = policy.at("result").text();
                if (mode!="payload" && mode!="updated_carrier" && mode!="old_carrier")
                    throw std::runtime_error("Unknown carrier unary result policy");
                if (operation.get("operand_passing", "const_ref")!=(mode=="payload" ? "const_ref" : "ref"))
                    throw std::runtime_error("Carrier unary result policy disagrees with operand mutation");
            }
            if (operation.has("condition_operand")) {
                if (!operation.yes("condition_operand") || form != "unary" || operation.get("returns") != "boolean"
                    || operation.get("operand_passing", "const_ref") != "const_ref"
                    || operation.get("function_set") != metadata.at("conditions").at("function_set").text())
                    throw std::runtime_error("Condition operand requires the const unary condition owner");
            }
            if (operation.has("carrier_operands")) {
                const auto &policy = operation.at("carrier_operands");
                fields_only(policy, "payloads peers state_cast", "carrier operand conversions");
                if (form!="binary" || !operation.has("operand_conversion_function_set"))
                    throw std::runtime_error("Carrier operand conversion requires a binary conversion owner");
                for (const auto *list : {"payloads", "peers"})
                    for (const auto &type : domains(policy.at(list))) registry.at("types").at(type);
                metadata.at("conversions").at("targets").at(policy.at("state_cast").text());
            }
            if (operation.has("compound_left_snapshot")) {
                if (form != "binary" || operation.get("compound_left_snapshot") != "converted")
                    throw std::runtime_error("Unsupported compound left snapshot mode");
            }
            if (operation.has("operand_conversion_function_set")) {
                if (form != "binary") throw std::runtime_error("Operand conversion owner requires a binary operation");
                metadata.at("function_sets").at(operation.at("operand_conversion_function_set").text());
            }
            if (operation.has("operand_passing")) {
                if (form != "unary") throw std::runtime_error("Operand passing requires a unary operation");
                const auto passing = operation.at("operand_passing").text();
                if (passing != "const_ref" && passing != "ref") throw std::runtime_error("Unsupported unary operand passing");
            }
            if (operation.has("result_expression")) {
                if (form != "unary") throw std::runtime_error("Result expression requires a unary operation");
                const auto expression = operation.at("result_expression").text();
                const std::string marker = "${result}";
                const auto position = expression.find(marker);
                if (position == std::string::npos || expression.find(marker, position + marker.size()) != std::string::npos)
                    throw std::runtime_error("Result expression requires exactly one result marker");
            }
            if (operation.has("expression") == operation.has("function_set"))
                throw std::runtime_error("Operator needs exactly one expression or function set owner");
            if (operation.has("function_set")) {
                metadata.at("function_sets").at(operation.at("function_set").text());
                if (operation.has("includes")) throw std::runtime_error("Function set owns its private includes");
            } else operation.at("expression").text();
            if (operation.has("operands")) {
                if (operation.at("operands").type != value::kind::array) throw std::runtime_error("Operator operands must be an array");
                std::set<std::string> allowed;
                for (const auto &t : operation.at("operands").items) {
                    registry.at("types").at(t.text());
                    if (!allowed.insert(t.text()).second) throw std::runtime_error("Duplicate operator operand admission");
                }
            }
            if (operation.has("pair_function_set")) metadata.at("function_sets").at(operation.at("pair_function_set").text());
            bool constant_rule = false;
            if (form == "binary") for (const auto &rule : registry.at("groups").at(operation.at("group").text()).at("rules").items)
                constant_rule |= rule.at("selection").text() == "same_or_constant";
            if (constant_rule) {
                const auto result = operation.at("distinct_result").text();
                if (result != "true" && result != "false")
                    throw std::runtime_error("Constant selection requires a boolean literal");
                metadata.at("types").at(operation.at("returns").text());
            } else if (operation.has("distinct_result")) throw std::runtime_error("Unused distinct result");
            if (operation.at("publish").type != value::kind::array)
                throw std::runtime_error("Operator publication must be an array");
            std::set<std::string> published;
            for (const auto &type : operation.at("publish").items) {
                registry.at("types").at(type.text());
                if (!published.insert(type.text()).second)
                    throw std::runtime_error("Duplicate operator publication");
            }
        }
        if (registry.has("operand_conversions")) {
            const auto &conversions = registry.at("operand_conversions");
            if (conversions.type != value::kind::object) throw std::runtime_error("Operand conversions require an object");
            for (const auto &[target, conversion] : conversions.fields) {
                registry.at("types").at(target);
                fields_only(conversion, "sources expression header", "operand conversion");
                conversion.at("header").text();
                const auto expression = conversion.at("expression").text();
                const std::string marker = "${value}";
                const auto position = expression.find(marker);
                if (position == std::string::npos || expression.find(marker, position + marker.size()) != std::string::npos)
                    throw std::runtime_error("Operand conversion requires exactly one value marker");
                std::set<std::string> seen;
                for (const auto &source : domains(conversion.at("sources"))) {
                    registry.at("types").at(source);
                    if (source == target || !seen.insert(source).second)
                        throw std::runtime_error("Duplicate or identity operand conversion");
                }
            }
        }
        if (registry.has("compounds")) {
            const auto &compounds = registry.at("compounds");
            if (compounds.type != value::kind::object) throw std::runtime_error("Compound registry requires an object");
            for (const auto &[name, compound] : compounds.fields) {
                authored_name(name);
                fields_only(compound, "operation rules carrier_targets", "compound operation");
                const auto &operation = registry.at("operations").at(compound.at("operation").text());
                if (operation.get("form", "binary") != "binary") throw std::runtime_error("Compound calculation must be binary");
                if (compound.has("carrier_targets")) {
                    if (!operation.has("carrier_operands") || operation.get("compound_left_snapshot")!="converted")
                        throw std::runtime_error("Carrier compound requires converted operand snapshots");
                    for (const auto &target : domains(compound.at("carrier_targets"))) registry.at("types").at(target);
                }
                if (compound.at("rules").type != value::kind::array || compound.at("rules").items.empty())
                    throw std::runtime_error("Compound rules require a nonempty array");
                std::set<std::pair<std::string, std::string>> pairs;
                for (const auto &rule : compound.at("rules").items) {
                    fields_only(rule, "target right writeback_function_set writeback_source", "compound rule");
                    if (rule.has("writeback_function_set")) metadata.at("function_sets").at(rule.at("writeback_function_set").text());
                    const auto target = rule.at("target").text();
                    registry.at("types").at(target);
                    if (rule.has("writeback_source")) {
                        registry.at("types").at(rule.at("writeback_source").text());
                        if (rule.has("writeback_function_set"))
                            throw std::runtime_error("Converted write-back and saved-kind write-back are separate protocols");
                    }
                    for (const auto &right : domains(rule.at("right"))) {
                        registry.at("types").at(right);
                        if (!pairs.emplace(target, right).second) throw std::runtime_error("Ambiguous compound rule");
                    }
                }
            }
        }
        if (registry.has("carrier_projection")) {
            const auto &policy = registry.at("carrier_projection");
            fields_only(policy, "operations payloads", "carrier projection policy");
            for (const auto &op : domains(policy.at("operations"))) {
                if (registry.at("operations").at(op).get("form", "binary") != "binary")
                    throw std::runtime_error("Carrier projection requires binary operations");
            }
            for (const auto &payload : domains(policy.at("payloads"))) registry.at("types").at(payload);
        }
        if (registry.has("carrier_comparisons")) {
            const auto &comparisons = registry.at("carrier_comparisons");
            fields_only(comparisons, "payloads payload_state operations", "carrier comparisons");
            authored_name(comparisons.at("payload_state").text());
            for (const auto &type : domains(comparisons.at("payloads"))) registry.at("types").at(type);
            for (const auto &[name, policy] : comparisons.at("operations").fields) {
                const auto &operation = registry.at("operations").at(name);
                if (operation.get("form", "binary") != "binary" || !operation.has("returns"))
                    throw std::runtime_error("Carrier comparison requires a binary operation with an explicit result type");
                metadata.at("types").at(operation.at("returns").text());
                fields_only(policy, "different_states same_states", "carrier comparison policy");
                const auto different = policy.at("different_states").text();
                if (different != "true" && different != "false") throw std::runtime_error("Invalid different-state result");
                if (policy.at("same_states").type != value::kind::object) throw std::runtime_error("Same-state results must be an object");
                for (const auto &[state, result] : policy.at("same_states").fields) {
                    authored_name(state);
                    if (state == comparisons.at("payload_state").text()) throw std::runtime_error("Payload states require payload comparison");
                    if (result.type == value::kind::object) {
                        fields_only(result, "function_set", "carrier state comparison");
                        metadata.at("function_sets").at(result.at("function_set").text());
                    } else if (result.text() != "true" && result.text() != "false")
                        throw std::runtime_error("Invalid same-state result");
                }
            }
        }
        if (registry.has("carrier_literals")) {
            const auto &literals = registry.at("carrier_literals");
            fields_only(literals, "types operations", "carrier literals");
            for (const auto &type : domains(literals.at("types"))) registry.at("types").at(type);
            for (const auto &[name, policy] : literals.at("operations").fields) {
                const auto &operation = registry.at("operations").at(name);
                if (operation.get("form", "binary") != "binary" || !operation.has("returns"))
                    throw std::runtime_error("Literal comparison requires a binary operation with an explicit result type");
                metadata.at("types").at(operation.at("returns").text());
                fields_only(policy, "matched unmatched", "carrier literal policy");
                for (const auto *key : {"matched", "unmatched"})
                    if (policy.get(key) != "true" && policy.get(key) != "false")
                        throw std::runtime_error("Invalid carrier literal result");
            }
        }
        validate_carriers(metadata);
        return registry;
    }

    static value resolve_unary_operator(const value &registry, const value &request, const value &function_sets)
    {
        fields_only(request, "operation operand", "unary operator request");
        const auto name = request.at("operation").text(), type = request.at("operand").text();
        const auto &operation = registry.at("operations").at(name);
        if (!registry.at("types").has(type) || !contains(operation.at("operands"), type))
            throw std::runtime_error("Unsupported unary operator operand");
        if (!contains(operation.at("publish"), type))
            throw std::runtime_error("Selected unary implementation is not published");
        const auto identity = operation.at("function_set").text() + "_" + type;
        const auto &function = function_sets.at(identity), &signature = function.at("operation");
        const auto result_type = operation.get("returns", type);
        const auto passing = operation.get("operand_passing", "const_ref");
        const auto &parameters = signature.at("parameters").items;
        if (parameters.size() != 1 || parameters[0].at("type").text() != type
            || parameters[0].get("passing") != passing
            || signature.at("returns").text() != result_type
            || signature.at("name").text() != operation.at("name").text())
            throw std::runtime_error("Unary function set signature mismatch");
        value result = value::object();
        result["operation"] = name; result["operand"] = type; result["operand_type"] = type;
        result["result_type"] = result_type; result["kind"] = "call";
        result["api"] = identity; result["header"] = function.at("header");
        result["function"] = function.at("namespace").text() + "::" + signature.at("name").text();
        result["conversion"] = "identity"; result["operand_passing"] = passing;
        result["evaluation"] = passing == "ref" ? "resolve_writable_operand_once" : "evaluate_operand_once";
        if (operation.has("result_expression")) result["result_expression"] = operation.at("result_expression");
        return result;
    }

    static value resolve_registered_operator(const value &registry, const value &request, const value &function_sets)
    {
        fields_only(request, "operation left right", "operator request");
        const auto name = request.at("operation").text();
        const auto left = request.at("left").text(), right = request.at("right").text();
        const auto &operation = registry.at("operations").at(name);
        const auto &types = registry.at("types");
        if (!types.has(left) || !types.has(right))
            throw std::runtime_error("Unsupported operator operand type");
        if (operation.has("operands") && (!contains(operation.at("operands"), left) || !contains(operation.at("operands"), right)))
            throw std::runtime_error("Unsupported operator operand admission");
        const auto &rules = registry.at("groups").at(operation.at("group").text()).at("rules");
        for (const auto &rule : rules.items) {
            if (!contains(rule.at("left"), types.at(left).at("domain").text())
                || !contains(rule.at("right"), types.at(right).at("domain").text())) continue;
            if ((rule.has("left_types") && !contains(rule.at("left_types"), left))
                || (rule.has("right_types") && !contains(rule.at("right_types"), right))) continue;
            const auto selection = rule.at("selection").text();
            if (selection == "ordered_pair") {
                const bool reverse = rule.get("reverse", "false") == "true";
                const auto first = reverse ? right : left, second = reverse ? left : right;
                const auto identity = operation.at("pair_function_set").text() + "_" + first + "_" + second;
                const auto &function = function_sets.at(identity);
                const auto &signature = function.at("operation");
                const auto &parameters = signature.at("parameters").items;
                if (parameters.size() != 2 || parameters[0].at("type").text() != first
                    || parameters[1].at("type").text() != second
                    || parameters[0].get("passing") != "const_ref" || parameters[1].get("passing") != "const_ref"
                    || signature.at("returns").text() != operation.at("returns").text())
                    throw std::runtime_error("Ordered pair function signature mismatch");
                value result = value::object();
                result["operation"] = name; result["left"] = left; result["right"] = right;
                result["kind"] = "call"; result["api"] = identity;
                result["function"] = function.at("namespace").text() + "::" + signature.at("name").text();
                result["header"] = function.at("header"); result["result_type"] = operation.at("returns");
                result["evaluation"] = "ordered_eager_left_then_right";
                result["conversion"] = "identity";
                result["argument_order"] = reverse ? "right_left" : "left_right";
                return result;
            }
            if (selection == "same_or_constant" && left != right) {
                value result = value::object();
                result["operation"] = name; result["left"] = left; result["right"] = right;
                result["kind"] = "constant_after_evaluation";
                result["result_type"] = operation.at("returns"); result["value"] = operation.at("distinct_result");
                result["evaluation"] = "ordered_eager_left_then_right";
                result["conversion"] = "identity";
                return result;
            }
            const auto selected = selection == "same_or_constant" || (selection == "same_or_maximum" && left == right)
                ? left : registry.at("domains").at(rule.at("domain").text()).at("maximum").text();
            bool published = false;
            for (const auto &type : operation.at("publish").items) published |= type.text() == selected;
            if (!published) throw std::runtime_error("Selected operator implementation is not published");
            value result = value::object();
            result["operation"] = name; result["left"] = left; result["right"] = right;
            result["operand_type"] = selected; result["result_type"] = operation.get("returns", selected);
            result["api"] = name + "_" + selected;
            result["function"] = "scpp::" + operation.at("name").text();
            result["header"] = "scpp/operators/" + name + "_" + selected + ".hpp";
            result["kind"] = "call";
            result["evaluation"] = "ordered_eager_left_then_right";
            if (operation.has("function_set")) {
                const auto identity = operation.at("function_set").text() + "_" + selected;
                const auto &function = function_sets.at(identity);
                const auto &signature = function.at("operation");
                if (signature.at("name").text() != operation.at("name").text()
                    || signature.at("returns").text() != result.at("result_type").text()
                    || signature.at("parameters").items.size() != 2)
                    throw std::runtime_error("Operator function set signature mismatch");
                for (const auto &p : signature.at("parameters").items)
                    if (p.at("type").text() != selected || p.get("passing") != "const_ref")
                        throw std::runtime_error("Operator function set operand mismatch");
                result["api"] = identity; result["header"] = function.at("header");
                result["function"] = function.at("namespace").text() + "::" + signature.at("name").text();
            }
            result["conversion"] = left == selected && right == selected ? "identity" : "ordered_operand_conversion";
            return result;
        }
        throw std::runtime_error("Unsupported operator operand domains; explicit cast required");
    }

    static value operand_conversion(const value &metadata, const std::string &source, const std::string &target)
    {
        value request = value::object(); request["source"] = source; request["target"] = target;
        if (source == target) { request["kind"] = "identity"; return request; }
        const auto &registry = metadata.at("global_operators");
        if (registry.has("operand_conversions") && registry.at("operand_conversions").has(target)) {
            const auto &recipe = registry.at("operand_conversions").at(target);
            if (contains(recipe.at("sources"), source)) {
                request["kind"] = "expression"; request["expression"] = recipe.at("expression");
                request["header"] = recipe.at("header");
                return request;
            }
        }
        auto plan = resolve_conversion(metadata, request);
        plan["kind"] = "call";
        return plan;
    }

    static value operation_operand_conversion(const value &metadata, const value &operation,
        const value &functions, const std::string &source, const std::string &target)
    {
        if (source == target || !operation.has("operand_conversion_function_set"))
            return operand_conversion(metadata, source, target);
        const auto identity = operation.at("operand_conversion_function_set").text() + "_" + source;
        const auto &function = functions.at(identity), &signature = function.at("operation");
        const auto &parameters = signature.at("parameters").items;
        if (parameters.size() != 1 || parameters[0].at("type").text() != source
            || parameters[0].get("passing") != "const_ref" || signature.at("returns").text() != target)
            throw std::runtime_error("Operator operand conversion signature mismatch");
        value plan = value::object();
        plan["source"] = source; plan["target"] = target; plan["kind"] = "call";
        plan["api"] = identity; plan["header"] = function.at("header");
        plan["function"] = function.at("namespace").text() + "::" + signature.at("name").text();
        return plan;
    }

    static value resolve_plain_operator(const value &metadata, const value &registry, const value &request)
    {
        const auto &operation = registry.at("operations").at(request.at("operation").text());
        const auto functions = (operation.has("function_set") || operation.has("pair_function_set") || operation.has("operand_conversion_function_set")) ? expand_function_sets(metadata) : value::object();
        if (operation.get("form", "binary") == "unary") return resolve_unary_operator(registry, request, functions);
        auto plan = resolve_registered_operator(registry, request, functions);
        if (plan.has("operand_type")) {
            const auto selected = plan.at("operand_type").text();
            plan["left_conversion"] = operation_operand_conversion(metadata, operation, functions, request.at("left").text(), selected);
            plan["right_conversion"] = operation_operand_conversion(metadata, operation, functions, request.at("right").text(), selected);
        }
        return plan;
    }

    // State exceptions are explicitly selected from the existing cast policy;
    // present payload formatting still belongs to the operation's conversion owner.
    static value resolve_converted_carriers(const value &metadata, const value &registry,
        const value &request, const value &names)
    {
        const auto &operation = registry.at("operations").at(request.at("operation").text());
        const auto &policy = operation.at("carrier_operands");
        value projected = request, projections = value::object();
        naming types(metadata_naming_registry(metadata, metadata.at("types"), names));
        for (const auto *side : {"left", "right"}) {
            const auto canonical = types.resolve(request.at(side).text()).first;
            if (registry.at("types").has(canonical)) {
                if (!contains(policy.at("peers"), canonical))
                    throw std::runtime_error("Unsupported carrier conversion peer");
                projected[side] = canonical;
            } else {
                auto projection = project_carrier(metadata, canonical, names);
                if (projection.type==value::kind::null || !contains(policy.at("payloads"), projection.at("target").text()))
                    throw std::runtime_error("Unsupported carrier conversion payload");
                if (!projection.has("states"))
                    throw std::runtime_error("Carrier operand conversion requires state descriptions");
                require_carrier_payload(projection);
                projected[side] = projection.at("target"); projections[side] = projection;
            }
        }
        // Validate the ordinary operation first; unavailable states never widen admission.
        const auto payload_plan = resolve_plain_operator(metadata, registry, projected);
        const auto target = policy.at("state_cast").text();
        if (payload_plan.get("operand_type") != target)
            throw std::runtime_error("Carrier state conversion must match selected operand type");
        value out = value::object(); out["kind"] = "converted_carrier_binary";
        out["operation"] = request.at("operation"); out["left"] = request.at("left"); out["right"] = request.at("right");
        for (const auto *side : {"left", "right"}) {
            const auto key = std::string(side)+"_conversion";
            out[key] = projections.has(side)
                ? carrier_conversion_plan(projections.at(side), payload_plan.at(key), target)
                : payload_plan.at(key);
        }
        value calculation = request; calculation["left"] = target; calculation["right"] = target;
        out["calculation"] = resolve_plain_operator(metadata, registry, calculation);
        out["result_type"] = out.at("calculation").at("result_type");
        out["steps"] = value::array();
        for (const auto *step : {"evaluate_left", "convert_left", "evaluate_right", "convert_right", "calculate"})
            out["steps"].items.emplace_back(step);
        out["failure"] = "throw_preserve_prior_effects";
        return out;
    }

    static value comparison_states(const value &projection, const std::string &payload)
    {
        value states = value::array();
        if (projection.get("kind") == "identity") {
            value state = value::object(); state["tag"] = payload; states.items.push_back(state); return states;
        }
        if (!projection.has("states")) throw std::runtime_error("Carrier comparison requires state observations");
        for (const auto &source : projection.at("states").items) {
            if (!source.has("comparison_tag")) throw std::runtime_error("Carrier state has no comparison tag");
            value state = value::object(); state["tag"] = source.at("comparison_tag");
            if (source.has("test")) state["test_expression"] = "${value}." + source.at("test").text() + "()";
            if (source.has("comparison_value")) {
                const auto &access = source.at("comparison_value");
                state["projection"] = value::object();
                state["projection"]["target"] = access.at("type");
                state["projection"]["expression"] = "${value}." + access.at("member").text() + "(\"carrier comparison\")";
                state["projection"]["ownership"] = "owned_value";
            }
            states.items.push_back(state);
        }
        return states;
    }

    static value state_comparison_call(const value &functions, const value &policy,
        const value &left, const value &right, const std::string &result_type)
    {
        if (!left.has("projection") || !right.has("projection"))
            throw std::runtime_error("Carrier state comparison requires owned state projections");
        const auto type = left.at("projection").at("target").text();
        if (right.at("projection").at("target").text() != type)
            throw std::runtime_error("Carrier state comparison projection types differ");
        const auto identity = policy.at("function_set").text() + "_" + type;
        const auto &function = functions.at(identity), &signature = function.at("operation");
        const auto &parameters = signature.at("parameters").items;
        if (signature.get("returns") != result_type || parameters.size() != 2)
            throw std::runtime_error("Carrier state comparison signature mismatch");
        for (const auto &parameter : parameters)
            if (parameter.get("type") != type || parameter.get("passing") != "const_ref")
                throw std::runtime_error("Carrier state comparison must borrow its projected values");
        value out = value::object(); out["kind"] = "call"; out["api"] = identity;
        out["header"] = function.at("header"); out["result_type"] = result_type;
        out["function"] = function.at("namespace").text() + "::" + signature.at("name").text();
        out["left_projection"] = left.at("projection"); out["right_projection"] = right.at("projection");
        return out;
    }

    static value resolve_carrier_literal(const value &metadata, const value &registry,
        const value &request, const value &names)
    {
        if (!registry.has("carrier_literals")) return value();
        const auto &policy = registry.at("carrier_literals");
        if (!policy.at("operations").has(request.at("operation").text())) return value();
        if (registry.at("types").has(request.at("left").text()) && registry.at("types").has(request.at("right").text()))
            return value(); // Existing plain scalar/literal plans retain their owner.
        naming types(metadata_naming_registry(metadata, metadata.at("types"), names));
        const auto left = types.resolve(request.at("left").text()).first;
        const auto right = types.resolve(request.at("right").text()).first;
        const bool literal_left = contains(policy.at("types"), left), literal_right = contains(policy.at("types"), right);
        if (literal_left == literal_right) return value();
        const auto projection = project_carrier(metadata, literal_left ? right : left, names);
        if (projection.type == value::kind::null || !projection.has("states")) return value();
        const auto literal = literal_left ? left : right;
        const auto &results = policy.at("operations").at(request.at("operation").text());
        value out = value::object(); out["states"] = value::array(); bool admitted = false;
        for (const auto &state : projection.at("states").items) {
            const bool matches = state.has("literals") && contains(state.at("literals"), literal);
            admitted |= matches;
            value branch = value::object(); branch["name"] = state.at("name");
            if (state.has("test")) branch["test_expression"] = "${value}." + state.at("test").text() + "()";
            branch["value"] = results.at(matches ? "matched" : "unmatched");
            out["states"].items.push_back(branch);
        }
        if (!admitted) throw std::runtime_error("Unsupported carrier literal comparison");
        out["kind"] = "carrier_literal_observation"; out["operation"] = request.at("operation");
        out["left"] = request.at("left"); out["right"] = request.at("right"); out["literal_type"] = literal;
        out["operand_side"] = literal_left ? "right" : "left";
        out["operand_type"] = projection.at("source"); out["header"] = projection.at("header"); out["cpp"] = projection.at("cpp");
        out["result_type"] = registry.at("operations").at(request.at("operation").text()).at("returns");
        out["evaluation"] = "observe_nonliteral_operand_once"; out["operand_passing"] = "const_ref";
        out["payload_access"] = "none";
        return out;
    }

    static value resolve_carrier_unary(const value &metadata, const value &registry,
        const value &request, const value &names)
    {
        fields_only(request, "operation operand", "unary operator request");
        const auto &operation = registry.at("operations").at(request.at("operation").text());
        const auto &policy = operation.at("carrier_unary");
        const auto projection = project_carrier(metadata, request.at("operand").text(), names);
        if (projection.type==value::kind::null || !contains(policy.at("payloads"), projection.at("target").text()))
            throw std::runtime_error("Unsupported carrier unary payload");
        require_carrier_payload(projection);
        value calculation = request; calculation["operand"] = projection.at("target");
        const auto scalar = resolve_plain_operator(metadata, registry, calculation);
        value out = value::object(); out["kind"] = "carrier_unary";
        out["operation"] = request.at("operation"); out["operand"] = projection.at("source");
        out["projection"] = projection; out["calculation"] = scalar;
        const auto mode = policy.at("result").text(); out["result_policy"] = mode;
        out["result_type"] = mode=="payload" ? scalar.at("result_type") : projection.at("source");
        out["steps"] = value::array();
        out["steps"].items.emplace_back(mode=="payload" ? "evaluate_operand_once" : "capture_destination_and_retain_owners");
        out["steps"].items.emplace_back("project_payload");
        if (mode=="old_carrier") out["steps"].items.emplace_back("snapshot_old_carrier");
        out["steps"].items.emplace_back("calculate");
        if (mode!="payload") {
            if (scalar.at("result_type").text()!=projection.at("target").text())
                throw std::runtime_error("Carrier update must preserve payload identity");
            out["writeback_conversion"] = carrier_payload_construction(projection);
            for (const auto *step : {"construct_writeback", "commit_destination", "return_value_snapshot"})
                out["steps"].items.emplace_back(step);
            out["failure"] = "no_writeback_preserve_prior_effects";
        } else {
            out["steps"].items.emplace_back("return_payload_value");
            out["failure"] = "throw_without_mutating_source";
        }
        return out;
    }

    // Project each operand independently; only the ordinary resolver chooses a
    // payload operation. No wrapper-pair overloads and no recursive extraction.
    value resolve_operator(const value &metadata, const value &request, const value &names)
    {
        const auto &registry = validated_registry(metadata);
        const auto &operation = registry.at("operations").at(request.at("operation").text());
        if (operation.get("form", "binary") == "unary" && operation.yes("condition_operand")
            && !registry.at("types").has(request.at("operand").text())) {
            fields_only(request, "operation operand", "unary operator request");
            if (project_carrier(metadata, request.at("operand").text(), names).type == value::kind::null)
                return resolve_plain_operator(metadata, registry, request);
            value condition_request = value::object(); condition_request["source"] = request.at("operand");
            value plan = value::object(); plan["kind"] = "condition_operator";
            plan["operation"] = request.at("operation"); plan["operand"] = request.at("operand");
            plan["condition"] = resolve_condition(metadata, condition_request, names);
            plan["result_type"] = "boolean";
            if (operation.has("result_expression")) plan["result_expression"] = operation.at("result_expression");
            return plan;
        }
        if (operation.get("form", "binary")=="unary" && operation.has("carrier_unary")
            && !registry.at("types").has(request.at("operand").text()))
            return resolve_carrier_unary(metadata, registry, request, names);
        if (operation.get("form", "binary") == "binary") {
            fields_only(request, "operation left right", "operator request");
            const auto literal = resolve_carrier_literal(metadata, registry, request, names);
            if (literal.type != value::kind::null) return literal;
        }
        if (operation.get("form", "binary") != "binary"
            || (registry.at("types").has(request.at("left").text())
                && registry.at("types").has(request.at("right").text())))
            return resolve_plain_operator(metadata, registry, request);
        fields_only(request, "operation left right", "operator request");
        if (operation.has("carrier_operands")) return resolve_converted_carriers(metadata, registry, request, names);
        const bool compare = registry.has("carrier_comparisons")
            && registry.at("carrier_comparisons").at("operations").has(request.at("operation").text());
        if (!compare && (!registry.has("carrier_projection")
            || !contains(registry.at("carrier_projection").at("operations"), request.at("operation").text())))
            throw std::runtime_error("Unsupported operator operand type");
        const auto &policy = registry.at(compare ? "carrier_comparisons" : "carrier_projection");
        naming types(metadata_naming_registry(metadata, metadata.at("types"), names));
        value out = value::object(), calculation = request;
        bool projected = false;
        for (const auto *side : {"left", "right"}) {
            const auto [identity, public_name] = types.resolve(request.at(side).text());
            value projection = value::object();
            projection["source"] = identity; projection["target"] = identity;
            projection["kind"] = "identity";
            if (!registry.at("types").has(identity)) {
                projection = project_carrier(metadata, identity, names);
                if (projection.type == value::kind::null) throw std::runtime_error("Operand family has no carrier projection");
                projected = true;
            }
            if (!contains(policy.at("payloads"), projection.at("target").text()))
                throw std::runtime_error("Unsupported carrier payload; only one layer is projected");
            require_carrier_payload(projection);
            calculation[side] = projection.at("target");
            out[std::string(side) + "_projection"] = projection;
        }
        if (!projected) return resolve_plain_operator(metadata, registry, calculation);
        out["calculation"] = resolve_plain_operator(metadata, registry, calculation);
        out["operation"] = request.at("operation"); out["left"] = request.at("left"); out["right"] = request.at("right");
        out["result_type"] = out.at("calculation").at("result_type");
        if (compare) {
            const auto payload = policy.at("payload_state").text();
            const auto &comparison = policy.at("operations").at(request.at("operation").text());
            out["left_states"] = comparison_states(out.at("left_projection"), payload);
            out["right_states"] = comparison_states(out.at("right_projection"), payload);
            out["same_states"] = value::object(); out["same_state_calculations"] = value::object();
            const auto functions = expand_function_sets(metadata);
            // At most a small state vocabulary, never a family/type overload product.
            for (const auto &left : out.at("left_states").items)
                for (const auto &right : out.at("right_states").items)
                    if (left.at("tag").text() == right.at("tag").text() && left.at("tag").text() != payload) {
                        const auto tag = left.at("tag").text();
                        if (!comparison.at("same_states").has(tag))
                            throw std::runtime_error("Unsupported carrier comparison state: " + tag);
                        const auto &selected = comparison.at("same_states").at(tag);
                        if (selected.type == value::kind::object)
                            out["same_state_calculations"][tag] = state_comparison_call(functions, selected, left, right, out.at("result_type").text());
                        else out["same_states"][tag] = selected;
                    }
            out["kind"] = "state_comparison"; out["payload_state"] = payload;
            out["different_states"] = comparison.at("different_states");
            out["steps"] = value::array();
            for (const auto *step : {"evaluate_left_snapshot", "evaluate_right_snapshot", "observe_states",
                     "compare_states", "project_present_payloads", "convert_payloads", "compare_payloads"})
                out["steps"].items.emplace_back(step);
            out["failure"] = "throw_preserve_prior_effects";
            return out;
        }
        out["kind"] = "checked_payload_binary";
        out["steps"] = value::array();
        for (const auto *step : {"evaluate_left", "project_left", "convert_left", "evaluate_right",
                 "project_right", "convert_right", "calculate"}) out["steps"].items.emplace_back(step);
        out["failure"] = "throw_preserve_prior_effects";
        return out;
    }

    value resolve_compound(const value &metadata, const value &request, const value &names)
    {
        fields_only(request, "operation target right", "compound request");
        const auto &registry = validated_registry(metadata);
        const auto name = request.at("operation").text(), target = request.at("target").text(), right = request.at("right").text();
        const auto &compound = registry.at("compounds").at(name);
        const value *selected_rule = nullptr;
        for (const auto &rule : compound.at("rules").items)
            if (rule.at("target").text() == target && contains(rule.at("right"), right)) selected_rule = &rule;
        value carrier_rule = value::object(), carrier_writeback;
        if (!selected_rule && compound.has("carrier_targets")
            && (!registry.at("types").has(target) || !registry.at("types").has(right))) {
            naming types(metadata_naming_registry(metadata, metadata.at("types"), names));
            const auto canonical = types.resolve(target).first;
            auto storage = canonical;
            value destination;
            if (!registry.at("types").has(canonical)) {
                destination = project_carrier(metadata, canonical, names);
                if (destination.type!=value::kind::null) storage = destination.at("target").text();
            }
            if (!contains(compound.at("carrier_targets"), storage))
                throw std::runtime_error("Unsupported compound destination payload");
            carrier_rule["writeback_source"] = storage;
            if (destination.type!=value::kind::null) {
                require_carrier_payload(destination);
                carrier_writeback = carrier_payload_construction(destination);
            }
            selected_rule = &carrier_rule;
        }
        if (!selected_rule) throw std::runtime_error("Unsupported compound operands");
        value calculation = value::object();
        calculation["operation"] = compound.at("operation"); calculation["left"] = target; calculation["right"] = right;
        const auto binary = resolve_operator(metadata, calculation, names);
        const auto &plan = binary.get("kind")=="converted_carrier_binary" ? binary.at("calculation") : binary;
        // A differing result needs explicit admission; an available conversion
        // alone cannot authorize narrowing or replacement of the destination kind.
        const auto writeback_source = selected_rule->get("writeback_source", target);
        if (plan.get("kind") != "call" || !plan.has("operand_type") || plan.get("result_type") != writeback_source)
            throw std::runtime_error("Compound calculation must return the declared write-back source");
        value out = value::object();
        out["operation"] = name; out["target"] = target; out["right"] = right; out["result_type"] = target;
        out["kind"] = "ordered_compound"; out["calculation"] = plan;
        out["left_conversion"] = binary.at("left_conversion");
        out["right_conversion"] = binary.at("right_conversion");
        out["writeback_conversion"] = carrier_writeback.type!=value::kind::null
            ? carrier_writeback : operand_conversion(metadata, plan.at("result_type").text(), target);
        if (selected_rule->has("writeback_function_set")) {
            const auto identity = selected_rule->at("writeback_function_set").text() + "_" + target;
            const auto functions = expand_function_sets(metadata);
            const auto &function = functions.at(identity), &signature = function.at("operation");
            const auto &parameters = signature.at("parameters").items;
            if (parameters.size() != 2 || signature.at("returns").text() != target)
                throw std::runtime_error("Compound write-back signature mismatch");
            for (const auto &parameter : parameters)
                if (parameter.at("type").text() != target || parameter.get("passing") != "const_ref")
                    throw std::runtime_error("Compound write-back must borrow saved left and calculated result");
            auto &writeback = out["writeback_conversion"];
            writeback["kind"] = "checked_saved_value"; writeback["api"] = identity;
            writeback["header"] = function.at("header");
            writeback["function"] = function.at("namespace").text() + "::" + signature.at("name").text();
            writeback["arguments"] = value::array();
            writeback["arguments"].items = {value("old_left"), value("calculated")};
        }
        const auto &operation = registry.at("operations").at(compound.at("operation").text());
        const bool converted_snapshot = operation.get("compound_left_snapshot") == "converted";
        if (converted_snapshot && selected_rule->has("writeback_function_set"))
            throw std::runtime_error("Saved-kind write-back requires the original left snapshot");
        out["steps"] = value::array();
        out["steps"].items.emplace_back("capture_destination_and_retain_owners");
        if (converted_snapshot) out["steps"].items.emplace_back("snapshot_converted_left");
        else {
            out["steps"].items.emplace_back("snapshot_left");
            out["steps"].items.emplace_back("convert_left");
        }
        for (const auto *step : {"evaluate_right", "convert_right", "calculate", "convert_writeback",
                 "commit_destination", "return_value_snapshot"}) out["steps"].items.emplace_back(step);
        out["failure"] = "no_writeback_preserve_prior_effects";
        return out;
    }

    value operator_functions(const value &metadata)
    {
        value functions = value::object();
        if (!metadata.has("global_operators")) return functions;
        const auto &registry = validated_registry(metadata);
        const auto function_sets = expand_function_sets(metadata);
        std::set<std::pair<std::string, std::string>> signatures;
        for (const auto &[name, operation] : registry.at("operations").fields) {
            for (const auto &type : operation.at("publish").items) {
                value request = value::object(); request["operation"] = name;
                if (operation.get("form", "binary") == "unary") {
                    request["operand"] = type;
                    (void)resolve_unary_operator(registry, request, function_sets);
                    continue;
                }
                request["left"] = type; request["right"] = type;
                const auto plan = resolve_registered_operator(registry, request, function_sets);
                if (plan.at("operand_type").text() != type.text())
                    throw std::runtime_error("Published operator must have normalized operands");
                if (!signatures.emplace(operation.at("name").text(), type.text()).second)
                    throw std::runtime_error("Duplicate normalized operator signature");
                if (operation.has("function_set")) continue;
                const auto identity = plan.at("api").text();
                if (functions.has(identity)) throw std::runtime_error("Duplicate normalized operator identity");
                value function = value::object(), op = value::object();
                function["namespace"] = "scpp"; function["header"] = plan.at("header");
                function["source"] = "include/scpp/generated/public_" + identity + ".cpp";
                op["name"] = operation.at("name"); op["returns"] = operation.get("returns", type.text());
                op["parameters"] = value::array();
                for (const auto *name : {"left", "right"}) {
                    value parameter = value::object(); parameter["name"] = name;
                    parameter["type"] = type; parameter["passing"] = "const_ref";
                    op["parameters"].items.push_back(parameter);
                }
                auto expression = operation.at("expression").text();
                const std::string marker = "${public:Selected}";
                for (auto position = expression.find(marker); position != std::string::npos; position = expression.find(marker))
                    expression.replace(position, marker.size(), "${public:" + type.text() + "}");
                for (const auto *argument : {"left", "right"}) {
                    const std::string token = std::string("${operand:") + argument + "}";
                    if (expression.find(token) == std::string::npos) continue;
                    auto operand = registry.at("types").at(type.text()).at("operand").text();
                    const std::string value_token = "${value}";
                    for (auto pos = operand.find(value_token); pos != std::string::npos; pos = operand.find(value_token))
                        operand.replace(pos, value_token.size(), argument);
                    for (auto pos = expression.find(token); pos != std::string::npos; pos = expression.find(token))
                        expression.replace(pos, token.size(), operand);
                }
                op["uses"] = value::array(); op["uses"].items.push_back(type);
                op["expression"] = expression;
                if (operation.has("includes")) op["includes"] = operation.at("includes");
                function["operation"] = op;
                functions[identity] = function;
            }
        }
        return functions;
    }
}
