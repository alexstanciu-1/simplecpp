#include "prepared.hpp"
#include "names.hpp"
#include "capabilities.hpp"
#include <set>

namespace scpp::api_generation
{
    static std::string required_text(const value &record, const std::string &field)
    {
        const auto &entry = record.at(field);
        if (entry.type != value::kind::text || entry.text().empty())
            throw std::runtime_error("Prepared request requires nonempty " + field);
        return entry.text();
    }

    // Request protocol fields, not semantic type or capability rules.
    static std::vector<std::string> binding_slots(const value &input)
    {
        const auto action = input.get("action");
        const auto &request = input.at("request");
        if (action == "resolve_operator")
            return request.has("operand") ? std::vector<std::string>{"operand"}
                : std::vector<std::string>{"left", "right"};
        if (action == "resolve_conditional")
            return request.has("condition") ? std::vector<std::string>{"condition", "then", "else"}
                : std::vector<std::string>{"then", "else"};
        static const std::map<std::string, std::vector<std::string>> protocols = {
            {"resolve_logical", {"left", "right"}}, {"resolve_coalesce", {"left", "right"}},
            {"resolve_condition", {"source"}}, {"resolve_conversion", {"source", "target"}},
            {"resolve_compound", {"target", "right"}}
        };
        const auto found = protocols.find(action);
        if (found == protocols.end()) throw std::runtime_error("Prepared source action is not implemented");
        return found->second;
    }

    static std::map<std::string, std::string> requested_types(const value &input)
    {
        std::map<std::string, std::string> result;
        if (input.get("action") == "generate") {
            const auto &roots = input.at("request").at("roots");
            if (roots.type != value::kind::array) throw std::runtime_error("Prepared roots require an array");
            for (std::size_t index = 0; index < roots.items.size(); ++index)
                result["root_" + std::to_string(index)] = roots.items[index].text();
            if (input.at("request").has("apis") && input.at("metadata").has("function_families")) {
                naming functions(function_naming_registry(input.at("metadata"), merged_type_leaves(input),
                    input.has("names") ? input.at("names") : value::object()));
                if (input.at("request").at("apis").type != value::kind::array)
                    throw std::runtime_error("API demands require an array");
                const auto &apis = input.at("request").at("apis").items;
                for (std::size_t index = 0; index < apis.size(); ++index) {
                    const auto requested = parse_type(apis[index].text());
                    if (!input.at("metadata").at("function_families").has(requested.name)) continue;
                    for (const auto &[parameter, argument] : functions.bind(apis[index].text()).arguments)
                        result["api_" + std::to_string(index) + "_" + parameter] = argument.canonical();
                }
            }
        } else {
            for (const auto &slot : binding_slots(input)) result[slot] = input.at("request").at(slot).text();
        }
        return result;
    }

    static void validate_binding(const value &binding, const std::string &expected, naming &types)
    {
        fields_only(binding, "source physical arguments", "prepared type binding");
        required_text(binding, "source");
        const auto physical = required_text(binding, "physical");
        const auto canonical = types.resolve(physical).first;
        if (physical != canonical || canonical != types.resolve(expected).first)
            throw std::runtime_error("Prepared physical binding mismatch: " + expected);
        if (binding.at("arguments").type != value::kind::object)
            throw std::runtime_error("Prepared source arguments require a map");
        for (const auto &[parameter, argument] : binding.at("arguments").fields) {
            if (parameter.empty()) throw std::runtime_error("Prepared source parameter name is empty");
            required_text(binding.at("arguments"), parameter);
        }
    }

    value validate_prepared_request(const value &input)
    {
        const auto mode = input.get("mode", "native");
        if (mode == "native") {
            if (input.has("prepared")) throw std::runtime_error("Prepared facts require source mode");
            return value();
        }
        if (mode != "source") throw std::runtime_error("Unknown API request mode");
        if (!input.has("prepared")) throw std::runtime_error("Source request requires prepared facts");
        const auto &prepared = input.at("prepared");
        fields_only(prepared, "version producer revision action request admission bindings dependencies requirements facts", "prepared request");
        if (required_text(prepared, "version") != "prepared-api-v1")
            throw std::runtime_error("Unsupported prepared request version");
        required_text(prepared, "producer"); required_text(prepared, "revision");
        required_text(prepared, "admission");
        if (required_text(prepared, "action") != input.get("action")
            || json_text(prepared.at("request")) != json_text(input.at("request")))
            throw std::runtime_error("Prepared admission does not match requested operation");
        const auto requested = requested_types(input);
        const auto &bindings = prepared.at("bindings");
        std::string allowed;
        for (const auto &[slot, type] : requested) allowed += slot + " ";
        fields_only(bindings, allowed, "prepared bindings");
        naming types(metadata_naming_registry(input.at("metadata"), merged_type_leaves(input),
            input.has("names") ? input.at("names") : value::object()));
        std::set<std::string> subjects{"operation"};
        std::map<std::string, value> source_bindings;
        auto remember = [&](const value &binding) {
            const auto source = binding.at("source").text();
            const auto [position, inserted] = source_bindings.emplace(source, binding);
            if (!inserted && json_text(position->second) != json_text(binding))
                throw std::runtime_error("Conflicting prepared source binding: " + source);
        };
        for (const auto &[slot, expected] : requested) {
            const auto &binding = bindings.at(slot);
            validate_binding(binding, expected, types);
            subjects.insert(slot); remember(binding);
        }
        if (prepared.has("dependencies")) {
            if (prepared.at("dependencies").type != value::kind::object)
                throw std::runtime_error("Prepared dependencies require a map");
            for (const auto &[name, binding] : prepared.at("dependencies").fields) {
                if (name.empty()) throw std::runtime_error("Empty prepared dependency name");
                validate_binding(binding, required_text(binding, "physical"), types);
                subjects.insert("dependency:" + name); remember(binding);
            }
        }
        if (input.get("action") == "generate") {
            // Only compiler-declared source arguments need semantic bindings.
            // Private recipe/layout dependencies remain physical-only.
            for (const auto &[source, binding] : source_bindings)
                for (const auto &[parameter, argument] : binding.at("arguments").fields)
                    if (!source_bindings.contains(argument.text()))
                        throw std::runtime_error("Missing prepared source dependency: " + source + "/" + parameter);
        }
        if (prepared.at("requirements").type != value::kind::array
            || prepared.at("requirements").items.empty() || prepared.at("facts").type != value::kind::array)
            throw std::runtime_error("Prepared request requires semantic requirements and facts");
        std::set<std::pair<std::string, std::string>> facts, requirements;
        for (const auto &fact : prepared.at("facts").items) {
            fields_only(fact, "subject contract evidence", "prepared semantic fact");
            const auto subject = required_text(fact, "subject"), contract = required_text(fact, "contract");
            if (!subjects.contains(subject))
                throw std::runtime_error("Unknown prepared fact subject");
            required_text(fact, "evidence");
            if (!facts.emplace(subject, contract).second) throw std::runtime_error("Duplicate prepared fact");
        }
        for (const auto &requirement : prepared.at("requirements").items) {
            fields_only(requirement, "subject contract", "prepared semantic requirement");
            const auto subject = required_text(requirement, "subject"), contract = required_text(requirement, "contract");
            if (!subjects.contains(subject))
                throw std::runtime_error("Unknown prepared requirement subject");
            if (!requirements.emplace(subject, contract).second) throw std::runtime_error("Duplicate prepared requirement");
            if (!facts.contains({subject, contract}))
                throw std::runtime_error("Missing prepared semantic fact: " + subject + "/" + contract);
        }
        // Preserve source identity, argument mapping and evidence provenance in the plan.
        // These are compiler attestations, not an independently verified certificate.
        return prepared;
    }
}
