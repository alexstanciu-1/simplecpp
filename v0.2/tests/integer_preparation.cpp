#include "api_generator/runtime.hpp"
#include <algorithm>
#include <fstream>
#include <iterator>
#include <iostream>
#include <limits>

using namespace scpp::api_generation;
static void require(bool ok) { if (!ok) throw std::runtime_error("Integer preparation proof failed"); }
template<class F> static void fails(F work, const std::string &text) {
    try { work(); } catch (const std::exception &error) {
        require(std::string(error.what()).find(text) != std::string::npos); return;
    }
    throw std::runtime_error("Expected rejection: " + text);
}
static value load(const char *path) {
    std::ifstream in(path); if (!in) throw std::runtime_error("Missing fixture");
    return parse_json({std::istreambuf_iterator<char>(in), std::istreambuf_iterator<char>()});
}
static value request(const std::string &l, const std::string &r) {
    value out = value::object(); out["operation"] = "add"; out["operands"] = value::array();
    out["operands"].items = {value(l), value(r)}; return out;
}
static value native_request(const std::string &public_type, const std::string &native) {
    auto out = request(public_type, public_type);
    out["operands"].items[1] = value::object();
    out["operands"].items[1]["native"] = native;
    return out;
}
int main(int argc, char **argv) {
    require(argc == 3);
    const auto metadata = load(argv[1]);
    auto publication = load(argv[2]);
    std::erase_if(publication["operators"].items, [&](const auto &r) {
        for (const auto &arg : r.at("operands").items)
            if (arg.type == value::kind::text && arg.text() == "float64") return true;
        const auto &op = metadata.at("operations").at(r.at("operation").text());
        return op.at("promotion").text() != "integer_domain_maximum" || op.at("form").text() != "binary";
    });
    const auto plan = prepare_runtime(metadata, publication);
    require(plan.types.size() == 2 && plan.operations.size() == 50 && std::count_if(plan.conversions.begin(), plan.conversions.end(), [](const auto &c) { return c.implementation == "checked_integer_cast"; }) == 14);
    for (const auto &op : plan.operations)
        require(op.execution == (op.left.identity == "int32" && op.right.identity == "int32" ? "int32" : "int64"));
    for (const auto &c : plan.conversions) if (!c.native_source && c.implementation == "checked_integer_cast") require(c.lossless == (c.target == "int64"));
    require(emit_runtime(plan) == emit_runtime(prepare_runtime(metadata, publication)));
    auto reversed = publication; std::reverse(reversed["types"].items.begin(), reversed["types"].items.end());
    std::reverse(reversed["operators"].items.begin(), reversed["operators"].items.end());
    require(emit_runtime(plan) == emit_runtime(prepare_runtime(metadata, reversed)));
    auto bad = metadata; bad["construction"]["explicit"] = parse_json("false");
    fails([&] { prepare_runtime(bad, publication); }, "must be explicit");
    bad = metadata; bad["types"]["int32"]["bits"] = parse_json("16");
    fails([&] { prepare_runtime(bad, publication); }, "identity disagrees");
    bad = metadata; bad["operations"]["add"]["public_operator"] = "-";
    fails([&] { prepare_runtime(bad, publication); }, "Unsupported public_operator");
    bad = metadata; bad["promotion_policies"]["integer_domain_maximum"]["different_signed"] = "int32";
    fails([&] { prepare_runtime(bad, publication); }, "Domain maximum");
    bad = metadata; bad["construction"]["native_integer_inputs"].items.emplace_back("double");
    fails([&] { prepare_runtime(bad, publication); }, "Unsupported native");
    bad = metadata; bad["operations"]["divide"]["implementation"] = "integer_add";
    fails([&] { prepare_runtime(bad, publication); }, "Unsupported implementation");
    auto p = publication; p["operators"].items.push_back(request("int32", "int64"));
    fails([&] { prepare_runtime(metadata, p); }, "Duplicate operator");
    p = publication; p["headers"]["types"] = "../escape.hpp";
    fails([&] { prepare_runtime(metadata, p); }, "Invalid public header");
    p = publication; p["headers"]["types"] = "scpp/./numeric.hpp";
    fails([&] { prepare_runtime(metadata, p); }, "must be normalized");
    p = publication; p["headers"]["types"] = p["headers"]["operators"];
    fails([&] { prepare_runtime(metadata, p); }, "Conflicting public headers");
    p = publication; p["unknown"] = "field";
    fails([&] { prepare_runtime(metadata, p); }, "unknown field");
    p = publication; p["types"] = value::array(); p["types"].items = {"int8", "int16"};
    p["operators"].items = {request("int8", "int16")};
    fails([&] { prepare_runtime(metadata, p); }, "published execution type");

    // Full metadata domain: 56 admitted ordered pairs; no uint64/signed fallback.
    p = publication; p["types"] = value::array();
    for (const auto &[name, t] : metadata.at("types").fields) p["types"].items.emplace_back(name);
    unsigned admitted = 0;
    for (const auto &[left, l] : metadata.at("types").fields) {
        for (const auto &[right, r] : metadata.at("types").fields) {
            p["operators"].items = {request(left, right)};
            const bool rejected = (left == "uint64" && r.yes("signed")) || (right == "uint64" && l.yes("signed"));
            if (rejected) fails([&] { prepare_runtime(metadata, p); }, "not admitted");
            else {
                const auto resolved = prepare_runtime(metadata, p);
                const auto expected = left == right ? left : (l.yes("signed") || r.yes("signed") ? "int64" : "uint64");
                require(resolved.operations[0].result == expected); ++admitted;
            }
        }
    }
    require(admitted == 56);

    // Host type ranges feed the SAME domain resolver, independent of literal values.
    for (const auto &n : plan.native_inputs) {
        for (const auto &[name, t] : metadata.at("types").fields) {
            auto q = native_request(name, n.cpp);
            for (unsigned order = 0; order != 2; ++order) {
                p["operators"].items = {q, request("int64", "int64"), request("uint64", "uint64")};
                if (name != "int64" && name != "uint64") p["operators"].items.push_back(request(name, name));
                const bool blocked = (name == "uint64" && n.is_signed) || (t.yes("signed") && !n.is_signed && n.digits == 64);
                if (blocked) fails([&] { prepare_runtime(metadata, p); }, "not admitted");
                else {
                    const auto resolved = prepare_runtime(metadata, p);
                    const unsigned bits = static_cast<unsigned>(std::stoul(t.at("bits").scalar));
                    const auto expected = t.yes("signed") == n.is_signed && bits == n.digits + unsigned(n.is_signed)
                        ? name : t.yes("signed") || n.is_signed ? "int64" : "uint64";
                    const auto &op = *std::find_if(resolved.operations.begin(), resolved.operations.end(),
                        [](const auto &o) { return o.left.native || o.right.native; });
                    require(op.result == expected);
                    const auto &native_operand = order == 0 ? op.right : op.left;
                    require(native_operand.native && native_operand.cpp == n.cpp);
                }
                std::reverse(q["operands"].items.begin(), q["operands"].items.end());
            }
        }
    }
    p = publication; p["operators"].items = {native_request("int32", "int")};
    fails([&] { prepare_runtime(metadata, p); }, "same-type execution operator");
    p = publication; p["headers"]["native_bridge"] = p["headers"]["operators"];
    fails([&] { prepare_runtime(metadata, p); }, "Conflicting public headers");
    p = publication; p["operators"].items = {native_request("int32", "double")};
    fails([&] { prepare_runtime(metadata, p); }, "Unregistered native");
    p["operators"].items = {native_request("int32", "unsigned long long")};
    if (std::numeric_limits<unsigned long long>::digits == 64)
        fails([&] { prepare_runtime(metadata, p); }, "not admitted");
    p["operators"].items = {native_request("int32", "int")};
    bad = metadata; bad["operations"]["add"].fields.erase("native_arguments");
    fails([&] { prepare_runtime(bad, p); }, "not enabled");
    bad = metadata; bad["operations"]["add"]["native_arguments"]["policy"] = "literal_value";
    fails([&] { prepare_runtime(bad, p); }, "Unsupported policy");
    p["operators"].items[0]["operands"].items[0] = p["operators"].items[0]["operands"].items[1];
    fails([&] { prepare_runtime(metadata, p); }, "Native-only");
    p = publication; p["operators"].items.push_back(native_request("int32", "int"));
    fails([&] { prepare_runtime(metadata, p); }, "Duplicate operator");
    // Duplicate detection is operation-specific; each operator has its own native
    // bridge policy and forwarding dependency, not an addition-shaped shortcut.
    for (const auto &[name, binding] : metadata.at("operations").fields) {
        if (binding.at("promotion").text() != "integer_domain_maximum" || binding.at("form").text() != "binary") continue;
        p = publication; auto q = request("int32", "int64"); q["operation"] = name;
        p["operators"].items = {q};
        auto resolved = prepare_runtime(metadata, p);
        require(resolved.operations.size() == 1 && resolved.operations[0].execution == "int64");
        require(resolved.operations[0].symbol == binding.at("public_operator").text());
        q = native_request("int32", "int"); q["operation"] = name;
        p["operators"].items = {q, request("int32", "int32")};
        if (name != "add") fails([&] { prepare_runtime(metadata, p); }, "same-type execution operator");
        p["operators"].items[1]["operation"] = name;
        require(prepare_runtime(metadata, p).operations.size() == 2);
        bad = metadata; bad["operations"][name].fields.erase("native_arguments");
        fails([&] { prepare_runtime(bad, p); }, "not enabled");
    }
    std::cout << "Integer preparation proofs passed\n";
}
