#include "access_paths.hpp"
#include "value_observations.hpp"
#include "capabilities.hpp"
#include "function_sets.hpp"
#include "names.hpp"

namespace scpp::api_generation
{
    value describe_access_path(const value &metadata, const value &leaves,
        const value &aliases, const value &request)
    {
        fields_only(request, "root depth operation observation purpose", "access path");
        const auto operation = request.at("operation").text();
        if (operation != "existing_read" && operation != "existing_store"
            && operation != "store" && operation != "key_check"
            && operation != "prefix_ready" && operation != "existing_erase" && operation != "observe")
            throw std::runtime_error("Unsupported access path operation");
        if (request.has("observation") && operation != "observe")
            throw std::runtime_error("Observation requires observe operation");
        const auto purpose = request.get("purpose", "probe");
        if (request.has("purpose") && (operation != "prefix_ready" || (purpose != "probe" && purpose != "remove")))
            throw std::runtime_error("Prefix purpose must be probe or remove");
        const auto &depth_value = request.at("depth");
        if (depth_value.type != value::kind::number || depth_value.scalar.empty()
            || depth_value.scalar.find_first_not_of("0123456789") != std::string::npos
            || depth_value.scalar.size() > 3)
            throw std::runtime_error("Access path depth must be an integer from 1 to 128");
        const auto depth = std::stoul(depth_value.scalar);
        if ((depth == 0 && operation != "store" && operation != "key_check"
            && operation != "prefix_ready") || depth > 128) throw std::runtime_error("Access path depth must be from 1 to 128");
        naming names(metadata_naming_registry(metadata, leaves, aliases));
        auto receiver = names.resolve(request.at("root").text()).first;
        value result = value::object(); result["root"] = receiver; result["operation"] = operation;
        result["removing"] = operation == "existing_erase" || purpose == "remove" ? "true" : "false";
        result["steps"] = value::array();
        for (unsigned index = 0; index < depth; ++index) {
            const auto node = names.bind(receiver);
            const auto &family = metadata.at("families").at(node.type.name);
            if (!family.has("access")) throw std::runtime_error("Receiver has no access protocol: " + receiver);
            (void)type_capabilities(metadata, leaves, aliases, receiver);
            value arguments = value::object();
            for (const auto &[parameter, argument] : node.arguments) arguments[parameter] = argument.canonical();
            auto protocol = instantiate_function(family.at("access"), "", arguments);
            fields_only(protocol, "key element read mutable includes present ready erase element_storage operations key_check write_read check_store store", "access protocol");
            if (protocol.has("operations")) {
                const auto &allowed = protocol.at("operations");
                if (allowed.type != value::kind::array) throw std::runtime_error("Access operations require an array");
                bool supported = false;
                for (const auto &entry : allowed.items) if (entry.text() == operation) supported = true;
                if (!supported) throw std::runtime_error("Unsupported receiver access operation: " + operation);
            }
            const auto storage = protocol.get("element_storage", "public");
            if (storage != "public" && storage != "native") throw std::runtime_error("Invalid access element storage");
            protocol.at("read").text(); protocol.at("mutable").text();
            protocol["key"] = names.resolve(protocol.at("key").text()).first;
            protocol["element"] = names.resolve(protocol.at("element").text()).first;
            protocol["receiver"] = receiver;
            receiver = protocol.at("element").text();
            result["steps"].items.push_back(protocol);
        }
        if (operation == "key_check") {
            result.at("steps").items.back().at("key_check").text();
            result["element"] = receiver;
            return result;
        }
        if (operation == "store") {
            result.at("steps").items.back().at("check_store").text();
            result.at("steps").items.back().at("store").text();
        }
        const bool probing = operation == "prefix_ready" || operation == "existing_erase" || operation == "observe";
        if (probing) {
            for (const auto &step : result.at("steps").items) step.at("present").text();
            if (operation == "observe")
                result["observation"] = describe_value_observation(metadata, leaves, aliases, receiver, request.at("observation").text());
            else if (operation == "existing_erase") result.at("steps").items.back().at("erase").text();
            else {
                const auto node = names.bind(receiver);
                const auto &access = metadata.at("families").at(node.type.name).at("access");
                value arguments = value::object();
                for (const auto &[parameter, argument] : node.arguments) arguments[parameter] = argument.canonical();
                result["ready_protocol"] = instantiate_function(access, "", arguments);
                result["ready"] = result.at("ready_protocol").at("ready");
            }
            result["element"] = receiver;
            return result;
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
