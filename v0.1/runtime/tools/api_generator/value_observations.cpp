#include "value_observations.hpp"
#include "names.hpp"
#include "capabilities.hpp"

namespace scpp::api_generation
{
    value describe_value_observation(const value &metadata, const value &leaves,
        const value &aliases, const std::string &type, const std::string &observation)
    {
        const auto &rule = metadata.at("value_observations").at(observation);
        fields_only(rule, "types families expression missing includes", "value observation");
        naming names(metadata_naming_registry(metadata, leaves, aliases));
        const auto canonical = names.resolve(type).first;
        const auto node = parse_type(canonical);
        bool selected = false;
        for (const auto *domain : {"types", "families"}) {
            const auto &entries = rule.at(domain);
            if (entries.type != value::kind::array) throw std::runtime_error("Observation domains require arrays");
            for (const auto &entry : entries.items) {
                const bool family = std::string(domain) == "families";
                if (family ? !metadata.at("families").has(entry.text()) : !leaves.has(entry.text()))
                    throw std::runtime_error("Unknown observation domain type: " + entry.text());
                if (family ? node.name == entry.text() : canonical == entry.text()) selected = true;
            }
        }
        if (!selected) throw std::runtime_error("Unsupported value observation: " + observation + " on " + canonical);
        (void)type_capabilities(metadata, leaves, aliases, canonical);
        rule.at("expression").text(); rule.at("missing").text();
        return rule;
    }
}
