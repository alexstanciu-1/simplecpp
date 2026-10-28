#include "api_generator/runtime.hpp"
#include <algorithm>
#include <fstream>
#include <iterator>
#include <iostream>
using namespace scpp::api_generation;
static void require(bool ok) { if (!ok) throw std::runtime_error("Floating preparation proof failed"); }
static value load(const char *path) {
    std::ifstream in(path); if (!in) throw std::runtime_error("Missing fixture");
    return parse_json({std::istreambuf_iterator<char>(in), std::istreambuf_iterator<char>()});
}
template<class F> static void fails(F f, const char *message) {
    try { f(); } catch (const std::exception &e) {
        require(std::string(e.what()).find(message) != std::string::npos); return;
    }
    throw std::runtime_error("Missing expected floating preparation failure");
}
int main(int argc, char **argv) {
    require(argc == 3);
    const auto m = load(argv[1]);
    auto p = load(argv[2]);
    p.fields.erase("vectors");
    p.fields.erase("string"); // String dependencies are covered by string_preparation.
    const auto plan = prepare_runtime(m, p);
    const auto wide_cast = std::find_if(plan.conversions.begin(), plan.conversions.end(), [](const auto &c) {
        return c.source == "int64" && c.target == "float64" && !c.native_source;
    });
    require(wide_cast != plan.conversions.end() && !wide_cast->lossless && !wide_cast->can_fail);
    auto q = p; q["types"].items.clear();
    for (const auto &[name, type] : m.at("types").fields) q["types"].items.emplace_back(name);
    for (const auto &[name, type] : m.at("types").fields) {
        for (const auto &operation : {"add", "less", "add_assign"}) {
            q["operators"] = parse_json(std::string("[{\"operation\":\"") + operation + "\",\"operands\":[\"" + name + "\",\"float64\"]}]");
            if (type.at("bits").scalar == "64") fails([&] { prepare_runtime(m, q); }, "explicit cast");
            else {
                const auto resolved = prepare_runtime(m, q);
                require(resolved.operations.size() == 1 && resolved.operations[0].execution == "float64");
            }
        }
    }
    q = p; q["operators"] = parse_json(R"([{"operation":"add","operands":["int32","float64"]}])");
    auto changed = m;
    std::erase_if(changed["scalar_casts"].items, [](const auto &v) { return v.text() == "integer_to_floating_rounding"; });
    fails([&] { prepare_runtime(changed, q); }, "normalization requires an admitted cast");
    q["operators"].items[0]["operation"] = "add_assign";
    changed = m;
    std::erase_if(changed["scalar_casts"].items, [](const auto &v) { return v.text() == "floating_to_integer_truncate_checked"; });
    fails([&] { prepare_runtime(changed, q); }, "write-back requires an admitted cast");
    changed = m; changed["floating"]["mixed_integer"] = "round_everything";
    fails([&] { prepare_runtime(changed, p); }, "Unsupported mixed_integer");
    changed = m; changed["floating"]["division_by_zero"] = "unchecked";
    fails([&] { prepare_runtime(changed, p); }, "Unsupported division_by_zero");
    changed = m; changed["scalar_casts"].items.emplace_back("integer_to_floating_rounding");
    fails([&] { prepare_runtime(changed, p); }, "Duplicate scalar cast");
    q = p; q["operators"] = parse_json(R"([{"operation":"remainder","operands":["float64","float64"]}])");
    fails([&] { prepare_runtime(m, q); }, "Unregistered numeric operation");
    q = p; q["values"].items = {"bool_t", "null_t"};
    q["operators"] = parse_json(R"([{"operation":"add","operands":["float64","float64"]}])");
    fails([&] { prepare_runtime(m, q); }, "published operand");
    changed = m; std::reverse(changed["scalar_casts"].items.begin(), changed["scalar_casts"].items.end());
    require(emit_runtime(plan) == emit_runtime(prepare_runtime(changed, p)));
    std::cout << "Floating preparation proofs passed\n";
}
