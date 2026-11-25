#include "carriers.hpp"
#include "names.hpp"
#include "capabilities.hpp"
#include <set>

namespace scpp::api_generation
{
    static bool contains(const value &list, const std::string &name)
    {
        for (const auto &item : list.items) if (item.text() == name) return true;
        return false;
    }
    static void validate_owned_member(const value &family, const std::string &method, const std::string &target, bool conditional = false)
    {
        authored_name(method);
        unsigned matches = 0;
        for (const auto &record : family.at("classes").items) {
            if (record.get("id") != "self") continue;
            for (const auto &member : record.at("methods").items) {
                if (member.get("name") != method) continue;
                const auto &parameters = member.at("parameters").items;
                if (member.get("returns") != target
                    || !member.yes("const") || (!conditional && member.has("requires_capabilities"))
                    || parameters.size() != 1 || parameters[0].get("type") != "c_string"
                    || parameters[0].get("passing", "value") != "value")
                    throw std::runtime_error("Carrier projection must return an owned payload from a const checked member");
                ++matches;
            }
        }
        if (matches != 1) throw std::runtime_error("Carrier projection requires one checked member");
    }

    void validate_carriers(const value &metadata)
    {
        for (const auto &[name, family] : metadata.at("families").fields) {
            if (!family.has("carrier")) continue;
            const auto &carrier = family.at("carrier");
            fields_only(carrier, "payload require_value states condition_observation", "carrier descriptor");
            if (!contains(family.at("parameters"), carrier.at("payload").text()))
                throw std::runtime_error("Unknown carrier payload parameter");
            validate_owned_member(family, carrier.at("require_value").text(), carrier.at("payload").text(), true);
            if (carrier.has("condition_observation")) {
                const auto method = carrier.at("condition_observation").text(); authored_name(method);
                unsigned matches = 0;
                for (const auto &record : family.at("classes").items) if (record.get("id")=="self")
                    for (const auto &member : record.at("methods").items) if (member.get("name")==method) {
                        if (member.get("condition_observation")!=carrier.at("payload").text()
                            || member.get("returns")!="boolean" || !member.yes("const")
                            || member.yes("static") || member.has("requires_capabilities")
                            || !member.at("parameters").items.empty())
                            throw std::runtime_error("Carrier condition observation must be a scoped const boolean method");
                        ++matches;
                    }
                if (matches!=1) throw std::runtime_error("Carrier condition observation requires one method");
            }
            if (carrier.has("states")) {
                const auto &states = carrier.at("states");
                if (states.type != value::kind::array || states.items.empty())
                    throw std::runtime_error("Carrier states must be a nonempty array");
                std::set<std::string> seen;
                for (std::size_t i = 0; i < states.items.size(); ++i) {
                    const auto &state = states.items[i];
                    fields_only(state, "name test casts condition comparison_tag comparison_value reconstruct literals", "carrier state");
                    if (state.has("condition") && state.get("condition") != "payload"
                        && state.get("condition") != "true" && state.get("condition") != "false")
                        throw std::runtime_error("Unknown carrier condition policy");
                    authored_name(state.at("name").text());
                    if (state.has("comparison_tag")) authored_name(state.at("comparison_tag").text());
                    if (state.has("comparison_value")) {
                        const auto &projection = state.at("comparison_value");
                        fields_only(projection, "type member", "carrier comparison value");
                        metadata.at("types").at(projection.at("type").text());
                        validate_owned_member(family, projection.at("member").text(), projection.at("type").text());
                    }
                    if (state.has("literals")) {
                        if (state.at("literals").type != value::kind::array || state.at("literals").items.empty())
                            throw std::runtime_error("Carrier state literals require a nonempty array");
                        std::set<std::string> literals;
                        for (const auto &literal : state.at("literals").items) {
                            metadata.at("global_operators").at("types").at(literal.text());
                            if (!literals.insert(literal.text()).second) throw std::runtime_error("Duplicate carrier state literal");
                        }
                    }

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
                    if (state.has("reconstruct")) {
                        const auto &recipe = state.at("reconstruct");
                        fields_only(recipe, "kind expression", "carrier reconstruction");
                        const auto kind = recipe.at("kind").text();
                        if (kind != "payload" && kind != "state") throw std::runtime_error("Unknown carrier reconstruction kind");
                        const auto expression = recipe.at("expression").text();
                        std::map<std::string, unsigned> markers;
                        for (std::size_t at = expression.find("${"); at != std::string::npos; at = expression.find("${", at)) {
                            const auto end = expression.find('}', at + 2);
                            if (end == std::string::npos) throw std::runtime_error("Unclosed reconstruction marker");
                            const auto marker = expression.substr(at + 2, end - at - 2);
                            if (marker != "target" && marker != "value" && marker != "source")
                                throw std::runtime_error("Unknown reconstruction marker");
                            ++markers[marker]; at = end + 1;
                        }
                        if (markers["target"] != 1 || markers["source"] > 1
                            || markers["value"] != (kind == "payload" ? 1u : 0u)
                            || (kind == "payload" && markers["source"] != 0))
                            throw std::runtime_error("Reconstruction must use one target and only its declared value source");
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

    void require_carrier_payload(const value &projection)
    {
        if (projection.get("payload_available", "true") != "true")
            throw std::runtime_error("Carrier owned payload access is unavailable for these capabilities");
    }

    value carrier_payload_construction(const value &projection)
    {
        if (!projection.has("states")) throw std::runtime_error("Carrier write-back requires state descriptions");
        value result;
        unsigned constructors = 0;
        for (const auto &state : projection.at("states").items) {
            if (!state.has("reconstruct") || state.at("reconstruct").get("kind")!="payload") continue;
            auto expression = state.at("reconstruct").at("expression").text();
            expression.replace(expression.find("${target}"), std::string("${target}").size(), projection.at("cpp").text());
            result = value::object(); result["kind"] = "construct_payload";
            result["expression"] = expression; result["header"] = projection.at("header");
            result["source"] = projection.at("target"); result["target"] = projection.at("source");
            ++constructors;
        }
        if (constructors!=1) throw std::runtime_error("Carrier write-back requires one payload construction");
        return result;
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
        type_bindings arguments;
        for (const auto &[parameter, argument] : binding.arguments)
            arguments[parameter] = type_capabilities(metadata,metadata.at("types"),names,argument.canonical());
        if (!capabilities_selected(metadata,family,arguments))
            throw std::runtime_error("Unsatisfied family capabilities: " + identity);
        const auto guarantees = effective_capabilities(metadata,family,arguments);
        bool available = false;
        for (const auto &record : family.at("classes").items) if (record.get("id") == "self")
            for (const auto &member : record.at("methods").items)
                if (member.get("name") == carrier.at("require_value").text())
                    available = capabilities_selected(metadata,member,arguments);
        value out = value::object(); out["capabilities"] = guarantees; out["payload_available"] = available ? "true" : "false";
        out["source"] = identity; out["family"] = node.name;
        for (const auto &[parameter, argument] : binding.arguments)
            if (parameter == carrier.at("payload").text()) out["target"] = argument.canonical();
        out["kind"] = "checked_member";
        out["header"] = "scpp/" + public_name + ".hpp"; out["cpp"] = "scpp::" + public_name;
        if (available) out["expression"] = "${value}." + carrier.at("require_value").text() + "(\"operator payload\")";
        out["ownership"] = "owned_value";
        if (carrier.has("condition_observation")) out["condition_observation"] = carrier.at("condition_observation");
        if (carrier.has("states")) out["states"] = carrier.at("states");
        return out;
    }
}
