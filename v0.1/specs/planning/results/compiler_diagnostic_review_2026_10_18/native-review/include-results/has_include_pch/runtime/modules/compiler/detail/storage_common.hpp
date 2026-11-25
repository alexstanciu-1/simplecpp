#pragma once

#include "scpp/shared_p.hpp"
#include "scpp/int_t.hpp"
#include "scpp/string_t.hpp"
#include <concepts>
#include <cstdint>
#include <limits>
#include <stdexcept>
#include <string_view>

namespace scpp::compiler::detail {

// Native boundaries reject coercion from booleans, floating point and strings.
template<std::signed_integral T> constexpr T storage_number(T value) { return value; }
template<std::signed_integral T> constexpr T storage_number(scpp::int_t<T> value) { return value.native_value(); }
template<class T>
concept storage_integer = requires(T value) { storage_number(value); };

template<class T>
concept storage_string = std::convertible_to<const T &, std::string_view> || std::same_as<T, scpp::string_t>;
inline std::string_view storage_key(const storage_string auto &key) {
	if constexpr (std::same_as<std::remove_cvref_t<decltype(key)>, scpp::string_t>) return key.native_value();
	else return std::string_view(key);
}

inline std::size_t storage_capacity(storage_integer auto input) {
	const auto capacity = storage_number(input);
	if (capacity < 0) throw std::invalid_argument("Storage capacity must be non-negative");
	if (static_cast<std::uintmax_t>(capacity) > std::numeric_limits<std::size_t>::max())
		throw std::length_error("Storage capacity exceeds native size");
	return static_cast<std::size_t>(capacity);
}

// Kept separately so the exhaustion boundary can be proved without huge allocation.
inline std::int64_t append_position(std::uintmax_t extent) {
	if (extent >= static_cast<std::uintmax_t>(std::numeric_limits<std::int64_t>::max()))
		throw std::overflow_error("Storage positions exhausted");
	return static_cast<std::int64_t>(extent);
}

template<class T>
struct storage_record {
	static_assert(std::is_class_v<T> && !scpp::detail::is_shared_p_v<T>,
		"Storage takes a record type, not shared_p<T>");
	using handle = scpp::shared_p<T>;
	static void validate(const handle &record) {
		if (!record) throw std::invalid_argument("Storage requires a non-null record");
	}
};

} // namespace scpp::compiler::detail
