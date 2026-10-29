#include "api_generator/runtime.hpp"
#include <fstream>
#include <iterator>
#include <stdexcept>
#include <algorithm>
using namespace scpp::api_generation;
static void require(bool ok) { if (!ok) throw std::runtime_error("Ownership preparation proof failed"); }
static value load(const char *p) {
    std::ifstream f(p); require(static_cast<bool>(f));
    return parse_json({std::istreambuf_iterator<char>(f), std::istreambuf_iterator<char>()});
}
template<class F> static void rejects(F f) {
    try { f(); } catch (const std::runtime_error &) { return; }
    throw std::runtime_error("Invalid ownership metadata accepted");
}
int main(int argc, char **argv) {
    require(argc == 3);
    const auto m = load(argv[1]), p = load(argv[2]);
    const auto plan = prepare_runtime(m, p);
    require(plan.ownership.records.size() == 2 && plan.operations.empty());
    require(plan.ownership.shared_size > 0 && plan.ownership.weak_align > 0);
    const auto artifacts = emit_runtime(plan);
    require(artifacts.contains("public/scpp/ownership.hpp") && artifacts.contains("src/ownership.cpp"));
    require(describe(plan).at("ownership").at("records").items.size() == 2);
    auto shuffled = p;
    std::reverse(shuffled["ownership"]["records"].items.begin(), shuffled["ownership"]["records"].items.end());
    require(emit_runtime(prepare_runtime(m, shuffled)) == artifacts);
    auto absent = p; absent.fields.erase("ownership");
    require(!emit_runtime(prepare_runtime(m, absent)).contains("src/ownership.cpp"));
    auto bad = p; bad["ownership"]["header"] = "scpp/integers.hpp";
    rejects([&] { prepare_runtime(m, bad); });
    bad = p; bad["ownership"]["records"].items[0]["name"] = "int64";
    rejects([&] { prepare_runtime(m, bad); });
    bad = p; bad["ownership"]["records"].items[0]["name"] = "class";
    rejects([&] { prepare_runtime(m, bad); });
    bad = p; bad["ownership"]["records"].items[0]["header"] = "../bad.hpp";
    rejects([&] { prepare_runtime(m, bad); });
    bad = p; bad["ownership"]["records"].items[0]["families"] = parse_json("[\"weak\"]");
    rejects([&] { prepare_runtime(m, bad); });
    bad = p; bad["ownership"]["records"].items[0]["families"] = parse_json("[\"shared\",\"shared\"]");
    rejects([&] { prepare_runtime(m, bad); });
    bad = p; bad["ownership"]["records"].items[0]["constructors"] = parse_json("[[\"unique_record_probe\"]]");
    rejects([&] { prepare_runtime(m, bad); });
    bad = p; bad["ownership"]["records"].items.push_back(bad["ownership"]["records"].items[0]);
    rejects([&] { prepare_runtime(m, bad); });
    auto wrong = m; wrong["ownership"]["weak_access"] = "implicit";
    rejects([&] { prepare_runtime(wrong, p); });
    wrong = m; wrong.fields.erase("ownership");
    rejects([&] { prepare_runtime(wrong, p); });
    // A separately requested exclusive-only family does not pull in shared/weak wrappers.
    auto exclusive = p;
    exclusive["ownership"]["records"].items.resize(1);
    exclusive["ownership"]["records"].items[0]["families"] = parse_json("[\"unique\"]");
    const auto h = emit_runtime(prepare_runtime(m, exclusive)).at("public/scpp/ownership.hpp");
    require(h.find("class shared_") == std::string::npos && h.find("class weak_") == std::string::npos);
}
