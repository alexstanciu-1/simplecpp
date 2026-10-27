#include "conversions.hpp"
#include "names.hpp"
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
            authored_name(name); fields_only(target, "expression includes", "conversion target");
            target.at("expression").text();
            if (!metadata.at("types").has(name) && !metadata.at("families").has(name)) throw std::runtime_error("Unknown conversion target");
        }
        return r;
    }

    value resolve_conversion(const value &metadata, const value &request)
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
