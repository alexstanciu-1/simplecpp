#include "operators.hpp"
#include "names.hpp"
#include "function_sets.hpp"
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
        fields_only(registry, "types domains groups operations", "global operators");
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
            fields_only(operation, "group name expression publish returns includes function_set pair_function_set operands distinct_result", "global operation");
            registry.at("groups").at(operation.at("group").text());
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
            for (const auto &rule : registry.at("groups").at(operation.at("group").text()).at("rules").items)
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
        return registry;
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
        return resolve_registered_operator(registry, request, functions);
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
