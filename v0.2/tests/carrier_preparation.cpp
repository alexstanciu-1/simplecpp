#include "api_generator/runtime.hpp"
#include <algorithm>
#include <fstream>
#include <iterator>
using namespace scpp::api_generation;
static void require(bool ok) { if (!ok) throw std::runtime_error("Carrier preparation proof failed"); }
static value load(const char *path) {
    std::ifstream in(path); if (!in) throw std::runtime_error("Missing fixture");
    return parse_json({std::istreambuf_iterator<char>(in), std::istreambuf_iterator<char>()});
}
template<class F> static void fails(F f, const char *message) {
    try { f(); } catch (const std::exception &e) {
        require(std::string(e.what()).find(message) != std::string::npos); return;
    }
    throw std::runtime_error("Expected carrier preparation failure");
}
int main(int argc, char **argv) {
    require(argc == 3);
    const auto m = load(argv[1]), p = load(argv[2]);
    const auto plan = prepare_runtime(m, p);
    require(plan.carriers.size() == 16);
    require(plan.source_types.at("nullable<int64>") == "nullable_int64");
    require(plan.operations.size() == 151); // No lifted operator matrix.
    auto q = p; q["carriers"]["specializations"].items.push_back(q["carriers"]["specializations"].items[0]);
    fails([&] { prepare_runtime(m, q); }, "Duplicate carrier");
    q = p; q["carriers"]["specializations"].items[0]["payload"] = "nullable_int64";
    fails([&] { prepare_runtime(m, q); }, "null/nested carriers");
    q = p; q["carriers"]["specializations"].items[0]["payload"] = "null_t";
    fails([&] { prepare_runtime(m, q); }, "null/nested carriers");
    q = p; q["carriers"]["specializations"].items[0]["family"] = "result_unknown";
    fails([&] { prepare_runtime(m, q); }, "Unknown carrier family");
    q = p; q["carriers"]["header"] = q["vectors"]["header"];
    fails([&] { prepare_runtime(m, q); }, "Conflicting public headers");
    auto changed = m; changed["carriers"]["families"]["result"] = "error";
    fails([&] { prepare_runtime(changed, p); }, "Unsupported carrier default");
    changed = m; changed["carriers"]["error"]["state"] = "message_not_empty";
    fails([&] { prepare_runtime(changed, p); }, "Unsupported state");
    changed = m; changed["carriers"]["extraction"] = "recursive";
    fails([&] { prepare_runtime(changed, p); }, "Unsupported carrier extraction");
    changed = m; changed["carriers"]["extraction"] = "explicit_checked_one_layer_value_copy";
    for (const auto &c : prepare_runtime(changed, p).carriers) require(!c.implicit_extraction);
    q = p; q.fields.erase("carriers");
    require(prepare_runtime(m, q).carriers.empty());
    require(!emit_runtime(prepare_runtime(m, q)).contains("public/scpp/carriers.hpp"));
    q = p; std::reverse(q["carriers"]["specializations"].items.begin(), q["carriers"]["specializations"].items.end());
    require(emit_runtime(prepare_runtime(m, q)) == emit_runtime(plan));
}
