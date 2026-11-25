#include "signatures.hpp"
#include "names.hpp"
#include <algorithm>

namespace scpp::api_generation
{
    value resolve_signature(const value &metadata, const value &leaves,
        const value &names, const value &request)
    {
        fields_only(request, "returns parameters return_origin", "signature descriptor");
        if (request.at("parameters").type != value::kind::array)
            throw std::runtime_error("Signature parameters require an array");
        naming types(metadata_naming_registry(metadata, leaves, names));
        value descriptor = value::object();
        descriptor["returns"] = types.resolve(request.at("returns").text()).first;
        descriptor["parameters"] = value::array();
        value operation = value::object();
        operation["returns"] = descriptor.at("returns");
        operation["parameters"] = value::array();
        type_name identity{"call_signature", {}, true};
        type_name returned{"value_result", {parse_type(descriptor.at("returns").text())}, true};
        const auto &parameters = request.at("parameters").items;
        std::size_t index = 0;
        for (const auto &parameter : parameters) {
            fields_only(parameter, "name type passing variadic", "signature parameter");
            if (parameter.has("name")) {
                authored_name(parameter.at("name").text());
                if (parameter.at("name").text().find("::") != std::string::npos)
                    throw std::runtime_error("Signature parameter name must be unqualified");
            }
            const auto type = types.resolve(parameter.at("type").text()).first;
            if (type == "void") throw std::runtime_error("Signature input cannot be void");
            const auto passing = parameter.get("passing", "value");
            if (passing != "value" && passing != "ref" && passing != "const_ref")
                throw std::runtime_error("Unsupported signature input mode");
            if (parameter.has("variadic") && parameter.at("variadic").type != value::kind::boolean)
                throw std::runtime_error("Signature variadic marker must be boolean");
            const bool variadic = parameter.yes("variadic");
            if (variadic && (passing != "value" || index + 1 != parameters.size()))
                throw std::runtime_error("Signature variadic input must be final and by value");
            value item = value::object(); item["type"] = type; item["passing"] = passing;
            item["variadic"].type = value::kind::boolean;
            item["variadic"].scalar = variadic ? "true" : "false";
            descriptor["parameters"].items.push_back(item);
            item.fields.erase("variadic"); item["name"] = "argument_" + std::to_string(index++);
            operation["parameters"].items.push_back(item);
            identity.arguments.push_back({variadic ? "variadic_input" : passing + "_input", {parse_type(type)}, true});
        }
        if (request.has("return_origin")) {
            const auto &origin = request.at("return_origin");
            if (origin.type != value::kind::number || origin.scalar.empty()
                || !std::all_of(origin.scalar.begin(), origin.scalar.end(), [](char c) { return c >= '0' && c <= '9'; }))
                throw std::runtime_error("Signature return origin must be a parameter index");
            const auto limit = std::to_string(parameters.size());
            if (origin.scalar.size() > limit.size() || (origin.scalar.size() == limit.size() && origin.scalar >= limit))
                throw std::runtime_error("Signature return origin is out of range");
            const auto selected = std::stoull(origin.scalar);
            unsigned matches = 0; bool valid = false;
            index = 0;
            for (const auto &parameter : descriptor.at("parameters").items) {
                if (parameter.get("passing") == "ref" && parameter.at("type").text() == descriptor.at("returns").text()) {
                    ++matches; valid = valid || index == selected;
                }
                ++index;
            }
            if (matches != 1 || !valid)
                throw std::runtime_error("Signature return origin requires one matching writable input");
            descriptor["return_origin"].type = value::kind::number;
            descriptor["return_origin"].scalar = std::to_string(selected);
            operation["return_origin"] = "argument_" + std::to_string(selected);
            returned.name = "reference_result_" + std::to_string(selected);
        }
        identity.arguments.insert(identity.arguments.begin(), returned);
        value result = value::object(); result["descriptor"] = descriptor;
        result["identity"] = identity.canonical(); result["name"] = structural_name(identity);
        auto key = structural_name(identity);
        if (!key.empty() && key.back() == '_') key.pop_back();
        result["key"] = key;
        result["operation_signature"] = operation;
        return result;
    }
}
