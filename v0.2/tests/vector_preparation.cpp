#include "api_generator/runtime.hpp"
#include <algorithm>
#include <fstream>
#include <iterator>
using namespace scpp::api_generation;
static void require(bool ok) { if (!ok) throw std::runtime_error("Vector preparation proof failed"); }
static value load(const char *path) {
    std::ifstream in(path); if (!in) throw std::runtime_error("Missing fixture");
    return parse_json({std::istreambuf_iterator<char>(in), std::istreambuf_iterator<char>()});
}
template<class F> static void fails(F f, const char *message) {
    try { f(); } catch (const std::exception &e) {
        require(std::string(e.what()).find(message) != std::string::npos); return;
    }
    throw std::runtime_error("Expected vector preparation failure");
}
int main(int argc, char **argv) {
    require(argc == 3);
    const auto m = load(argv[1]);
    auto p = load(argv[2]); p.fields.erase("carriers");
    const auto plan = prepare_runtime(m, p);
    require(plan.vectors.size() == 2 && plan.vectors[0].name == "vector_int64");
    require(plan.source_types.at("vector<string>") == "vector_string");
    auto q = p; q["vectors"]["specializations"].items.push_back(q["vectors"]["specializations"].items[0]);
    fails([&] { prepare_runtime(m, q); }, "Duplicate vector specialization");
    q = p; q["vectors"]["specializations"].items[0]["element"] = "missing";
    fails([&] { prepare_runtime(m, q); }, "published element type");
    q = p; q["vectors"]["specializations"].items[0]["native_value"] = "const char*";
    fails([&] { prepare_runtime(m, q); }, "requires string element");
    q = p; q["operators"].items.clear();
    fails([&] { prepare_runtime(m, q); }, "element equality");
    q = p; q["vectors"]["specializations"].items[0]["casts_from"].items.emplace_back("vector_missing");
    fails([&] { prepare_runtime(m, q); }, "published source specialization");
    q = p; q["vectors"]["specializations"].items[0]["casts_from"].items.emplace_back("vector_string");
    auto changed = m; changed["string"]["text_casts"].items.clear();
    q["string"]["casts"].items.clear();
    fails([&] { prepare_runtime(changed, q); }, "explicit element conversion");
    q = p; q["vectors"]["header"] = q["string"]["header"];
    fails([&] { prepare_runtime(m, q); }, "Conflicting public headers");
    changed = m; changed["vector"]["storage"] = "std_unordered_map";
    fails([&] { prepare_runtime(changed, p); }, "Unsupported storage");
    changed = m; changed["vector"]["read"] = "reference";
    fails([&] { prepare_runtime(changed, p); }, "Unsupported read");
    q = p; q.fields.erase("vectors");
    require(prepare_runtime(m, q).vectors.empty());
    require(!emit_runtime(prepare_runtime(m, q)).contains("public/scpp/vectors.hpp"));
    q = p; std::reverse(q["vectors"]["specializations"].items.begin(), q["vectors"]["specializations"].items.end());
    for (auto &v : q["vectors"]["specializations"].items) std::reverse(v["operations"].items.begin(), v["operations"].items.end());
    require(emit_runtime(prepare_runtime(m, q)) == emit_runtime(plan));
}
