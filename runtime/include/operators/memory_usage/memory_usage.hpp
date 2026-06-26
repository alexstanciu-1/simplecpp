#pragma once

#include "lang/php/support/php_common.hpp"

namespace scpp {

namespace detail {

// Reads one numeric memory field from /proc/self/status when available.
// How: Linux exposes resident and peak resident process memory in kilobytes through VmRSS and VmHWM.
[[nodiscard]] inline std::int64_t read_proc_status_kb(const char *field_name) {
	std::ifstream input("/proc/self/status");
	if (!input.is_open()) {
		return static_cast<std::int64_t>(-1);
	}

	std::string line;
	while (std::getline(input, line)) {
		if (line.rfind(field_name, 0) != 0) {
			continue;
		}

		std::istringstream stream(line.substr(std::char_traits<char>::length(field_name)));
		std::int64_t value_kb = 0;
		std::string unit;
		if (stream >> value_kb >> unit) {
			return value_kb;
		}
		return static_cast<std::int64_t>(-1);
	}

	return static_cast<std::int64_t>(-1);
}

// Returns the current resident process memory in bytes when the platform exposes it.
// How: Linux uses VmRSS; unsupported platforms fall back to zero because the runtime does not track allocator-internal usage yet.
[[nodiscard]] inline std::int64_t process_memory_usage_bytes() {
#if defined(__linux__)
	const std::int64_t value_kb = read_proc_status_kb("VmRSS:");
	if (value_kb >= 0) {
		return value_kb * static_cast<std::int64_t>(1024);
	}
#endif
	return static_cast<std::int64_t>(0);
}

// Returns the peak resident process memory in bytes when the platform exposes it.
// How: Linux prefers VmHWM; Unix-like fallbacks use getrusage where ru_maxrss is defined in kilobytes on Linux and bytes on macOS/BSD.
[[nodiscard]] inline std::int64_t process_peak_memory_usage_bytes() {
#if defined(__linux__)
	const std::int64_t value_kb = read_proc_status_kb("VmHWM:");
	if (value_kb >= 0) {
		return value_kb * static_cast<std::int64_t>(1024);
	}
#endif
#if defined(__unix__) || defined(__APPLE__)
	struct rusage usage {};
	if (getrusage(RUSAGE_SELF, &usage) == 0) {
		#if defined(__APPLE__)
		return static_cast<std::int64_t>(usage.ru_maxrss);
		#else
		return static_cast<std::int64_t>(usage.ru_maxrss) * static_cast<std::int64_t>(1024);
		#endif
	}
#endif
	return static_cast<std::int64_t>(0);
}

} // namespace detail

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
