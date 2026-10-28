#include "function_sets.hpp"
#include "names.hpp"

namespace scpp::api_generation
{
    // One substitution pass: inserted snippets may contain ordinary operation
    // placeholders, but cannot recursively expand another variant placeholder.
    static std::string expand_text(const std::string &text, const std::string &variant, const value &bindings)
    {
        std::string out;
        for (std::size_t i = 0; i < text.size();) {
            if (text.compare(i, 10, "${variant}") == 0) {
                out += variant; i += 10;
            } else if (text.compare(i, 10, "${variant:") == 0) {
                const auto end = text.find('}', i + 10);
                if (end == std::string::npos) throw std::runtime_error("Unclosed variant placeholder");
                const auto key = text.substr(i + 10, end - i - 10);
                out += bindings.at(key).text(); i = end + 1;
            } else {
                out += text[i++];
            }
        }
        return out;
    }

    value instantiate_function(value item, const std::string &variant, const value &bindings)
    {
        if (item.type == value::kind::text) return value(expand_text(item.text(), variant, bindings));
        if (item.type == value::kind::array)
            for (auto &child : item.items) child = instantiate_function(child, variant, bindings);
        if (item.type == value::kind::object)
            for (auto &[key, child] : item.fields) child = instantiate_function(child, variant, bindings);
        return item;
    }

    value expand_function_sets(const value &metadata)
    {
        value out = value::object();
        if (!metadata.has("function_sets")) return out;
        const auto &sets = metadata.at("function_sets");
        if (sets.type != value::kind::object) throw std::runtime_error("Function sets require an object");
        for (const auto &[name, set] : sets.fields) {
            authored_name(name); fields_only(set, "variants function", "function set");
            if (set.at("variants").type != value::kind::object || set.at("function").type != value::kind::object)
                throw std::runtime_error("Function set requires variant and function objects");
            for (const auto &[variant, bindings] : set.at("variants").fields) {
                authored_name(variant);
                if (bindings.type != value::kind::object) throw std::runtime_error("Variant bindings require an object");
                for (const auto &[key, binding] : bindings.fields) {
                    authored_name(key);
                    if (binding.text().find("${variant") != std::string::npos)
                        throw std::runtime_error("Recursive variant substitution is not supported");
                }
                const auto identity = name + "_" + variant;
                if (out.has(identity)) throw std::runtime_error("Duplicate function set API identity");
                out[identity] = instantiate_function(set.at("function"), variant, bindings);
            }
        }
        return out;
    }
}
