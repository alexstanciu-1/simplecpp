#include "scpp/regex/string_grapheme_count.hpp"
#include "scpp/regex/string_grapheme_slice.hpp"
#include <algorithm>
#include <iostream>
#include <string_view>
#include <limits>
#include <sstream>
#include <string>
#include <vector>

namespace {
bool verify(const std::string& line) {
    std::istringstream tokens(line);
    std::vector<size_t> boundaries{0};
    std::string text;
    for (std::string cluster; tokens >> cluster;) {
        for (size_t i = 0; i < cluster.size(); i += 2)
            text.push_back(static_cast<char>(std::stoi(cluster.substr(i, 2), nullptr, 16)));
        boundaries.push_back(text.size());
    }
    const scpp::string input{text.data(), static_cast<scpp::int64>(text.size())};
    const auto count = static_cast<scpp::int64>(boundaries.size() - 1);
    if (scpp::regex::grapheme_count(input) != count) return false;
    for (scpp::int64 start = -1; start <= count + 1; ++start) {
        for (scpp::int64 length : {scpp::int64{-1}, scpp::int64{0}, scpp::int64{1}, scpp::int64{2},
                                   std::numeric_limits<scpp::int64>::max()}) {
            std::string_view expected;
            if (start >= 0 && start < count && length > 0) {
                const auto end = start + std::min(length, count - start);
                expected = std::string_view(text).substr(boundaries[start], boundaries[end] - boundaries[start]);
            }
            const auto result = scpp::regex::grapheme_slice(input, start, length);
            if (std::string_view(result.data(), result.size()) != expected) return false;
        }
    }
    return true;
}
}

int main() {
    std::string line;
    while (std::getline(std::cin, line)) {
        if (!verify(line)) {
            std::cerr << "Grapheme boundary mismatch for UTF-8 clusters: " << line << '\n';
            return 1;
        }
    }
}
