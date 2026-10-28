#include "signatures.hpp"
#include "capabilities.hpp"
#include "names.hpp"
#include <set>
#include <vector>

namespace scpp::api_generation
{
    static void validate_registry(const value &metadata)
    {
        if (!metadata.has("capabilities")) return;
        const auto &registry = metadata.at("capabilities");
        if (registry.type != value::kind::object) throw std::runtime_error("capabilities registry must be an object");
        for (const auto &[name, recipe] : registry.fields) {
            authored_name(name);
            fields_only(recipe, "checks includes implies", "capability " + name);
            if (recipe.at("checks").type != value::kind::array || recipe.at("checks").items.empty())
                throw std::runtime_error("Capability requires nonempty checks: " + name);
            if (!recipe.has("implies")) continue;
            const auto &edges = recipe.at("implies");
            if (edges.type != value::kind::array) throw std::runtime_error("Capability implies must be an array");
            std::set<std::string> seen;
            for (const auto &edge : edges.items) {
                const auto target = edge.text();
                if (!registry.has(target)) throw std::runtime_error("Unknown implied capability: " + target);
                if (!seen.insert(target).second) throw std::runtime_error("Duplicate implied capability: " + target);
            }
        }
        std::map<std::string, int> states;
        for (const auto &[root, unused] : registry.fields) {
            std::vector<std::pair<std::string, bool>> pending{{root, false}};
            while (!pending.empty()) {
                const auto [name, finishing] = pending.back(); pending.pop_back();
                if (finishing) { states[name] = 2; continue; }
                if (states[name] == 1) throw std::runtime_error("Capability implication cycle: " + name);
                if (states[name] == 2) continue;
                states[name] = 1;
                pending.emplace_back(name, true);
                const auto &recipe = registry.at(name);
                if (recipe.has("implies")) for (const auto &edge : recipe.at("implies").items)
                    pending.emplace_back(edge.text(), false);
            }
        }
    }

    static bool implies(const value &metadata, const std::string &provided, const std::string &required,
        std::set<std::string> &visited)
    {
        std::vector<std::string> pending{provided};
        while (!pending.empty()) {
            const auto name = pending.back(); pending.pop_back();
            if (name == required) return true;
            if (!visited.insert(name).second) continue;
            const auto &recipe = metadata.at("capabilities").at(name);
            if (recipe.has("implies")) for (const auto &edge : recipe.at("implies").items)
                pending.push_back(edge.text());
        }
        return false;
    }

    value merged_type_leaves(const value &input)
    {
        const auto &metadata = input.at("metadata");
        validate_registry(metadata);
        std::vector<value> sources{metadata.at("types")};
        if (input.has("leaf_imports")) sources.push_back(input.at("leaf_imports"));
        if (input.has("leaves")) sources.push_back(input.at("leaves"));
        // Register all identities before normalizing descriptors that reference
        // other registered types. This does not assign them storage or layout.
        value registered = value::object();
        for (const auto &source : sources) {
            if (source.type != value::kind::object) throw std::runtime_error("Type leaves require an object");
            for (const auto &[key, leaf] : source.fields) registered[key] = leaf;
        }
        value leaves = value::object();
        for (const auto &source : sources) for (const auto &[key, original] : source.fields) {
            auto leaf = original;
            if (leaf.has("signature")) {
                fields_only(leaf, "signature owner", "signature leaf " + key);
                if (leaf.has("owner") && leaf.at("owner").text() != "runtime.signature")
                    throw std::runtime_error("Signature descriptors have canonical ownership");
                const auto normalized = resolve_signature(metadata, registered,
                    input.has("names") ? input.at("names") : value::object(), leaf.at("signature"));
                if (normalized.at("key").text() != key)
                    throw std::runtime_error("Signature identity does not match its descriptor: " + key);
                leaf["signature"] = normalized.at("descriptor"); leaf["owner"] = "runtime.signature";
            } else {
                fields_only(leaf, "cpp header owner native borrow construct includes capabilities", "leaf " + key);
            }
            if (leaves.has(key)) {
                for (const auto &[field, item] : leaf.fields)
                    if (!leaves.at(key).has(field) || json_text(leaves.at(key).at(field)) != json_text(item))
                        throw std::runtime_error("Conflicting leaf identity: " + key);
            } else {
                if (!leaf.has("signature")) {
                    leaf.at("cpp").text(); leaf.at("header").text(); leaf.at("owner").text();
                }
                leaves[key] = leaf;
            }
        }
        return leaves;
    }

    void validate_capabilities(const value &metadata, const value &record)
    {
        if (!record.has("capabilities")) return;
        const auto &list = record.at("capabilities");
        if (list.type != value::kind::array) throw std::runtime_error("capabilities must be an array");
        std::set<std::string> seen;
        for (const auto &item : list.items) {
            const auto name = item.text();
            if (!metadata.has("capabilities") || !metadata.at("capabilities").has(name))
                throw std::runtime_error("Unknown capability: " + name);
            if (!seen.insert(name).second) throw std::runtime_error("Duplicate capability: " + name);
        }
    }

    bool capabilities_selected(const value &metadata, const value &record, const type_bindings &types)
    {
        if (!record.has("requires_capabilities")) return true;
        const auto &requirements = record.at("requires_capabilities");
        if (requirements.type != value::kind::object) throw std::runtime_error("requires_capabilities must be an object");
        bool available = true;
        for (const auto &[parameter, list] : requirements.fields) {
            if (!types.contains(parameter)) throw std::runtime_error("Unknown capability parameter: " + parameter);
            value required = value::object(); required["capabilities"] = list;
            validate_capabilities(metadata, required);
            const auto &type = types.at(parameter); validate_capabilities(metadata, type);
            for (const auto &item : list.items) {
                bool found = false;
                std::set<std::string> visited;
                if (type.has("capabilities")) for (const auto &capability : type.at("capabilities").items)
                    if (implies(metadata, capability.text(), item.text(), visited)) found = true;
                available = available && found;
            }
        }
        return available;
    }

    value effective_capabilities(const value &metadata, const value &record, const type_bindings &types)
    {
        validate_capabilities(metadata, record);
        std::set<std::string> selected;
        if (record.has("capabilities")) for (const auto &item : record.at("capabilities").items) selected.insert(item.text());
        if (record.has("conditional_capabilities")) {
            const auto &conditions = record.at("conditional_capabilities");
            if (conditions.type != value::kind::object) throw std::runtime_error("conditional_capabilities must be an object");
            for (const auto &[name, condition] : conditions.fields) {
                metadata.at("capabilities").at(name);
                fields_only(condition, "requires_capabilities", "conditional capability");
                if (!condition.has("requires_capabilities")) throw std::runtime_error("Conditional capability requires a condition");
                if (selected.contains(name)) throw std::runtime_error("Duplicate unconditional and conditional capability: " + name);
                if (capabilities_selected(metadata, condition, types)) selected.insert(name);
            }
        }
        value result = value::array(); for (const auto &name : selected) result.items.emplace_back(name);
        return result;
    }

    static value resolve(const value &metadata, const value &leaves, naming &names, const std::string &source, unsigned depth)
    {
        if (depth > 128) throw std::runtime_error("Capability nesting limit exceeded");
        const auto identity = names.resolve(source).first;
        if (leaves.has(identity)) {
            validate_capabilities(metadata, leaves.at(identity));
            return leaves.at(identity);
        }
        const auto binding = names.bind(identity);
        const auto &family = metadata.at("families").at(binding.type.name);
        type_bindings arguments;
        for (const auto &[name, type] : binding.arguments)
            arguments[name] = resolve(metadata, leaves, names, type.canonical(), depth + 1);
        if (!capabilities_selected(metadata, family, arguments))
            throw std::runtime_error("Unsatisfied family capabilities: " + identity);
        value out = value::object(); out["capabilities"] = effective_capabilities(metadata, family, arguments);
        return out;
    }

    value type_capabilities(const value &metadata, const value &leaves, const value &names, const std::string &source)
    {
        naming registry(metadata_naming_registry(metadata, leaves, names));
        return resolve(metadata, leaves, registry, source, 0);
    }

    void validate_import_capabilities(const value &metadata, const value &leaves, const value &names, const value &imports)
    {
        for (const auto &[identity, record] : imports.fields) {
            const auto expected = type_capabilities(metadata, leaves, names, identity);
            if (json_text(effective_capabilities(metadata, record, {})) != json_text(effective_capabilities(metadata, expected, {})))
                throw std::runtime_error("Conflicting imported capabilities: " + identity);
        }
    }
}
