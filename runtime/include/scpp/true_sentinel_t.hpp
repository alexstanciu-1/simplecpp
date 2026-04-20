#pragma once

namespace scpp {

// Sentinel tag used by result_or_bool<T> for explicit true-state construction and comparison.
struct true_sentinel_t final {};

inline constexpr true_sentinel_t true_sentinel{};

} // namespace scpp
