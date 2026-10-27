#include "carriers.hpp"
#include "names.hpp"
#include <set>

namespace scpp::api_generation
{
    static bool contains(const value &list, const std::string &name)
    {
        for (const auto &item : list.items) if (item.text() == name) return true;
        return false;
    }
    void validate_carriers(const value &metadata)
    {
        for (const auto &[name, family] : metadata.at("families").fields) {
            if (!family.has("carrier")) continue;
            const auto &carrier = family.at("carrier");
            fields_only(carrier, "payload require_value states", "carrier descriptor");
            if (!contains(family.at("parameters"), carrier.at("payload").text()))
                throw std::runtime_error("Unknown carrier payload parameter");
            const auto method = carrier.at("require_value").text();
            authored_name(method);
            unsigned matches = 0;
            for (const auto &record : family.at("classes").items) {
                if (record.get("id") != "self") continue;
                for (const auto &member : record.at("methods").items) {
                    if (member.get("name") != method) continue;
                    const auto &parameters = member.at("parameters").items;
                    if (member.get("returns") != carrier.at("payload").text()
                        || !member.yes("const") || member.has("requires_capabilities")
                        || parameters.size() != 1 || parameters[0].get("type") != "c_string"
                        || parameters[0].get("passing", "value") != "value")
                        throw std::runtime_error("Carrier projection must return an owned payload from a const checked member");
                    ++matches;
                }
            }
            if (matches != 1) throw std::runtime_error("Carrier projection requires one checked member");
            if (carrier.has("states")) {
                const auto &states = carrier.at("states");
                if (states.type != value::kind::array || states.items.empty())
                    throw std::runtime_error("Carrier states must be a nonempty array");
                std::set<std::string> seen;
                for (std::size_t i = 0; i < states.items.size(); ++i) {
                    const auto &state = states.items[i];
                    fields_only(state, "name test casts condition comparison_tag", "carrier state");
                    if (state.has("condition") && state.get("condition") != "payload"
                        && state.get("condition") != "true" && state.get("condition") != "false")
                        throw std::runtime_error("Unknown carrier condition policy");
                    authored_name(state.at("name").text());
                    if (state.has("comparison_tag")) authored_name(state.at("comparison_tag").text());
                    if (!seen.insert(state.at("name").text()).second) throw std::runtime_error("Duplicate carrier state");
                    if (state.has("test") == (i + 1 == states.items.size()))
                        throw std::runtime_error("Carrier states require a final fallback only");
                    if (state.has("test")) {
                        unsigned predicates = 0;
                        for (const auto &record : family.at("classes").items) {
                            if (record.get("id") != "self") continue;
                            for (const auto &member : record.at("methods").items) {
                                if (member.get("name") != state.at("test").text()) continue;
                                if (member.get("returns") != "boolean" || !member.yes("const")
                                    || !member.at("parameters").items.empty() || member.has("requires_capabilities"))
                                    throw std::runtime_error("Carrier state requires a const boolean observation");
                                ++predicates;
                            }
                        }
                        if (predicates != 1) throw std::runtime_error("Carrier state requires one observation member");
                    }
                    if (!state.has("casts")) continue;
                    if (state.at("casts").type != value::kind::object) throw std::runtime_error("Carrier state casts require an object");
                    for (const auto &[target, recipe] : state.at("casts").fields) {
                        metadata.at("conversions").at("targets").at(target);
                        fields_only(recipe, "expression header", "carrier state cast");
                        // Non-payload state factories may not read or extract a payload.
                        if (recipe.at("expression").text().find("${") != std::string::npos)
                            throw std::runtime_error("Carrier state cast must be an independent result expression");
                        recipe.at("header").text();
                    }
                }
            }

        }
    }

    value project_carrier(const value &metadata, const std::string &source, const value &names)
    {
        naming types(metadata_naming_registry(metadata, metadata.at("types"), names));
        const auto [identity, public_name] = types.resolve(source);
        const auto node = types.normalize(parse_type(identity));
        if (!metadata.at("families").has(node.name)) return value();
        const auto &family = metadata.at("families").at(node.name);
        if (!family.has("carrier")) return value();
        const auto &carrier = family.at("carrier");
        const auto binding = types.bind(identity);
        value out = value::object();
        out["source"] = identity;
        for (const auto &[parameter, argument] : binding.arguments)
            if (parameter == carrier.at("payload").text()) out["target"] = argument.canonical();
        out["kind"] = "checked_member";
        out["header"] = "scpp/" + public_name + ".hpp"; out["cpp"] = "scpp::" + public_name;
        out["expression"] = "${value}." + carrier.at("require_value").text() + "(\"operator payload\")";
        out["ownership"] = "owned_value";
        if (carrier.has("states")) out["states"] = carrier.at("states");
        return out;
    }
}
