#pragma once

#include "scpp/string_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/bool_t.hpp"

#include <chrono>
#include <cstdint>
#include <iomanip>
#include <sstream>
#include <stdexcept>

namespace scpp::php {

// Implements PHP microtime() string mode.
// How: system_clock is sampled once, then formatted as "0.xxxxxxxx seconds" to mirror PHP's default contract.
inline string_t microtime() {
	const auto now = std::chrono::system_clock::now();
	const auto since_epoch = now.time_since_epoch();
	const auto micros_total = std::chrono::duration_cast<std::chrono::microseconds>(since_epoch).count();
	const auto seconds = micros_total / static_cast<std::int64_t>(1000000);
	const auto micros_part = micros_total % static_cast<std::int64_t>(1000000);

	std::ostringstream stream;
	stream << std::fixed << std::setprecision(8)
		<< (static_cast<double>(micros_part) / 1000000.0)
		<< ' ' << seconds;
	return string_t(stream.str());
}

// Implements the numeric branch of PHP microtime(true).
// How: the helper is split out explicitly because the current runtime does not model a string|float union return type for one overload.
inline float_t microtime(bool_t as_float) {
	if (!as_float.native_value()) {
		throw std::logic_error("microtime(false) is not supported; use microtime() for string form");
	}

	const auto now = std::chrono::system_clock::now();
	const auto since_epoch = now.time_since_epoch();
	const auto micros_total = std::chrono::duration_cast<std::chrono::microseconds>(since_epoch).count();
	return float_t(static_cast<double>(micros_total) / 1000000.0);
}

} // namespace scpp::php
