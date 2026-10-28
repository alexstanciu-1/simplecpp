#include "runtime.hpp"
#include <filesystem>
#include <fstream>
#include <iostream>
#include <iterator>

namespace fs = std::filesystem;
static std::string read(const fs::path &path) {
    std::ifstream input(path, std::ios::binary);
    if (!input) throw std::runtime_error("Cannot read " + path.string());
    return {std::istreambuf_iterator<char>(input), std::istreambuf_iterator<char>()};
}
int main(int argc, char **argv) {
    try {
        if (argc != 4 && argc != 5) throw std::runtime_error("Usage: scpp_api_generator RUNTIME.json PUBLICATION.json OUTPUT [--check]");
        const bool check = argc == 5;
        if (check && std::string(argv[4]) != "--check") throw std::runtime_error("Unknown generator option");
        using namespace scpp::api_generation;
        const auto plan = prepare_runtime(parse_json(read(argv[1]), argv[1]), parse_json(read(argv[2]), argv[2]));
        const auto artifacts = emit_runtime(plan);
        const fs::path output = argv[3];
        if (fs::exists(output / "inventory.json")) {
            const auto previous = parse_json(read(output / "inventory.json"));
            if (previous.type != value::kind::array) throw std::runtime_error("Invalid old generated inventory");
            for (const auto &path : previous.items) if (!artifacts.contains(path.text()))
                throw std::runtime_error("Obsolete generated outputs; regenerate in empty staging");
        }
        for (const auto &[path, contents] : artifacts) {
            const auto target = output / path;
            if (check) {
                if (read(target) != contents) throw std::runtime_error("Stale generated file: " + target.string());
            } else {
                fs::create_directories(target.parent_path());
                std::ofstream file(target, std::ios::binary | std::ios::trunc);
                file.exceptions(std::ios::failbit | std::ios::badbit);
                file << contents;
            }
        }
        std::cout << (check ? "Verified " : "Generated ") << plan.types.size() + plan.values.size() + plan.vectors.size() + (!plan.string.name.empty() ? 1 : 0) << " runtime types and " << plan.operations.size() << " numeric operator signatures\n";
    } catch (const std::exception &error) { std::cerr << error.what() << '\n'; return 1; }
}
