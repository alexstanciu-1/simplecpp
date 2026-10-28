#include "api_generator/runtime.hpp"
#include <algorithm>
#include <fstream>
#include <iterator>
#include <iostream>
using namespace scpp::api_generation;
static void require(bool ok) { if (!ok) throw std::runtime_error("Value preparation proof failed"); }
static value load(const char *path) {
    std::ifstream in(path); if (!in) throw std::runtime_error("Missing fixture");
    return parse_json({std::istreambuf_iterator<char>(in), std::istreambuf_iterator<char>()});
}
template<class F> static void fails(F f, const char *message) {
    try { f(); } catch (const std::exception &e) {
        require(std::string(e.what()).find(message) != std::string::npos); return;
    }
    throw std::runtime_error("Missing expected preparation failure");
}
int main(int argc, char **argv) {
    require(argc == 3);
    const auto m = load(argv[1]);
    auto p = load(argv[2]); p.fields.erase("carriers");
    p.fields.erase("vectors");
    p.fields.erase("string"); // String dependencies are covered by string_preparation.
    const auto plan = prepare_runtime(m, p);
    require(plan.values.size() == 3 && plan.boolean_type == "bool_t");
    require(plan.source_types.at("bool") == "bool_t" && plan.source_types.at("int") == "int64");
    require(plan.aliases.at("int_t") == "int64" && plan.aliases.at("float_t") == "float64");
    require(plan.short_aliases == std::vector<std::string>{"int_t"});
    auto configured = m; configured["source_types"]["int"] = "int32";
    require(prepare_runtime(configured, p).aliases.at("int_t") == "int32");
    configured = m; configured["aliases"]["int_t"] = "null";
    fails([&] { prepare_runtime(configured, p); }, "configured int/float default");
    auto changed = m;
    changed["values"]["bool_t"]["condition"] = "implicit";
    fails([&] { prepare_runtime(changed, p); }, "Unsupported condition");
    changed = m; changed["values"]["null_t"]["condition"] = "implicit";
    fails([&] { prepare_runtime(changed, p); }, "Null has no condition");
    changed = m; changed["values"]["bool_t"]["operations"].items.emplace_back("add");
    fails([&] { prepare_runtime(changed, p); }, "Unsupported simple value operation");
    changed = m; changed["values"]["bool_t"]["operations"].items.emplace_back("not");
    fails([&] { prepare_runtime(changed, p); }, "Duplicate simple value operation");
    changed = m; changed["source_types"]["bool"] = "int64";
    fails([&] { prepare_runtime(changed, p); }, "identity disagrees");
    auto q = p; q["values"].items = {"null_t"};
    fails([&] { prepare_runtime(m, q); }, "require published boolean");
    q = p; q["values"].items.clear();
    fails([&] { prepare_runtime(m, q); }, "comparisons require published boolean");
    q = p; q["values"].items.emplace_back("bool_t");
    fails([&] { prepare_runtime(m, q); }, "Duplicate published value");
    q = p; q["headers"]["values"] = q["headers"]["types"];
    fails([&] { prepare_runtime(m, q); }, "Conflicting public headers");
    // Comparisons preserve original operands; unlike arithmetic they need no
    // common type or lossless implicit conversion at signed/uint64 extremes.
    q = p; q["types"].items = {"int64", "uint64"};
    q["operators"] = parse_json(R"([{"operation":"less","operands":["int64","uint64"],"both_orders":true}])");
    const auto comparisons = prepare_runtime(m, q);
    require(comparisons.operations.size() == 2);
    for (const auto &op : comparisons.operations) {
        require(op.result == "bool_t" && op.execution == "direct_values");
        require(op.promotion == "exact_integer_comparison");
    }
    changed = m; changed["operations"]["less"]["native_arguments"] = m.at("operations").at("add").at("native_arguments");
    fails([&] { prepare_runtime(changed, q); }, "Native comparison adapters");
    q = p; q["operators"] = parse_json(R"([{"operation":"negate","operands":["int64","int64"]}])");
    fails([&] { prepare_runtime(m, q); }, "operand count");
    q["operators"] = parse_json(R"([{"operation":"negate","operands":["int64"],"both_orders":true}])");
    fails([&] { prepare_runtime(m, q); }, "Both orders");
    q["operators"] = parse_json(R"([{"operation":"add_assign","operands":[{"native":"int"},"int64"]}])");
    fails([&] { prepare_runtime(m, q); }, "public left operand");
    q["operators"] = parse_json(R"([{"operation":"shift_left","operands":["int32","int64"]}])");
    const auto shifts = prepare_runtime(m, q);
    require(shifts.operations[0].execution == "int32" && shifts.operations[0].result == "int32");
    const auto description = describe(shifts);
    require(description.at("operations").items[0].at("right_conversion").text() == "identity");
    q["operators"] = parse_json(R"([{"operation":"add_assign","operands":["int32","int64"]}])");
    const auto mutation = prepare_runtime(m, q);
    require(mutation.operations[0].execution == "int64" && mutation.operations[0].result == "int32");
    require(describe(mutation).at("operations").items[0].at("write_back").text() == "checked_after_calculation");
    changed = m; changed["operations"]["negate"]["form"] = "binary";
    fails([&] { prepare_runtime(changed, p); }, "Unsupported form");
    // No integers required for a boolean/null-only publication.
    q = p; q["types"].items.clear(); q["operators"].items.clear();
    require(prepare_runtime(m, q).types.empty());
    auto reordered = p;
    std::reverse(reordered["values"].items.begin(), reordered["values"].items.end());
    require(emit_runtime(plan) == emit_runtime(prepare_runtime(m, reordered)));
    std::cout << "Value preparation proofs passed\n";
}
