#include "operators.hpp"
#include "names.hpp"
#include <set>

namespace scpp::api_generation
{
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
                fields_only(rule, "left right selection domain", "operator rule");
                const auto left = rule.at("left").text(), right = rule.at("right").text();
                registry.at("domains").at(left); registry.at("domains").at(right);
                registry.at("domains").at(rule.at("domain").text());
                const auto selection = rule.at("selection").text();
                if (selection != "maximum" && selection != "same_or_maximum")
                    throw std::runtime_error("Unknown operator selection policy");
                if (selection == "same_or_maximum" && (left != right || left != rule.at("domain").text()))
                    throw std::runtime_error("Same-type selection requires one domain");
                if (!pairs.emplace(left, right).second)
                    throw std::runtime_error("Ambiguous operator domain rule");
            }
        }
        for (const auto &[name, operation] : registry.at("operations").fields) {
            authored_name(name); authored_name(operation.at("name").text());
            fields_only(operation, "group name expression publish returns includes", "global operation");
            registry.at("groups").at(operation.at("group").text());
            operation.at("expression").text();
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

    value resolve_operator(const value &metadata, const value &request)
    {
        fields_only(request, "operation left right", "operator request");
        const auto &registry = validated_registry(metadata);
        const auto name = request.at("operation").text();
        const auto left = request.at("left").text(), right = request.at("right").text();
        const auto &operation = registry.at("operations").at(name);
        const auto &types = registry.at("types");
        if (!types.has(left) || !types.has(right))
            throw std::runtime_error("Unsupported operator operand type");
        const auto &rules = registry.at("groups").at(operation.at("group").text()).at("rules");
        for (const auto &rule : rules.items) {
            if (rule.at("left").text() != types.at(left).at("domain").text()
                || rule.at("right").text() != types.at(right).at("domain").text()) continue;
            const auto selected = rule.at("selection").text() == "same_or_maximum" && left == right
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
            result["conversion"] = left == selected && right == selected ? "identity" : "ordered_operand_conversion";
            return result;
        }
        throw std::runtime_error("Unsupported operator operand domains; explicit cast required");
    }

    value operator_functions(const value &metadata)
    {
        value functions = value::object();
        if (!metadata.has("global_operators")) return functions;
        const auto &registry = validated_registry(metadata);
        std::set<std::pair<std::string, std::string>> signatures;
        for (const auto &[name, operation] : registry.at("operations").fields) {
            for (const auto &type : operation.at("publish").items) {
                value request = value::object(); request["operation"] = name;
                request["left"] = type; request["right"] = type;
                const auto plan = resolve_operator(metadata, request);
                if (plan.at("operand_type").text() != type.text())
                    throw std::runtime_error("Published operator must have normalized operands");
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
