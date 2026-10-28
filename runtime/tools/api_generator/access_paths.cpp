#include "access_paths.hpp"
#include "capabilities.hpp"
#include "function_sets.hpp"
#include "names.hpp"

namespace scpp::api_generation
{
    value describe_access_path(const value &metadata, const value &leaves,
        const value &aliases, const value &request)
    {
        fields_only(request, "root depth operation", "access path");
        const auto operation = request.at("operation").text();
        if (operation != "existing_read" && operation != "existing_store")
            throw std::runtime_error("Unsupported access path operation");
        const auto &depth_value = request.at("depth");
        if (depth_value.type != value::kind::number || depth_value.scalar.empty()
            || depth_value.scalar.find_first_not_of("0123456789") != std::string::npos
            || depth_value.scalar.size() > 3)
            throw std::runtime_error("Access path depth must be an integer from 1 to 128");
        const auto depth = std::stoul(depth_value.scalar);
        if (depth == 0 || depth > 128) throw std::runtime_error("Access path depth must be from 1 to 128");
        naming names(metadata_naming_registry(metadata, leaves, aliases));
        auto receiver = names.resolve(request.at("root").text()).first;
        value result = value::object(); result["root"] = receiver; result["operation"] = operation;
        result["steps"] = value::array();
        for (unsigned index = 0; index < depth; ++index) {
            const auto node = names.bind(receiver);
            const auto &family = metadata.at("families").at(node.type.name);
            if (!family.has("access")) throw std::runtime_error("Receiver has no access protocol: " + receiver);
            (void)type_capabilities(metadata, leaves, aliases, receiver);
            value arguments = value::object();
            for (const auto &[parameter, argument] : node.arguments) arguments[parameter] = argument.canonical();
            auto protocol = instantiate_function(family.at("access"), "", arguments);
            fields_only(protocol, "key element read mutable includes", "access protocol");
            protocol.at("read").text(); protocol.at("mutable").text();
            protocol["key"] = names.resolve(protocol.at("key").text()).first;
            protocol["element"] = names.resolve(protocol.at("element").text()).first;
            protocol["receiver"] = receiver;
            receiver = protocol.at("element").text();
            result["steps"].items.push_back(protocol);
        }
        // Owned read and value assignment need a copyable leaf, independently of
        // intermediate storage. This is a native capability, not source admission.
        const auto leaf = type_capabilities(metadata, leaves, aliases, receiver);
        value requirements = value::object(); requirements["requires_capabilities"] = value::object();
        requirements["requires_capabilities"]["leaf"] = value::array();
        requirements["requires_capabilities"]["leaf"].items.emplace_back("copy_storage");
        if (!capabilities_selected(metadata, requirements, {{"leaf", leaf}}))
            throw std::runtime_error("Access path requires copyable leaf storage");
        result["element"] = receiver;
        return result;
    }
}
