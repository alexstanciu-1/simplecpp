#pragma once

#include "scpp/detail/process_memory.hpp"
#include "scpp/int_t.hpp"
#include "scpp/bool_t.hpp"

namespace scpp {


// Implements memory_get_usage() in a process-level, benchmark-oriented form.
// How: the runtime currently reports resident process memory in bytes rather than allocator internals.
[[nodiscard]] inline int_t<> memory_get_usage() {
	return int_t<>(detail::process_memory_usage_bytes());
}

// Implements memory_get_usage(true|false) with the current prototype semantics.
// How: the bool parameter is accepted for surface compatibility, but both branches currently return the same process-level byte count.
[[nodiscard]] inline int_t<> memory_get_usage(bool_t) {
	return int_t<>(detail::process_memory_usage_bytes());
}

// Implements memory_get_peak_usage() in a process-level, benchmark-oriented form.
// How: the runtime currently reports peak resident process memory in bytes rather than allocator internals.
[[nodiscard]] inline int_t<> memory_get_peak_usage() {
	return int_t<>(detail::process_peak_memory_usage_bytes());
}

// Implements memory_get_peak_usage(true|false) with the current prototype semantics.
// How: the bool parameter is accepted for surface compatibility, but both branches currently return the same process-level byte count.
[[nodiscard]] inline int_t<> memory_get_peak_usage(bool_t) {
	return int_t<>(detail::process_peak_memory_usage_bytes());
}

} // namespace scpp
