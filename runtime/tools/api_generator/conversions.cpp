#include "conversions.hpp"
#include "names.hpp"
#include "carriers.hpp"
#include <set>

namespace scpp::api_generation
{
    static const value &registry(const value &metadata)
    {
        const auto &r = metadata.at("conversions");
        fields_only(r, "sources bindings targets", "conversions");
        for (auto name : {"sources", "bindings", "targets"})
            if (r.at(name).type != value::kind::object) throw std::runtime_error("Conversion registry requires objects");
        for (const auto &[source, normalized] : r.at("sources").fields) {
            authored_name(source);
            if (!metadata.at("types").has(source) && !metadata.at("families").has(source)) throw std::runtime_error("Unknown conversion source");
            r.at("bindings").at(normalized.text());
            if (r.at("sources").at(normalized.text()).text() != normalized.text())
                throw std::runtime_error("Conversion normalization must be one step");
        }
        for (const auto &[name, binding] : r.at("bindings").fields) {
            if (!r.at("sources").has(name) || r.at("sources").at(name).text() != name)
                throw std::runtime_error("Conversion binding must be normalized");
            authored_name(name); fields_only(binding, "operand includes targets", "conversion binding");
            binding.at("operand").text();
            std::set<std::string> seen;
            if (binding.at("targets").type != value::kind::array) throw std::runtime_error("Conversion targets must be an array");
            for (const auto &target : binding.at("targets").items) {
                r.at("targets").at(target.text());
                if (!seen.insert(target.text()).second) throw std::runtime_error("Duplicate conversion target");
            }
        }
        for (const auto &[name, target] : r.at("targets").fields) {
            authored_name(name); fields_only(target, "expression includes transport", "conversion target");
            target.at("expression").text();
            if (!metadata.at("types").has(name) && !metadata.at("families").has(name)) throw std::runtime_error("Unknown conversion target");
        }
        return r;
    }

    static value resolve_plain_conversion(const value &metadata, const value &request)
    {
        fields_only(request, "source target", "conversion request");
        const auto &r = registry(metadata);
        const auto source = request.at("source").text(), target = request.at("target").text();
        if (!r.at("sources").has(source) || !r.at("targets").has(target))
            throw std::runtime_error("Unsupported checked conversion");
        const auto normalized = r.at("sources").at(source).text();
        bool admitted = false;
        for (const auto &t : r.at("bindings").at(normalized).at("targets").items) admitted |= t.text() == target;
        if (!admitted) throw std::runtime_error("Unsupported checked conversion");
        value out = value::object();
        out["source"] = source; out["target"] = target; out["operand_type"] = normalized;
        out["api"] = "cast_" + target + "_from_" + normalized;
        out["function"] = "scpp::cast_" + target;
        out["header"] = "scpp/conversions/" + out.at("api").text() + ".hpp";
        out["conversion"] = source == normalized ? "identity" : "lossless_source_normalization";
        return out;
    }

    static value reconstruct_carrier(const value &metadata, const value &projection,
        const value &destination)
    {
        if (projection.at("family").text() != destination.at("family").text())
            throw std::runtime_error("Cross-family carrier conversion is not admitted");
        if (!projection.has("states") || !destination.has("states"))
            throw std::runtime_error("Carrier reconstruction requires state descriptions");
        value request = value::object(); request["source"] = projection.at("target"); request["target"] = destination.at("target");
        const auto calculation = resolve_plain_conversion(metadata, request);
        value out = value::object(); out["kind"] = "carrier_reconstruction";
        out["source"] = projection.at("source"); out["target"] = destination.at("source");
        out["target_cpp"] = destination.at("cpp"); out["target_header"] = destination.at("header");
        out["projection"] = projection; out["payload_conversion"] = calculation; out["states"] = value::array();
        for (const auto &state : projection.at("states").items) {
            const value *target_state = nullptr;
            for (const auto &candidate : destination.at("states").items)
                if (candidate.at("name").text() == state.at("name").text()) target_state = &candidate;
            if (!target_state || !target_state->has("reconstruct"))
                throw std::runtime_error("Missing carrier state reconstruction");
            const auto &recipe = target_state->at("reconstruct");
            value branch = value::object(); branch["name"] = state.at("name");
            if (state.has("test")) branch["test_expression"] = "${value}." + state.at("test").text() + "()";
            branch["kind"] = recipe.at("kind");
            auto expression = recipe.at("expression").text();
            expression.replace(expression.find("${target}"), std::string("${target}").size(), destination.at("cpp").text());
            branch["expression"] = expression; out["states"].items.push_back(branch);
        }
        out["evaluation"] = "evaluate_source_once_then_select_state";
        out["failure"] = "throw_without_mutating_source";
        out["commit"] = "construct_destination_after_payload_conversion";
        return out;
    }

    value resolve_conversion(const value &metadata, const value &request, const value &names)
    {
        fields_only(request, "source target", "conversion request");
        const auto &r = registry(metadata);
        const auto source = request.at("source").text(), target = request.at("target").text();
        if (r.at("sources").has(source)) return resolve_plain_conversion(metadata, request);
        validate_carriers(metadata);
        const auto projection = project_carrier(metadata, source, names);
        if (projection.type == value::kind::null) throw std::runtime_error("Unsupported checked conversion");
        if (!r.at("targets").has(target)) {
            const auto destination = project_carrier(metadata, target, names);
            if (destination.type == value::kind::null) throw std::runtime_error("Unsupported checked conversion");
            return reconstruct_carrier(metadata, projection, destination);
        }
        value payload = value::object(); payload["source"] = projection.at("target"); payload["target"] = target;
        // Resolve once against plain conversions; never recursively peel a carrier.
        const auto calculation = resolve_plain_conversion(metadata, payload);
        value out = value::object();
        out["kind"] = "carrier_conversion"; out["source"] = projection.at("source"); out["target"] = target;
        out["projection"] = projection; out["payload_conversion"] = calculation;
        out["states"] = value::array();
        if (projection.has("states")) {
            for (const auto &state : projection.at("states").items) {
                value branch = value::object(); branch["name"] = state.at("name");
                if (state.has("test")) branch["test_expression"] = "${value}." + state.at("test").text() + "()";
                branch["conversion"] = value::object(); branch["conversion"]["kind"] = "checked_payload";
                if (state.has("casts") && state.at("casts").has(target)) {
                    branch["conversion"] = state.at("casts").at(target);
                    branch["conversion"]["kind"] = "expression";
                }
                out["states"].items.push_back(branch);
            }
        }
        out["evaluation"] = "evaluate_source_once_then_select_state";
        out["failure"] = "throw_without_mutating_source";
        return out;
    }

    value conversion_functions(const value &metadata)
    {
        value functions = value::object();
        if (!metadata.has("conversions")) return functions;
        const auto &r = registry(metadata);
        for (const auto &[source, binding] : r.at("bindings").fields) {
            for (const auto &target : binding.at("targets").items) {
                value request = value::object(); request["source"] = source; request["target"] = target;
                const auto plan = resolve_conversion(metadata, request);
                const auto &recipe = r.at("targets").at(target.text());
                value f = value::object(), op = value::object(), parameter = value::object();
                f["namespace"] = "scpp"; f["header"] = plan.at("header");
                f["source"] = "include/scpp/generated/public_" + plan.at("api").text() + ".cpp";
                op["name"] = "cast_" + target.text(); op["returns"] = target;
                parameter["name"] = "value"; parameter["type"] = source; parameter["passing"] = "const_ref";
                op["parameters"] = value::array(); op["parameters"].items.push_back(parameter);
                auto expression = recipe.at("expression").text();
                const std::string marker = "${operand}";
                for (auto p = expression.find(marker); p != std::string::npos; p = expression.find(marker))
                    expression.replace(p, marker.size(), binding.at("operand").text());
                op["expression"] = expression;
                if (recipe.has("transport")) op["transport"] = recipe.at("transport");
                op["includes"] = value::array();
                for (const auto *record : {&binding, &recipe}) if (record->has("includes"))
                    for (const auto &header : record->at("includes").items) op["includes"].items.push_back(header);
                op["uses"] = value::array(); op["uses"].items = {value(source), target};
                f["operation"] = op;
                if (functions.has(plan.at("api").text())) throw std::runtime_error("Duplicate conversion API identity");
                functions[plan.at("api").text()] = f;
            }
        }
        return functions;
    }
}
