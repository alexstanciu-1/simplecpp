#pragma once
// Shared donor control-byte index primitives; physical position determines width.
#include <cstdint>
#include <cstddef>
#include <limits>
#include <type_traits>
#include <variant>
#include <vector>
namespace scpp::detail {
namespace hash_detail {
template <typename idx_t>
struct flat_hash_index_t {
	std::vector<std::uint8_t> ctrl_bytes_;
	std::vector<idx_t> buckets_;
	std::uint32_t capacity_ = 0;
	std::uint32_t size_ = 0;
	std::uint32_t deleted_count_ = 0;
};

enum class ctrl_state_t : std::uint8_t {
	empty = 0b10000000,
	deleted = 0b11111110,
	sentinel = 0b11111111,
};

using hash_index_variant_t = std::variant<
	std::monostate,
	flat_hash_index_t<std::uint8_t>,
	flat_hash_index_t<std::uint16_t>,
	flat_hash_index_t<std::uint32_t>
>;

// Buckets store physical entry positions, not bucket offsets. Erased entries
// retain their positions, so index width must be independent of live capacity.
[[nodiscard]] inline hash_index_variant_t index_for_entries(std::size_t count) {
	if (count <= 256) return flat_hash_index_t<std::uint8_t>{};
	if (count <= 65536) return flat_hash_index_t<std::uint16_t>{};
	return flat_hash_index_t<std::uint32_t>{};
}

[[nodiscard]] inline bool index_holds_entries(const hash_index_variant_t &index, std::size_t count) {
	return std::visit([&](const auto &idx) {
		using I = std::decay_t<decltype(idx)>;
		if constexpr (std::is_same_v<I, std::monostate>) {
			return false;
		} else {
			using position_t = typename std::decay_t<decltype(idx.buckets_)>::value_type;
			return count == 0 || count - 1 <= std::numeric_limits<position_t>::max();
		}
	}, index);
}

[[nodiscard]] inline std::uint64_t mix64(std::uint64_t value) noexcept {
	value ^= value >> 16;
	value *= 0x85ebca6bull;
	value ^= value >> 13;
	value *= 0xc2b2ae35ull;
	value ^= value >> 16;
	return value;
}

} // namespace hash_detail
}
