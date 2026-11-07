#pragma once
#include <cstdint>
#include <limits>
#include <string_view>

namespace scpp::detail {
// Canonical unsigned decimal through UINT32_MAX; -1 denotes a byte-string key.
inline std::int64_t source_key_index(std::string_view key) noexcept {
    if (key.empty() || (key.size() > 1 && key.front() == '0')) return -1;
    std::int64_t number = 0;
    for (unsigned char c : key) {
        if (c < '0' || c > '9') return -1;
        number = number * 10 + (c - '0');
        if (number > std::numeric_limits<std::uint32_t>::max()) return -1;
    }
    return number;
}
}
