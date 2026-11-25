#pragma once
#include <cstdint>
#include <string>
#include <vector>

struct Token { std::int64_t offset, length, kind; };
struct Scan {
    std::string source, reason;
    std::vector<Token> tokens;
    std::int64_t error = -1;
};
Scan scan(std::string source, std::size_t prior_count = 0);
