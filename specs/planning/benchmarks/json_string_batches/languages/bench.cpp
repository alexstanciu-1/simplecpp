#include <nlohmann/json.hpp>
#include <chrono>
#include <iostream>
#include <string>

int main(int argc, char** argv) {
    if (argc != 4) return 1;
    const int count = std::stoi(argv[1]), size = std::stoi(argv[2]), loops = std::stoi(argv[3]);
    std::string wire;
    std::getline(std::cin, wire);
    const auto word = std::string(size / 2, 'a') + 'b' + std::string(size - size / 2 - 1, 'z');
    const auto checked = nlohmann::json::parse(wire);
    const auto& values = checked.at("value");
    if (!values.is_array() || values.size() != static_cast<std::size_t>(count)) return 2;
    for (const auto& value : values) if (value != word) return 3;

    for (auto k : {std::max(1, loops / 8), loops}) {
        std::size_t total = 0;
        auto start = std::chrono::steady_clock::now();
        for (int i = 0; i < k; ++i) total += nlohmann::json::parse(wire).at("value").size();
        auto elapsed = std::chrono::steady_clock::now() - start;
        if (total != static_cast<std::size_t>(k) * count) return 4;
        if (k == loops) std::cout << std::chrono::duration<double, std::nano>(elapsed).count() / k << '\n';
    }
}
