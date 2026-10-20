#pragma once

namespace scpp {

// Sentinel tag used by result_or_false<T> for explicit failure-state comparisons.
struct false_sentinel_t final {};

inline constexpr false_sentinel_t false_sentinel{};

} // namespace scpp
