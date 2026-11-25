#pragma once

#include <cstdint>
#include <string_view>

namespace scpp::detail {
inline std::uint64_t fnv1a64(std::string_view bytes) noexcept {
    std::uint64_t hash = 14695981039346656037ULL;
    for (unsigned char byte : bytes) {
        hash ^= byte;
        hash *= 1099511628211ULL;
    }
    return hash;
}
}
