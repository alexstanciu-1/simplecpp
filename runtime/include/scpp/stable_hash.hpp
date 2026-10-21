#pragma once

#include "scpp/int_t.hpp"
#include "scpp/string_t.hpp"

#include <cstdint>
#include <string>
#include <string_view>

namespace scpp::stable_hash {

// FNV-1a, 64-bit unsigned arithmetic over exact bytes. Independent of table hashing.
// Persisted consumers must use this new namespace; v1/std::hash keys are incompatible.
inline constexpr std::string_view key_format = "stable_hash_u64:fnv1a64:v2:";

[[nodiscard]] inline std::uint64_t bytes_u64(const std::string_view bytes) noexcept {
	std::uint64_t hash = UINT64_C(14695981039346656037);
	for (const unsigned char byte : bytes) {
		hash ^= byte;
		hash *= UINT64_C(1099511628211);
	}
	return hash;
}

[[nodiscard]] inline std::uint64_t string_u64(const string_t &value) noexcept {
	return bytes_u64(value.native_value());
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

[[nodiscard]] inline string_t versioned_key(const string_t &value) {
	return string_t(std::string(key_format) + string_hex(value).native_value());
}

[[nodiscard]] inline int_t<std::uint64_t> string_uint64(const string_t &value) {
	return int_t<std::uint64_t>(string_u64(value));
}

} // namespace scpp::stable_hash
