#pragma once

#include "scpp/runtime_error.hpp"
#include "scpp/vector_t.hpp"

#include <bit>
#include <cstddef>
#include <cstdint>
#include <limits>
#include <string>

namespace scpp {

// Compact fixed-size bit storage for compiler dirty/visited state.
//
// Shape:
// - vector_t<uint64_t> backing, so storage stays in the Simple C++ container family
// - explicit resize before indexed operations
// - range-checked set/clear/test to catch stale dependency indexes early
class bitset_t final {
private:
	static constexpr std::size_t bits_per_word = 64U;

	vector_t<std::uint64_t> words_;
	std::size_t bit_count_ = 0;

	[[nodiscard]] static std::size_t word_count_for_bits(const std::size_t bit_count) noexcept {
		return (bit_count + bits_per_word - 1U) / bits_per_word;
	}

	[[nodiscard]] static std::size_t word_index(const std::size_t bit_index) noexcept {
		return bit_index / bits_per_word;
	}

	[[nodiscard]] static std::uint64_t bit_mask(const std::size_t bit_index) noexcept {
		return std::uint64_t{1} << (bit_index % bits_per_word);
	}

	void check_index(const std::size_t bit_index, const char *context) const {
		if (bit_index >= bit_count_) {
			throw runtime_error(
				std::string(context) + ": bit index " + std::to_string(bit_index)
					+ " is out of bounds for size " + std::to_string(bit_count_),
				"bounds_error"
			);
		}
	}

	void mask_unused_tail_bits() noexcept {
		if (bit_count_ == 0 || words_.size() == 0) {
			return;
		}
		const auto used_bits = bit_count_ % bits_per_word;
		if (used_bits == 0) {
			return;
		}
		const auto mask = (std::uint64_t{1} << used_bits) - 1U;
		words_.native_value().back() &= mask;
	}

public:
	bitset_t() = default;

	explicit bitset_t(const std::size_t bit_count) {
		resize(bit_count);
	}

	[[nodiscard]] std::size_t size() const noexcept {
		return bit_count_;
	}

	[[nodiscard]] std::size_t word_count() const noexcept {
		return words_.size();
	}

	[[nodiscard]] std::size_t capacity_bits() const noexcept {
		return words_.capacity() * bits_per_word;
	}

	void reserve_bits(const std::size_t bit_capacity) {
		words_.reserve(word_count_for_bits(bit_capacity));
	}

	void resize(const std::size_t bit_count) {
		const auto next_word_count = word_count_for_bits(bit_count);
		words_.native_value().resize(next_word_count, 0U);
		bit_count_ = bit_count;
		mask_unused_tail_bits();
	}

	void clear() noexcept {
		words_.clear();
		bit_count_ = 0;
	}

	void clear_all_bits() noexcept {
		for (auto &word : words_.native_value()) {
			word = 0U;
		}
	}

	void set(const std::size_t bit_index) {
		check_index(bit_index, "bitset_set");
		words_.native_value()[word_index(bit_index)] |= bit_mask(bit_index);
	}

	void clear(const std::size_t bit_index) {
		check_index(bit_index, "bitset_clear");
		words_.native_value()[word_index(bit_index)] &= ~bit_mask(bit_index);
	}

	[[nodiscard]] bool test(const std::size_t bit_index) const {
		check_index(bit_index, "bitset_test");
		return (words_.native_value()[word_index(bit_index)] & bit_mask(bit_index)) != 0U;
	}

	[[nodiscard]] bool any_set() const noexcept {
		for (const auto word : words_.native_value()) {
			if (word != 0U) {
				return true;
			}
		}
		return false;
	}

	[[nodiscard]] std::size_t count() const noexcept {
		std::size_t total = 0;
		for (const auto word : words_.native_value()) {
			total += static_cast<std::size_t>(std::popcount(word));
		}
		return total;
	}
};

} // namespace scpp
