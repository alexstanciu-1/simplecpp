#pragma once

#include "scpp/int_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/support/hash_t.hpp"

#include <cstdint>
#include <string>

namespace scpp::stable_hash {

// Stable compiler/runtime fingerprint helpers.
//
// Contract:
// - algorithm is the Simple C++ runtime string-key hash
// - output must remain stable across process runs and host stdlib versions
// - changing this algorithm is a persistent-artifact compatibility break and
//   must be versioned by cache/artifact layers before rollout
[[nodiscard]] inline std::uint64_t string_u64(const string_t &value) noexcept {
	return hash_detail::key_ops<string_t>::hash(value);
}

[[nodiscard]] inline string_t to_hex(const std::uint64_t hash) {
	const char *digits = "0123456789abcdef";
	char buffer[16];
	for (int index = 15; index >= 0; --index) {
		const auto shift = static_cast<unsigned>((15 - index) * 4);
		buffer[index] = digits[(hash >> shift) & 0x0fU];
	}
	return string_t(std::string(buffer, 16));
}

[[nodiscard]] inline string_t string_hex(const string_t &value) {
	return to_hex(string_u64(value));
}

[[nodiscard]] inline int_t<std::uint64_t> string_uint64(const string_t &value) {
	return int_t<std::uint64_t>(string_u64(value));
}

} // namespace scpp::stable_hash
