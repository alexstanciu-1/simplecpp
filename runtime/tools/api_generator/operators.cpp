#include "operators.hpp"
#include "names.hpp"
#include "function_sets.hpp"
#include "conversions.hpp"
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
        fields_only(registry, "types domains groups operations compounds", "global operators");
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
            fields_only(operation, "form group name expression publish returns includes function_set pair_function_set operands distinct_result result_expression operand_passing", "global operation");
            const auto form = operation.get("form", "binary");
            if (form != "binary" && form != "unary") throw std::runtime_error("Unknown operator form");
            if (form == "unary") {
                fields_only(operation, "form name publish returns function_set operands result_expression operand_passing", "unary operation");
                operation.at("function_set").text();
                operation.at("operands");
            } else registry.at("groups").at(operation.at("group").text());
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
        if (registry.has("compounds")) {
            const auto &compounds = registry.at("compounds");
            if (compounds.type != value::kind::object) throw std::runtime_error("Compound registry requires an object");
            for (const auto &[name, compound] : compounds.fields) {
                authored_name(name);
                fields_only(compound, "operation rules", "compound operation");
                const auto &operation = registry.at("operations").at(compound.at("operation").text());
                if (operation.get("form", "binary") != "binary") throw std::runtime_error("Compound calculation must be binary");
                if (compound.at("rules").type != value::kind::array || compound.at("rules").items.empty())
                    throw std::runtime_error("Compound rules require a nonempty array");
                std::set<std::pair<std::string, std::string>> pairs;
                for (const auto &rule : compound.at("rules").items) {
                    fields_only(rule, "target right", "compound rule");
                    const auto target = rule.at("target").text();
                    registry.at("types").at(target);
                    for (const auto &right : domains(rule.at("right"))) {
                        registry.at("types").at(right);
                        if (!pairs.emplace(target, right).second) throw std::runtime_error("Ambiguous compound rule");
                    }
                }
            }
        }
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

    value resolve_operator(const value &metadata, const value &request)
    {
        const auto &registry = validated_registry(metadata);
        const auto &operation = registry.at("operations").at(request.at("operation").text());
        const auto functions = (operation.has("function_set") || operation.has("pair_function_set")) ? expand_function_sets(metadata) : value::object();
        if (operation.get("form", "binary") == "unary") return resolve_unary_operator(registry, request, functions);
        return resolve_registered_operator(registry, request, functions);
    }

    static value compound_conversion(const value &metadata, const std::string &source, const std::string &target)
    {
        value request = value::object(); request["source"] = source; request["target"] = target;
        if (source == target) { request["kind"] = "identity"; return request; }
        auto plan = resolve_conversion(metadata, request);
        plan["kind"] = "call";
        return plan;
    }

    value resolve_compound(const value &metadata, const value &request)
    {
        fields_only(request, "operation target right", "compound request");
        const auto &registry = validated_registry(metadata);
        const auto name = request.at("operation").text(), target = request.at("target").text(), right = request.at("right").text();
        const auto &compound = registry.at("compounds").at(name);
        bool admitted = false;
        for (const auto &rule : compound.at("rules").items)
            admitted |= rule.at("target").text() == target && contains(rule.at("right"), right);
        if (!admitted) throw std::runtime_error("Unsupported compound operands");
        value calculation = value::object();
        calculation["operation"] = compound.at("operation"); calculation["left"] = target; calculation["right"] = right;
        const auto plan = resolve_operator(metadata, calculation);
        // This first protocol owns plain, same-result-type write-back only. A
        // conversion that happens to compile cannot authorize destination narrowing.
        if (plan.get("kind") != "call" || !plan.has("operand_type") || plan.get("result_type") != target)
            throw std::runtime_error("Compound requires a common-operand calculation returning the destination type");
        value out = value::object();
        out["operation"] = name; out["target"] = target; out["right"] = right; out["result_type"] = target;
        out["kind"] = "ordered_compound"; out["calculation"] = plan;
        out["left_conversion"] = compound_conversion(metadata, target, plan.at("operand_type").text());
        out["right_conversion"] = compound_conversion(metadata, right, plan.at("operand_type").text());
        out["writeback_conversion"] = compound_conversion(metadata, plan.at("result_type").text(), target);
        out["steps"] = value::array();
        for (const auto *step : {"capture_destination_and_retain_owners", "snapshot_left", "convert_left", "evaluate_right",
                 "convert_right", "calculate", "convert_writeback", "commit_destination", "return_value_snapshot"})
            out["steps"].items.emplace_back(step);
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
                if (operation.has("function_set")) continue;
                const auto identity = plan.at("api").text();
                if (functions.has(identity) || !signatures.emplace(operation.at("name").text(), type.text()).second)
                    throw std::runtime_error("Duplicate normalized operator signature");
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
