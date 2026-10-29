#pragma once
// Reviewed donor: runtime/include/scpp/support/hash_t.hpp at 0c230e0bc.
// Typed table; index primitives are shared with the private packed engine.
#include "hash_index.hpp"
#include <cstdint>
#include <cstddef>
#include <limits>
#include <stdexcept>
#include <string_view>
#include <functional>
#include <type_traits>
#include <utility>
#include <variant>
#include <vector>

namespace scpp::detail {

// Concrete runtime types use private adapters. No numeric-string normalization.
template<class Key> struct integer_hash_key {
    using rep = decltype(std::declval<Key>().native_value());
    static std::uint64_t hash(const Key& key) noexcept {
        return hash_detail::mix64(static_cast<std::uint64_t>(key.native_value()));
    }
    static bool equal(const Key& a, const Key& b) noexcept {
        return a.native_value() == b.native_value();
    }
};
template<class Key> struct string_hash_key {
    static std::string_view bytes(const Key& key) {
        return {key.data(), static_cast<std::size_t>(key.size().native_value())};
    }
    static std::uint64_t hash(const Key& key) {
        return hash_detail::mix64(static_cast<std::uint64_t>(std::hash<std::string_view>{}(bytes(key))));
    }
    static bool equal(const Key& a, const Key& b) { return bytes(a) == bytes(b); }
};

// Private storage owner. Public generated methods copy reads and own mutation.
// See v0.2/specs/hash_slice.md. Traversal preservation is deferred as RT-DEBT-01.
template<class T_VALUE, class T_KEY, class KeyOps>
class typed_hash final {
    static_assert(std::is_nothrow_default_constructible_v<T_VALUE> && std::is_nothrow_default_constructible_v<T_KEY>);
    static_assert(std::is_nothrow_move_constructible_v<T_VALUE> && std::is_nothrow_move_constructible_v<T_KEY>);
    static_assert(std::is_nothrow_move_assignable_v<T_VALUE> && std::is_nothrow_move_assignable_v<T_KEY>);
	using native_values_t = std::vector<T_VALUE>;
	using native_keys_t = std::vector<T_KEY>;
	using native_live_t = std::vector<std::uint8_t>;

	native_values_t values_;
	native_keys_t keys_;
	native_live_t live_;
	hash_detail::hash_index_variant_t hash_index_;
	std::size_t live_size_ = 0;

	bool check_and_rehash() {
		if (std::holds_alternative<std::monostate>(hash_index_)) {
			rehash(4);
			return true;
		}
		std::uint32_t cap = 0;
		std::uint32_t sz = 0;
		std::uint32_t del = 0;
		std::visit([&](auto &idx) {
			using I = std::decay_t<decltype(idx)>;
			if constexpr (!std::is_same_v<I, std::monostate>) {
				cap = idx.capacity_;
				sz = idx.size_;
				del = idx.deleted_count_;
			}
		}, hash_index_);
		if (static_cast<std::uint64_t>(sz) + del >= (static_cast<std::uint64_t>(cap) * 3) / 4 || !hash_detail::index_holds_entries(hash_index_, values_.size())) {
			const auto next_capacity = (sz >= cap / 2) ? checked_capacity(static_cast<std::uint64_t>(cap) * 2) : cap;
			rehash(next_capacity == 0 ? 4 : next_capacity);
			return true;
		}
		return false;
	}

	void rehash(std::uint32_t next_capacity) {
		auto next_index = hash_detail::index_for_entries(values_.size());

		std::visit([&](auto &idx) {
			using I = std::decay_t<decltype(idx)>;
			if constexpr (!std::is_same_v<I, std::monostate>) {
				idx.capacity_ = next_capacity;
				idx.size_ = 0;
				idx.deleted_count_ = 0;
				idx.ctrl_bytes_.assign(next_capacity, static_cast<std::uint8_t>(hash_detail::ctrl_state_t::empty));
				idx.buckets_.resize(next_capacity);
				for (std::uint32_t i = 0; i < values_.size(); ++i) {
					if (!live_[i]) continue;
					const auto hash = KeyOps::hash(keys_[i]);
					auto bucket = static_cast<std::uint32_t>(hash % next_capacity);
					const auto fingerprint = static_cast<std::uint8_t>(hash & 0x7F);
					while (idx.ctrl_bytes_[bucket] < 128) {
						bucket = (bucket + 1) % next_capacity;
					}
					idx.ctrl_bytes_[bucket] = fingerprint;
					idx.buckets_[bucket] = static_cast<typename decltype(idx.buckets_)::value_type>(i);
					idx.size_++;
				}
			}
		}, next_index);

		hash_index_ = std::move(next_index);
	}

	void add_to_index(const T_KEY &key, std::uint32_t physical_index) {
		// Rebuilding includes the newly appended live entry already.
		if (check_and_rehash()) return;
		std::visit([&](auto &idx) {
			using I = std::decay_t<decltype(idx)>;
			if constexpr (!std::is_same_v<I, std::monostate>) {
				const auto hash = KeyOps::hash(key);
				auto bucket = static_cast<std::uint32_t>(hash % idx.capacity_);
				const auto fingerprint = static_cast<std::uint8_t>(hash & 0x7F);
				while (idx.ctrl_bytes_[bucket] < 128
					&& idx.ctrl_bytes_[bucket] != static_cast<std::uint8_t>(hash_detail::ctrl_state_t::deleted)) {
					bucket = (bucket + 1) % idx.capacity_;
				}
				if (idx.ctrl_bytes_[bucket] == static_cast<std::uint8_t>(hash_detail::ctrl_state_t::deleted)) {
					--idx.deleted_count_;
				}
				idx.ctrl_bytes_[bucket] = fingerprint;
				idx.buckets_[bucket] = static_cast<typename decltype(idx.buckets_)::value_type>(physical_index);
				idx.size_++;
			}
		}, hash_index_);
	}

	std::pair<bool, T_VALUE *> find_key(const T_KEY &key) {
		if (std::holds_alternative<std::monostate>(hash_index_)) {
			return {false, nullptr};
		}
		std::pair<bool, T_VALUE *> result{false, nullptr};
		std::visit([&](auto &idx) {
			using I = std::decay_t<decltype(idx)>;
			if constexpr (!std::is_same_v<I, std::monostate>) {
				if (!idx.capacity_) return;
				const auto hash = KeyOps::hash(key);
				auto bucket = static_cast<std::uint32_t>(hash % idx.capacity_);
				const auto fingerprint = static_cast<std::uint8_t>(hash & 0x7F);
				while (idx.ctrl_bytes_[bucket] != static_cast<std::uint8_t>(hash_detail::ctrl_state_t::empty)) {
					if (idx.ctrl_bytes_[bucket] == fingerprint) {
						const auto physical_index = static_cast<std::uint32_t>(idx.buckets_[bucket]);
						if (live_[physical_index] && KeyOps::equal(keys_[physical_index], key)) {
							result = {true, &values_[physical_index]};
							return;
						}
					}
					bucket = (bucket + 1) % idx.capacity_;
				}
			}
		}, hash_index_);
		return result;
	}

	std::pair<bool, const T_VALUE *> find_key(const T_KEY &key) const {
		if (std::holds_alternative<std::monostate>(hash_index_)) {
			return {false, nullptr};
		}
		std::pair<bool, const T_VALUE *> result{false, nullptr};
		std::visit([&](const auto &idx) {
			using I = std::decay_t<decltype(idx)>;
			if constexpr (!std::is_same_v<I, std::monostate>) {
				if (!idx.capacity_) return;
				const auto hash = KeyOps::hash(key);
				auto bucket = static_cast<std::uint32_t>(hash % idx.capacity_);
				const auto fingerprint = static_cast<std::uint8_t>(hash & 0x7F);
				while (idx.ctrl_bytes_[bucket] != static_cast<std::uint8_t>(hash_detail::ctrl_state_t::empty)) {
					if (idx.ctrl_bytes_[bucket] == fingerprint) {
						const auto physical_index = static_cast<std::uint32_t>(idx.buckets_[bucket]);
						if (live_[physical_index] && KeyOps::equal(keys_[physical_index], key)) {
							result = {true, &values_[physical_index]};
							return;
						}
					}
					bucket = (bucket + 1) % idx.capacity_;
				}
			}
		}, hash_index_);
		return result;
	}

	bool erase_key(const T_KEY &key) {
		if (std::holds_alternative<std::monostate>(hash_index_)) {
			return false;
		}
		bool found = false;
		std::visit([&](auto &idx) {
			using I = std::decay_t<decltype(idx)>;
			if constexpr (!std::is_same_v<I, std::monostate>) {
				if (!idx.capacity_) return;
				const auto hash = KeyOps::hash(key);
				auto bucket = static_cast<std::uint32_t>(hash % idx.capacity_);
				const auto fingerprint = static_cast<std::uint8_t>(hash & 0x7F);
				while (idx.ctrl_bytes_[bucket] != static_cast<std::uint8_t>(hash_detail::ctrl_state_t::empty)) {
					if (idx.ctrl_bytes_[bucket] == fingerprint) {
						const auto physical_index = static_cast<std::uint32_t>(idx.buckets_[bucket]);
						if (live_[physical_index] && KeyOps::equal(keys_[physical_index], key)) {
							idx.ctrl_bytes_[bucket] = static_cast<std::uint8_t>(hash_detail::ctrl_state_t::deleted);
							idx.size_--;
							idx.deleted_count_++;
							live_[physical_index] = 0;
							keys_[physical_index] = T_KEY{};
							values_[physical_index] = T_VALUE{};
							live_size_--;
							found = true;
							return;
						}
					}
					bucket = (bucket + 1) % idx.capacity_;
				}
			}
		}, hash_index_);
		return found;
	}

    void insert_or_assign_key(T_KEY key, T_VALUE value) {
        if (auto [found, ptr] = find_key(key); found) {
            *ptr = std::move(value);
            return;
        }
        const auto old_size = values_.size();
        if (old_size >= std::numeric_limits<std::uint32_t>::max())
            throw std::length_error("hash_physical_entry_limit");
        // Allocation may grow capacities, but membership/order remain unchanged on
        // failure. Rehash prepares a replacement index before publishing it.
        try {
            keys_.push_back(std::move(key));
            values_.push_back(std::move(value));
            live_.push_back(1);
            add_to_index(keys_.back(), static_cast<std::uint32_t>(old_size));
        } catch (...) {
            if (live_.size() > old_size) live_.pop_back();
            if (values_.size() > old_size) values_.pop_back();
            if (keys_.size() > old_size) keys_.pop_back();
            throw;
        }
        ++live_size_;
    }

	[[nodiscard]] T_KEY next_append_key() const requires requires { typename KeyOps::rep; } {
		using rep_t = typename KeyOps::rep;
		std::uint64_t max_key = 0;
		bool found_non_negative_key = false;
		for (std::uint32_t i = 0; i < keys_.size(); ++i) {
			if (!live_[i]) continue;
			const auto native_key = keys_[i].native_value();
			if constexpr (std::is_signed_v<rep_t>) {
				if (native_key < 0) {
					continue;
				}
			}
			const auto unsigned_key = static_cast<std::uint64_t>(native_key);
			if (!found_non_negative_key || unsigned_key > max_key) {
				max_key = unsigned_key;
				found_non_negative_key = true;
			}
		}
		if (!found_non_negative_key) {
			return T_KEY{static_cast<rep_t>(0)};
		}
		if (max_key >= static_cast<std::uint64_t>(std::numeric_limits<rep_t>::max())) {
			throw std::overflow_error("hash_append_key_overflow");
		}
		return T_KEY{static_cast<rep_t>(max_key + 1)};
	}

public:
    static std::uint32_t checked_capacity(std::uint64_t size) {
        if (size > std::numeric_limits<std::uint32_t>::max())
            throw std::length_error("hash_index_capacity_limit");
        return static_cast<std::uint32_t>(size);
    }
    typed_hash() = default;
    typed_hash(const typed_hash& other)
        : values_(other.values_), keys_(other.keys_), live_(other.live_), live_size_(other.live_size_) {
        if (live_size_) rehash(checked_capacity(static_cast<std::uint64_t>(values_.size()) * 2));
    }
    typed_hash(typed_hash&& other) noexcept { swap(other); }
    typed_hash& operator=(const typed_hash& other) {
        if (this != &other) { typed_hash copy(other); swap(copy); }
        return *this;
    }
    typed_hash& operator=(typed_hash&& other) noexcept {
        if (this != &other) { typed_hash discarded; swap(discarded); swap(other); }
        return *this;
    }
    void swap(typed_hash& other) noexcept {
        values_.swap(other.values_); keys_.swap(other.keys_); live_.swap(other.live_);
        hash_index_.swap(other.hash_index_); std::swap(live_size_, other.live_size_);
    }
    std::size_t size() const noexcept { return live_size_; }
    const T_VALUE* find(const T_KEY& key) const { return find_key(key).second; }
    void set(T_KEY key, T_VALUE value) { insert_or_assign_key(std::move(key), std::move(value)); }
    bool remove(const T_KEY& key) { return erase_key(key); }
    T_KEY append(T_VALUE value) requires requires { typename KeyOps::rep; } {
        auto key = next_append_key();
        insert_or_assign_key(key, std::move(value));
        return key;
    }
    void clear() noexcept { typed_hash empty; swap(empty); }

    // Private instrumentation/traversal; no generated public cursor or reference API.
    template<class Visitor> void visit_entries(Visitor&& visitor) const {
        for (std::size_t i = 0; i < keys_.size(); ++i)
            if (live_[i]) visitor(keys_[i], values_[i]);
    }
    std::size_t physical_size() const noexcept { return values_.size(); }
    unsigned index_bytes() const noexcept {
        return std::visit([](const auto& index) -> unsigned {
            using I = std::decay_t<decltype(index)>;
            if constexpr (std::is_same_v<I, std::monostate>) return 0;
            else return sizeof(typename std::decay_t<decltype(index.buckets_)>::value_type);
        }, hash_index_);
    }
};
} // namespace scpp::detail
