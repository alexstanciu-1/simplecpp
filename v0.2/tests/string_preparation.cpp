#include "api_generator/runtime.hpp"
#include <algorithm>
#include <fstream>
#include <iterator>
using namespace scpp::api_generation;
static void require(bool ok) { if (!ok) throw std::runtime_error("String preparation proof failed"); }
static value load(const char *path) {
    std::ifstream in(path); if (!in) throw std::runtime_error("Missing fixture");
    return parse_json({std::istreambuf_iterator<char>(in), std::istreambuf_iterator<char>()});
}
template<class F> static void fails(F f, const char *message) {
    try { f(); } catch (const std::exception &e) {
        require(std::string(e.what()).find(message) != std::string::npos); return;
    }
    throw std::runtime_error("Expected string preparation failure");
}
int main(int argc, char **argv) {
    require(argc == 3);
    const auto m = load(argv[1]);
    auto p = load(argv[2]); p.fields.erase("vectors");
    const auto plan = prepare_runtime(m, p);
    require(plan.string.name == "string" && plan.string.count_type == "int64");
    require(plan.string.operations.size() == 8 && plan.source_types.at("string") == "string");
    require(std::count_if(plan.conversions.begin(), plan.conversions.end(), [](const auto &c) {
        return c.source == "string" || c.target == "string";
    }) == 8);
    for (const auto &op : plan.string.operations) {
        require(op.execution == "string" && op.left.identity == "string" && op.right.identity == "string");
        require(op.result == (op.operation == "add" || op.operation == "add_assign" ? "string" : "bool_t"));
    }
    auto q = p; q["string"]["operations"].items.emplace_back("subtract");
    fails([&] { prepare_runtime(m, q); }, "not enabled");
    q = p; q["string"]["operations"].items.emplace_back("add");
    fails([&] { prepare_runtime(m, q); }, "Duplicate published string");
    q = p; q["string"]["casts"].items.emplace_back("null_t");
    fails([&] { prepare_runtime(m, q); }, "published numeric or boolean");
    q = p; q["string"]["casts"].items.emplace_back("int64");
    fails([&] { prepare_runtime(m, q); }, "Duplicate published text cast");
    q = p; q["types"].items = {"int32"};
    fails([&] { prepare_runtime(m, q); }, "published count type");
    q = p; q["string"]["header"] = q["headers"]["values"];
    fails([&] { prepare_runtime(m, q); }, "Conflicting public headers");
    auto changed = m; changed["string"]["null_pointer"] = "empty";
    fails([&] { prepare_runtime(changed, p); }, "Unsupported null_pointer");
    changed = m; changed["string"]["text_casts"].items = {"decimal_integer"};
    fails([&] { prepare_runtime(changed, p); }, "not enabled by metadata");
    changed = m; changed.fields.erase("string");
    fails([&] { prepare_runtime(changed, p); }, "requires metadata");
    q = p; q.fields.erase("string");
    auto absent = prepare_runtime(m, q);
    require(absent.string.name.empty() && !absent.source_types.contains("string"));
    require(!emit_runtime(absent).contains("public/scpp/string.hpp"));
    q = p;
    std::reverse(q["string"]["operations"].items.begin(), q["string"]["operations"].items.end());
    std::reverse(q["string"]["casts"].items.begin(), q["string"]["casts"].items.end());
    require(emit_runtime(prepare_runtime(m, q)) == emit_runtime(plan));
}
