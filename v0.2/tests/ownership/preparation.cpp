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
    require(plan.ownership.records.size() == 4 && plan.operations.empty());
    require(plan.ownership.shared_size > 0 && plan.ownership.weak_align > 0);
    const auto artifacts = emit_runtime(plan);
    require(artifacts.contains("public/scpp/ownership.hpp") && artifacts.contains("src/ownership.cpp"));
    require(describe(plan).at("ownership").at("records").items.size() == 4);
    const auto &record = plan.ownership.records.back(); // record_throwing_move sorts last
    require(record.families.size() == 1 && record.families[0].family == "value");
    const auto facts = describe(plan).at("ownership");
    unsigned qualification_count = 0, inline_count = 0;
    for (const auto &r : facts.at("records").items) {
        for (const auto &f : r.at("families").items) {
            if (f.at("family").text() == "value") {
                ++inline_count;
                require(f.at("special_members").text() == "native_payload_dependent");
                require(!f.has("size") && !f.has("pointee"));
            } else {
                qualification_count += f.at("qualification_constructors").items.size();
            }
        }
    }
    require(qualification_count == 12 && inline_count == 4);
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
    exclusive["ownership"]["records"].items[0].fields.erase("const_families");
    const auto h = emit_runtime(prepare_runtime(m, exclusive)).at("public/scpp/ownership.hpp");
    require(h.find("class shared_") == std::string::npos && h.find("class weak_") == std::string::npos);
    for (const auto &request : {"[\"weak\"]", "[\"value\"]", "[\"shared\",\"shared\"]"}) {
        bad = p; bad["ownership"]["records"].items[0]["const_families"] = parse_json(request);
        rejects([&] { prepare_runtime(m, bad); });
    }
    wrong = m; wrong["ownership"].fields.erase("value");
    rejects([&] { prepare_runtime(wrong, p); });
    wrong = m; wrong["ownership"]["value"]["special_members"] = "always_noexcept";
    rejects([&] { prepare_runtime(wrong, p); });
    wrong = m; wrong["ownership"].fields.erase("const_pointee");
    rejects([&] { prepare_runtime(wrong, p); });
    auto const_only = exclusive;
    const_only["ownership"]["records"].items[0].fields.erase("families");
    const_only["ownership"]["records"].items[0]["const_families"] = parse_json("[\"shared\",\"weak\",\"unique\"]");
    const auto const_header = emit_runtime(prepare_runtime(m, const_only)).at("public/scpp/ownership.hpp");
    require(const_header.find("class shared_const_record_probe") != std::string::npos);
    require(const_header.find("class shared_record_probe") == std::string::npos);
    auto value_only = exclusive;
    value_only["ownership"]["records"].items[0]["families"] = parse_json("[\"value\"]");
    const auto value_header = emit_runtime(prepare_runtime(m, value_only)).at("public/scpp/ownership.hpp");
    require(value_header.find("storage_") == std::string::npos);
    auto reordered = p;
    for (auto &r : reordered["ownership"]["records"].items) {
        std::reverse(r["families"].items.begin(), r["families"].items.end());
        if (r.has("const_families")) std::reverse(r["const_families"].items.begin(), r["const_families"].items.end());
    }
    require(emit_runtime(prepare_runtime(m, reordered)) == artifacts);
}
