// Bounds of the native std::string capacity observation used by the JSON reader.
#pragma once
#include <cstddef>

namespace scpp::detail::json::reader_detail {
inline constexpr std::size_t first_qualified_prefix = 1024;
inline constexpr std::size_t last_qualified_prefix = 65536;

constexpr bool predicted_full_prefix(std::size_t size) noexcept {
	return size >= first_qualified_prefix && size <= last_qualified_prefix && size % 8 == 7;
}
}
