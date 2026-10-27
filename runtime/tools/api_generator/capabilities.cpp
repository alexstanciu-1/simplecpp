#include "capabilities.hpp"
#include "names.hpp"
#include <set>

namespace scpp::api_generation
{
    value merged_type_leaves(const value &input)
    {
        auto leaves = input.at("metadata").at("types");
        if (leaves.type != value::kind::object) throw std::runtime_error("types must be an object");
        for (const auto &[key, leaf] : leaves.fields) {
            fields_only(leaf,"cpp header owner native borrow construct includes capabilities", "leaf " + key);
            leaf.at("cpp").text(); leaf.at("header").text(); leaf.at("owner").text();
        }
        if (!input.has("leaves")) return leaves;
        if (input.at("leaves").type != value::kind::object) throw std::runtime_error("leaves must be an object");
        for (const auto &[key, leaf] : input.at("leaves").fields) {
            fields_only(leaf,"cpp header owner native borrow construct includes capabilities", "leaf " + key);
            if (leaves.has(key)) {
                for (const auto &[field, item] : leaf.fields)
                    if (!leaves.at(key).has(field) || json_text(leaves.at(key).at(field)) != json_text(item))
                        throw std::runtime_error("Conflicting leaf identity: " + key);
            } else {
                leaf.at("cpp").text(); leaf.at("header").text(); leaf.at("owner").text();
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
                if (type.has("capabilities")) for (const auto &capability : type.at("capabilities").items)
                    if (capability.text() == item.text()) found = true;
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
