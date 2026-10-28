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
        fields_only(prepared, "version producer revision action request admission bindings requirements facts", "prepared request");
        if (required_text(prepared, "version") != "prepared-api-v1")
            throw std::runtime_error("Unsupported prepared request version");
        required_text(prepared, "producer"); required_text(prepared, "revision");
        required_text(prepared, "admission");
        if (required_text(prepared, "action") != input.get("action")
            || json_text(prepared.at("request")) != json_text(input.at("request")))
            throw std::runtime_error("Prepared admission does not match requested operation");
        const auto slots = binding_slots(input);
        const auto &bindings = prepared.at("bindings");
        std::string allowed;
        for (const auto &slot : slots) allowed += slot + " ";
        fields_only(bindings, allowed, "prepared bindings");
        naming types(metadata_naming_registry(input.at("metadata"), merged_type_leaves(input),
            input.has("names") ? input.at("names") : value::object()));
        for (const auto &slot : slots) {
            const auto &binding = bindings.at(slot);
            fields_only(binding, "source physical arguments", "prepared type binding");
            required_text(binding, "source");
            const auto physical = required_text(binding, "physical");
            const auto canonical = types.resolve(physical).first;
            if (physical != canonical || canonical != types.resolve(input.at("request").at(slot).text()).first)
                throw std::runtime_error("Prepared physical binding mismatch: " + std::string(slot));
            if (binding.at("arguments").type != value::kind::object)
                throw std::runtime_error("Prepared source arguments require a map");
            for (const auto &[parameter, argument] : binding.at("arguments").fields)
                required_text(binding.at("arguments"), parameter);
        }
        if (prepared.at("requirements").type != value::kind::array
            || prepared.at("requirements").items.empty() || prepared.at("facts").type != value::kind::array)
            throw std::runtime_error("Prepared request requires semantic requirements and facts");
        std::set<std::pair<std::string, std::string>> facts, requirements;
        for (const auto &fact : prepared.at("facts").items) {
            fields_only(fact, "subject contract evidence", "prepared semantic fact");
            const auto subject = required_text(fact, "subject"), contract = required_text(fact, "contract");
            if (subject != "operation" && !bindings.has(subject))
                throw std::runtime_error("Unknown prepared fact subject");
            required_text(fact, "evidence");
            if (!facts.emplace(subject, contract).second) throw std::runtime_error("Duplicate prepared fact");
        }
        for (const auto &requirement : prepared.at("requirements").items) {
            fields_only(requirement, "subject contract", "prepared semantic requirement");
            const auto subject = required_text(requirement, "subject"), contract = required_text(requirement, "contract");
            if (subject != "operation" && !bindings.has(subject))
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
