#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/dynamic_t.hpp"
#include "scpp/util/global_string_pool.hpp"

#include <concepts>
#include <cstddef>
#include <cstdint>
#include <functional>
#include <iterator>
#include <limits>
#include <memory>
#include <stdexcept>
#include <string>
#include <type_traits>
#include <utility>
#include <variant>
#include <vector>

namespace scpp {

class mixed_t;

template <typename T> struct is_table_value : std::false_type {};

template <> struct is_table_value<null_t>   : std::true_type {};
template <> struct is_table_value<bool_t>   : std::true_type {};
template <> struct is_table_value<int_t<>>    : std::true_type {};
template <> struct is_table_value<float_t>  : std::true_type {};
template <> struct is_table_value<string_t> : std::true_type {};
template <> struct is_table_value<mixed_t>  : std::true_type {};
template <typename T, typename K> struct is_table_value<std::shared_ptr<hash_t<T, K>>> : std::true_type {};
template <typename T, typename K> struct is_table_value<std::unique_ptr<hash_t<T, K>>> : std::true_type {};
template <typename T, typename K> struct is_table_value<std::weak_ptr<hash_t<T, K>>>   : std::true_type {};

template <typename T>
concept TableValue = is_table_value<T>::value;

using maybe_value_t = nullable<mixed_t>;

template <typename T>
[[nodiscard]] inline bool_t is_nullopt(const nullable<T> &v) noexcept {
	return bool_t{!v.has_value().native_value()};
}

template <typename T>
[[nodiscard]] inline bool_t was_found(const nullable<T> &v) noexcept {
	return v.has_value();
}

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

[[nodiscard]] inline std::size_t index_capacity(const hash_index_variant_t &index) noexcept {
	std::size_t capacity = 0;
	std::visit([&](const auto &idx) {
		using I = std::decay_t<decltype(idx)>;
		if constexpr (!std::is_same_v<I, std::monostate>) {
			capacity = idx.capacity_;
		}
	}, index);
	return capacity;
}

[[nodiscard]] inline std::size_t estimated_index_storage_bytes(const hash_index_variant_t &index) noexcept {
	std::size_t bytes = 0;
	std::visit([&](const auto &idx) {
		using I = std::decay_t<decltype(idx)>;
		if constexpr (!std::is_same_v<I, std::monostate>) {
			using bucket_vector_t = std::decay_t<decltype(idx.buckets_)>;
			bytes = sizeof(I)
				+ idx.ctrl_bytes_.capacity() * sizeof(std::uint8_t)
				+ idx.buckets_.capacity() * sizeof(typename bucket_vector_t::value_type);
		}
	}, index);
	return bytes;
}

[[nodiscard]] inline std::uint64_t mix64(std::uint64_t value) noexcept {
	value ^= value >> 16;
	value *= 0x85ebca6bull;
	value ^= value >> 13;
	value *= 0xc2b2ae35ull;
	value ^= value >> 16;
	return value;
}

struct dyn_keys final {
	[[nodiscard]] static bool php_parse_non_negative_int_key(const std::string &text, std::uint32_t &out) {
#if defined(SCPP_LANGUAGE_TARGET_PHP) && SCPP_LANGUAGE_TARGET_PHP
		if (text.empty()) return false;
		if (text[0] == '+') return false;
		if (text[0] == '-') return false;
		if (text.size() > 1 && text[0] == '0') return false;

		std::uint64_t value = 0;
		for (const unsigned char ch : text) {
			if (ch < static_cast<unsigned char>('0') || ch > static_cast<unsigned char>('9')) return false;
			value = (value * 10u) + static_cast<std::uint64_t>(ch - static_cast<unsigned char>('0'));
			if (value > static_cast<std::uint64_t>(std::numeric_limits<std::uint32_t>::max())) return false;
		}

		out = static_cast<std::uint32_t>(value);
		return true;
#else
		(void)text;
		(void)out;
		return false;
#endif
	}

	[[nodiscard]] static std::uint32_t pack(const int_t<> &key) {
		return static_cast<std::uint32_t>(key.native_value());
	}

	[[nodiscard]] static std::uint32_t pack(const string_t &key) {
		std::uint32_t normalized_int_key = 0;
		if (php_parse_non_negative_int_key(key.native_value(), normalized_int_key)) {
			return normalized_int_key;
		}
		return global_string_pool::instance().intern(key);
	}

	[[nodiscard]] static std::uint32_t pack(const mixed_t &key);
	[[nodiscard]] static mixed_t unpack(std::uint32_t key);
	[[nodiscard]] static bool is_string(std::uint32_t key) {
		return global_string_pool::is_string_id(key);
	}
};

template <typename T_KEY>
struct key_ops;

template <typename T_KEY>
	requires std::is_enum_v<T_KEY>
struct key_ops<T_KEY> final {
	[[nodiscard]] static std::uint64_t hash(const T_KEY &key) {
		return mix64(static_cast<std::uint64_t>(static_cast<std::underlying_type_t<T_KEY>>(key)));
	}

	[[nodiscard]] static bool equal(const T_KEY &left, const T_KEY &right) {
		return left == right;
	}
};

template <>
struct key_ops<string_t> final {
	[[nodiscard]] static std::uint64_t hash(const string_t &key) {
		return mix64(static_cast<std::uint64_t>(std::hash<std::string>{}(key.native_value())));
	}

	[[nodiscard]] static bool equal(const string_t &left, const string_t &right) {
		return left.native_value() == right.native_value();
	}
};

template <typename Rep>
struct key_ops<int_t<Rep>> final {
	[[nodiscard]] static std::uint64_t hash(const int_t<Rep> &key) {
		return mix64(static_cast<std::uint64_t>(key.native_value()));
	}

	[[nodiscard]] static bool equal(const int_t<Rep> &left, const int_t<Rep> &right) {
		return left.native_value() == right.native_value();
	}
};

template <typename T>
struct key_ops<shared_p<T>> final {
	[[nodiscard]] static std::uint64_t hash(const shared_p<T> &key) {
		return mix64(static_cast<std::uint64_t>(reinterpret_cast<std::uintptr_t>(key.get())));
	}

	[[nodiscard]] static bool equal(const shared_p<T> &left, const shared_p<T> &right) {
		return left.get() == right.get();
	}
};

template <typename T>
struct key_ops<unique_p<T>> final {
	[[nodiscard]] static std::uint64_t hash(const unique_p<T> &key) {
		return mix64(static_cast<std::uint64_t>(reinterpret_cast<std::uintptr_t>(key.get())));
	}

	[[nodiscard]] static bool equal(const unique_p<T> &left, const unique_p<T> &right) {
		return left.get() == right.get();
	}
};

template <typename T>
struct key_ops<weak_p<T>> final {
	[[nodiscard]] static std::uint64_t hash(const weak_p<T> &key) {
		const auto locked = key.lock();
		return mix64(static_cast<std::uint64_t>(reinterpret_cast<std::uintptr_t>(locked.get())));
	}

	[[nodiscard]] static bool equal(const weak_p<T> &left, const weak_p<T> &right) {
		return left.lock().get() == right.lock().get();
	}
};

template <typename T_KEY>
concept hash_key = requires(const T_KEY &left, const T_KEY &right) {
	{ key_ops<T_KEY>::hash(left) } -> std::convertible_to<std::uint64_t>;
	{ key_ops<T_KEY>::equal(left, right) } -> std::convertible_to<bool>;
};

template <typename T_KEY>
concept typed_hash_key = hash_key<T_KEY>;

} // namespace hash_detail

template <typename T_VALUE, typename T_KEY>
class hash_t final {
private:
	static_assert(!std::same_as<T_VALUE, mixed_t>, "hash_t<mixed_t> uses the specialized dynamic implementation");
	static_assert(hash_detail::typed_hash_key<T_KEY>, "hash_t<T, T_KEY> requires a supported typed key family");

	using native_values_t = std::vector<T_VALUE>;
	using native_keys_t = std::vector<T_KEY>;
	using native_live_t = std::vector<std::uint8_t>;

	native_values_t values_;
	native_keys_t keys_;
	native_live_t live_;
	hash_detail::hash_index_variant_t hash_index_;
	std::size_t live_size_ = 0;

	void check_and_rehash() {
		if (std::holds_alternative<std::monostate>(hash_index_)) return;
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
		if (sz + del >= (cap * 3) / 4) {
			const auto next_capacity = (sz >= cap / 2) ? cap * 2 : cap;
			rehash(next_capacity == 0 ? 4 : next_capacity);
		}
	}

	void rehash(std::uint32_t next_capacity) {
		hash_detail::hash_index_variant_t next_index;
		if (next_capacity <= 256) {
			next_index.template emplace<hash_detail::flat_hash_index_t<std::uint8_t>>();
		} else if (next_capacity <= 65536) {
			next_index.template emplace<hash_detail::flat_hash_index_t<std::uint16_t>>();
		} else {
			next_index.template emplace<hash_detail::flat_hash_index_t<std::uint32_t>>();
		}

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
					const auto hash = hash_detail::key_ops<T_KEY>::hash(keys_[i]);
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
		if (std::holds_alternative<std::monostate>(hash_index_)) {
			rehash(4);
		}
		check_and_rehash();
		std::visit([&](auto &idx) {
			using I = std::decay_t<decltype(idx)>;
			if constexpr (!std::is_same_v<I, std::monostate>) {
				const auto hash = hash_detail::key_ops<T_KEY>::hash(key);
				auto bucket = static_cast<std::uint32_t>(hash % idx.capacity_);
				const auto fingerprint = static_cast<std::uint8_t>(hash & 0x7F);
				while (idx.ctrl_bytes_[bucket] < 128
					&& idx.ctrl_bytes_[bucket] != static_cast<std::uint8_t>(hash_detail::ctrl_state_t::deleted)) {
					bucket = (bucket + 1) % idx.capacity_;
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
				const auto hash = hash_detail::key_ops<T_KEY>::hash(key);
				auto bucket = static_cast<std::uint32_t>(hash % idx.capacity_);
				const auto fingerprint = static_cast<std::uint8_t>(hash & 0x7F);
				while (idx.ctrl_bytes_[bucket] != static_cast<std::uint8_t>(hash_detail::ctrl_state_t::empty)) {
					if (idx.ctrl_bytes_[bucket] == fingerprint) {
						const auto physical_index = static_cast<std::uint32_t>(idx.buckets_[bucket]);
						if (live_[physical_index] && hash_detail::key_ops<T_KEY>::equal(keys_[physical_index], key)) {
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
				const auto hash = hash_detail::key_ops<T_KEY>::hash(key);
				auto bucket = static_cast<std::uint32_t>(hash % idx.capacity_);
				const auto fingerprint = static_cast<std::uint8_t>(hash & 0x7F);
				while (idx.ctrl_bytes_[bucket] != static_cast<std::uint8_t>(hash_detail::ctrl_state_t::empty)) {
					if (idx.ctrl_bytes_[bucket] == fingerprint) {
						const auto physical_index = static_cast<std::uint32_t>(idx.buckets_[bucket]);
						if (live_[physical_index] && hash_detail::key_ops<T_KEY>::equal(keys_[physical_index], key)) {
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
				const auto hash = hash_detail::key_ops<T_KEY>::hash(key);
				auto bucket = static_cast<std::uint32_t>(hash % idx.capacity_);
				const auto fingerprint = static_cast<std::uint8_t>(hash & 0x7F);
				while (idx.ctrl_bytes_[bucket] != static_cast<std::uint8_t>(hash_detail::ctrl_state_t::empty)) {
					if (idx.ctrl_bytes_[bucket] == fingerprint) {
						const auto physical_index = static_cast<std::uint32_t>(idx.buckets_[bucket]);
						if (live_[physical_index] && hash_detail::key_ops<T_KEY>::equal(keys_[physical_index], key)) {
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

	template <typename T_INSERT_KEY>
	void insert_or_assign_key(T_INSERT_KEY &&key, T_VALUE value) {
		if (auto [found, ptr] = find_key(key); found) {
			*ptr = std::move(value);
			return;
		}
		const auto physical_index = static_cast<std::uint32_t>(values_.size());
		keys_.push_back(std::forward<T_INSERT_KEY>(key));
		values_.push_back(std::move(value));
		live_.push_back(1);
		live_size_++;
		add_to_index(keys_[physical_index], physical_index);
	}

	[[nodiscard]] bool is_live_physical_index(std::uint32_t index) const noexcept {
		return index < live_.size() && live_[index] != 0;
	}

	[[nodiscard]] const T_KEY &key_from_physical_index(std::uint32_t index) const {
		if (!is_live_physical_index(index)) {
			throw std::out_of_range("hash_t::key_from_physical_index: invalid entry index");
		}
		return keys_[index];
	}

	[[nodiscard]] T_VALUE &value_from_physical_index(std::uint32_t index) {
		if (!is_live_physical_index(index)) {
			throw std::out_of_range("hash_t::value_from_physical_index: invalid entry index");
		}
		return values_[index];
	}

	[[nodiscard]] const T_VALUE &value_from_physical_index(std::uint32_t index) const {
		if (!is_live_physical_index(index)) {
			throw std::out_of_range("hash_t::value_from_physical_index const: invalid entry index");
		}
		return values_[index];
	}

	[[nodiscard]] T_KEY next_append_key() const requires detail::is_int_t_v<T_KEY> {
		using rep_t = detail::int_rep_t<T_KEY>;
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
			throw std::overflow_error("hash_t::append key overflow");
		}
		return T_KEY{static_cast<rep_t>(max_key + 1)};
	}

public:
	hash_t() = default;

	hash_t(const hash_t &other) requires(std::copyable<T_VALUE> && std::copyable<T_KEY>)
		: values_(other.values_), keys_(other.keys_), live_(other.live_), live_size_(other.live_size_) {
		if (live_size_ > 0) {
			rehash(static_cast<std::uint32_t>(values_.size() > 0 ? values_.size() * 2 : 4));
		}
	}

	hash_t(hash_t &&) noexcept = default;

	hash_t &operator=(const hash_t &other) requires(std::copyable<T_VALUE> && std::copyable<T_KEY>) {
		if (this == &other) return *this;
		hash_t copy(other);
		*this = std::move(copy);
		return *this;
	}

	hash_t &operator=(hash_t &&) noexcept = default;
	~hash_t() = default;

	[[nodiscard]] bool_t empty() const noexcept {
		return bool_t{live_size_ == 0};
	}

	[[nodiscard]] std::size_t size() const noexcept {
		return live_size_;
	}

	[[nodiscard]] std::size_t capacity() const noexcept {
		return values_.capacity();
	}

	[[nodiscard]] std::size_t key_capacity() const noexcept {
		return keys_.capacity();
	}

	[[nodiscard]] std::size_t index_capacity() const noexcept {
		return hash_detail::index_capacity(hash_index_);
	}

	[[nodiscard]] std::size_t estimated_storage_bytes() const noexcept {
		return sizeof(*this)
			+ values_.capacity() * sizeof(T_VALUE)
			+ keys_.capacity() * sizeof(T_KEY)
			+ live_.capacity() * sizeof(std::uint8_t)
			+ hash_detail::estimated_index_storage_bytes(hash_index_);
	}

	[[nodiscard]] bool_t is_packed() const noexcept {
		return bool_t{false};
	}

	void clear() noexcept {
		values_.clear();
		keys_.clear();
		live_.clear();
		hash_index_ = std::monostate{};
		live_size_ = 0;
	}

	class entry_view final {
	public:
		entry_view(hash_t *owner, std::uint32_t index) noexcept
			: owner_(owner), index_(index) {}

		[[nodiscard]] decltype(auto) key() const {
			return owner_->key_from_physical_index(index_);
		}

		[[nodiscard]] T_VALUE value_copy() const requires std::copyable<T_VALUE> {
			return owner_->value_from_physical_index(index_);
		}

		[[nodiscard]] T_VALUE &value_ref() const {
			return owner_->value_from_physical_index(index_);
		}

	private:
		hash_t *owner_;
		std::uint32_t index_;
	};

	class const_entry_view final {
	public:
		const_entry_view(const hash_t *owner, std::uint32_t index) noexcept
			: owner_(owner), index_(index) {}

		[[nodiscard]] decltype(auto) key() const {
			return owner_->key_from_physical_index(index_);
		}

		[[nodiscard]] T_VALUE value_copy() const requires std::copyable<T_VALUE> {
			return owner_->value_from_physical_index(index_);
		}

		[[nodiscard]] const T_VALUE &value_ref() const {
			return owner_->value_from_physical_index(index_);
		}

	private:
		const hash_t *owner_;
		std::uint32_t index_;
	};

	class entry_iterator final {
	public:
		using difference_type = std::ptrdiff_t;
		using value_type = entry_view;
		using iterator_category = std::forward_iterator_tag;

		entry_iterator(hash_t *owner, std::uint32_t index) noexcept
			: owner_(owner), index_(index) {
			skip_dead_entries();
		}

		[[nodiscard]] entry_view operator*() const noexcept {
			return entry_view(owner_, index_);
		}

		entry_iterator &operator++() noexcept {
			++index_;
			skip_dead_entries();
			return *this;
		}

		[[nodiscard]] bool operator==(const entry_iterator &other) const noexcept {
			return owner_ == other.owner_ && index_ == other.index_;
		}

		[[nodiscard]] bool operator!=(const entry_iterator &other) const noexcept {
			return !(*this == other);
		}

	private:
		void skip_dead_entries() noexcept {
			while (index_ < owner_->values_.size() && !owner_->is_live_physical_index(index_)) {
				++index_;
			}
		}

		hash_t *owner_;
		std::uint32_t index_;
	};

	class const_entry_iterator final {
	public:
		using difference_type = std::ptrdiff_t;
		using value_type = const_entry_view;
		using iterator_category = std::forward_iterator_tag;

		const_entry_iterator(const hash_t *owner, std::uint32_t index) noexcept
			: owner_(owner), index_(index) {
			skip_dead_entries();
		}

		[[nodiscard]] const_entry_view operator*() const noexcept {
			return const_entry_view(owner_, index_);
		}

		const_entry_iterator &operator++() noexcept {
			++index_;
			skip_dead_entries();
			return *this;
		}

		[[nodiscard]] bool operator==(const const_entry_iterator &other) const noexcept {
			return owner_ == other.owner_ && index_ == other.index_;
		}

		[[nodiscard]] bool operator!=(const const_entry_iterator &other) const noexcept {
			return !(*this == other);
		}

	private:
		void skip_dead_entries() noexcept {
			while (index_ < owner_->values_.size() && !owner_->is_live_physical_index(index_)) {
				++index_;
			}
		}

		const hash_t *owner_;
		std::uint32_t index_;
	};

	[[nodiscard]] entry_iterator begin_entries() noexcept {
		return entry_iterator(this, 0);
	}

	[[nodiscard]] entry_iterator end_entries() noexcept {
		return entry_iterator(this, static_cast<std::uint32_t>(values_.size()));
	}

	[[nodiscard]] const_entry_iterator begin_entries() const noexcept {
		return const_entry_iterator(this, 0);
	}

	[[nodiscard]] const_entry_iterator end_entries() const noexcept {
		return const_entry_iterator(this, static_cast<std::uint32_t>(values_.size()));
	}

	[[nodiscard]] T_KEY append(const T_VALUE &value) requires std::copyable<T_VALUE> {
		if constexpr (detail::is_int_t_v<T_KEY>) {
			const auto key = next_append_key();
			insert_or_assign_key(key, T_VALUE{value});
			return key;
		}
		throw std::runtime_error("hash_t::append requires integer keys");
	}

	[[nodiscard]] T_KEY append(T_VALUE &&value) {
		if constexpr (detail::is_int_t_v<T_KEY>) {
			const auto key = next_append_key();
			insert_or_assign_key(key, std::move(value));
			return key;
		}
		throw std::runtime_error("hash_t::append requires integer keys");
	}

	hash_t &set(const T_KEY &key, const T_VALUE &value) requires(std::copyable<T_KEY> && std::copyable<T_VALUE>) {
		insert_or_assign_key(key, T_VALUE{value});
		return *this;
	}

	hash_t &set(T_KEY &&key, const T_VALUE &value) requires std::copyable<T_VALUE> {
		insert_or_assign_key(std::move(key), T_VALUE{value});
		return *this;
	}

	hash_t &set(const T_KEY &key, T_VALUE &&value) requires std::copyable<T_KEY> {
		insert_or_assign_key(key, std::move(value));
		return *this;
	}

	hash_t &set(T_KEY &&key, T_VALUE &&value) {
		insert_or_assign_key(std::move(key), std::move(value));
		return *this;
	}

	[[nodiscard]] bool_t has(const T_KEY &key) const {
		return bool_t{find_key(key).first};
	}

	[[nodiscard]] nullable<T_VALUE> find(const T_KEY &key) const requires std::copyable<T_VALUE> {
		auto [found, ptr] = find_key(key);
		if (!found) {
			return nullable<T_VALUE>(nullopt);
		}
		nullable<T_VALUE> result;
		result.native_value() = *ptr;
		return result;
	}

	template <typename U = T_VALUE>
	[[nodiscard]] U try_ref(const T_KEY &key) const
		requires(detail::is_shared_p_v<U> && std::copyable<U>)
	{
		return at(key);
	}

	template <typename U = T_VALUE>
	[[nodiscard]] U try_ref(const T_KEY &) const
		requires((!detail::is_shared_p_v<U>) && std::copyable<U>)
	{
		throw std::runtime_error("hash_t::try_ref is supported only for shared_p<T> values in the current safe subset");
	}

	T_VALUE &at(const T_KEY &key) {
		auto [found, ptr] = find_key(key);
		if (!found) throw std::out_of_range("hash_t::at: not found");
		return *ptr;
	}

	const T_VALUE &at(const T_KEY &key) const {
		auto [found, ptr] = find_key(key);
		if (!found) throw std::out_of_range("hash_t::at const: not found");
		return *ptr;
	}

	[[nodiscard]] bool remove(const T_KEY &key) {
		return erase_key(key);
	}

	T_VALUE &operator[](const T_KEY &key) requires std::copyable<T_KEY> {
		auto [found, ptr] = find_key(key);
		if (!found) {
			insert_or_assign_key(key, T_VALUE{});
			return *find_key(key).second;
		}
		return *ptr;
	}

	T_VALUE &operator[](T_KEY &&key) {
		auto [found, ptr] = find_key(key);
		if (!found) {
			insert_or_assign_key(std::move(key), T_VALUE{});
			return value_from_physical_index(static_cast<std::uint32_t>(values_.size() - 1));
		}
		return *ptr;
	}

	const T_VALUE &operator[](const T_KEY &key) const {
		auto [found, ptr] = find_key(key);
		if (!found) {
			static const T_VALUE null_value{};
			return null_value;
		}
		return *ptr;
	}

	template <typename Fn>
	void debug_visit_entries(Fn &&fn) const {
		for (std::uint32_t i = 0; i < keys_.size(); ++i) {
			if (!live_[i]) continue;
			fn(keys_[i], values_[i]);
		}
	}
};

template <>
class hash_t<mixed_t, mixed_t> final {
private:
	using key_storage_t = std::uint32_t;
	using native_values_t = std::vector<mixed_t>;
	using native_keys_t = std::vector<key_storage_t>;

	static constexpr std::uint32_t TOMBSTONE_KEY = 0xFFFFFFFFu;

	native_values_t values_;
	std::variant<std::monostate, native_keys_t> keys_;
	hash_detail::hash_index_variant_t hash_index_;

	void wake_up_associative_mode() {
		const auto count = static_cast<std::uint32_t>(values_.size());
		keys_.template emplace<native_keys_t>();
		auto &keys = std::get<native_keys_t>(keys_);
		keys.reserve(count > 0 ? count : 4);
		for (std::uint32_t i = 0; i < count; ++i) {
			keys.push_back(i);
		}
		rehash(count > 0 ? count * 2 : 4);
	}

	void check_and_rehash() {
		if (std::holds_alternative<std::monostate>(hash_index_)) return;
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
		if (sz + del >= (cap * 3) / 4) {
			const auto next_capacity = (sz >= cap / 2) ? cap * 2 : cap;
			rehash(next_capacity == 0 ? 4 : next_capacity);
		}
	}

	void rehash(std::uint32_t next_capacity) {
		hash_detail::hash_index_variant_t next_index;
		if (next_capacity <= 256) {
			next_index.template emplace<hash_detail::flat_hash_index_t<std::uint8_t>>();
		} else if (next_capacity <= 65536) {
			next_index.template emplace<hash_detail::flat_hash_index_t<std::uint16_t>>();
		} else {
			next_index.template emplace<hash_detail::flat_hash_index_t<std::uint32_t>>();
		}

		auto &keys = std::get<native_keys_t>(keys_);
		std::visit([&](auto &idx) {
			using I = std::decay_t<decltype(idx)>;
			if constexpr (!std::is_same_v<I, std::monostate>) {
				idx.capacity_ = next_capacity;
				idx.size_ = 0;
				idx.deleted_count_ = 0;
				idx.ctrl_bytes_.assign(next_capacity, static_cast<std::uint8_t>(hash_detail::ctrl_state_t::empty));
				idx.buckets_.resize(next_capacity);
				for (std::uint32_t i = 0; i < values_.size(); ++i) {
					if (keys[i] == TOMBSTONE_KEY) continue;
					const auto hash = hash_detail::mix64(keys[i]);
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

	void add_to_index(std::uint32_t key, std::uint32_t physical_index) {
		check_and_rehash();
		std::visit([&](auto &idx) {
			using I = std::decay_t<decltype(idx)>;
			if constexpr (!std::is_same_v<I, std::monostate>) {
				const auto hash = hash_detail::mix64(key);
				auto bucket = static_cast<std::uint32_t>(hash % idx.capacity_);
				const auto fingerprint = static_cast<std::uint8_t>(hash & 0x7F);
				while (idx.ctrl_bytes_[bucket] < 128
					&& idx.ctrl_bytes_[bucket] != static_cast<std::uint8_t>(hash_detail::ctrl_state_t::deleted)) {
					bucket = (bucket + 1) % idx.capacity_;
				}
				idx.ctrl_bytes_[bucket] = fingerprint;
				idx.buckets_[bucket] = static_cast<typename decltype(idx.buckets_)::value_type>(physical_index);
				idx.size_++;
			}
		}, hash_index_);
	}

	bool erase_packed(std::uint32_t index) {
		if (index >= values_.size()) return false;
		wake_up_associative_mode();
		return erase_int(index);
	}

	void insert_or_assign_int(std::uint32_t key, mixed_t value) {
		if (std::holds_alternative<std::monostate>(keys_)) {
			if (!hash_detail::dyn_keys::is_string(key)) {
				if (key < values_.size()) {
					values_[key] = std::move(value);
					return;
				}
				if (key == values_.size()) {
					values_.push_back(std::move(value));
					return;
				}
			}
			wake_up_associative_mode();
		}
		if (auto [found, ptr] = find_int(key); found) {
			*ptr = std::move(value);
			return;
		}
		auto &keys = std::get<native_keys_t>(keys_);
		const auto physical_index = static_cast<std::uint32_t>(values_.size());
		keys.push_back(key);
		values_.push_back(std::move(value));
		add_to_index(key, physical_index);
	}

	std::pair<bool, mixed_t *> find_int(std::uint32_t key) {
		if (std::holds_alternative<std::monostate>(keys_)) {
			if (!hash_detail::dyn_keys::is_string(key) && key < values_.size()) {
				return {true, &values_[key]};
			}
			return {false, nullptr};
		}
		auto &keys = std::get<native_keys_t>(keys_);
		std::pair<bool, mixed_t *> result{false, nullptr};
		std::visit([&](auto &idx) {
			using I = std::decay_t<decltype(idx)>;
			if constexpr (!std::is_same_v<I, std::monostate>) {
				if (!idx.capacity_) return;
				const auto hash = hash_detail::mix64(key);
				auto bucket = static_cast<std::uint32_t>(hash % idx.capacity_);
				const auto fingerprint = static_cast<std::uint8_t>(hash & 0x7F);
				while (idx.ctrl_bytes_[bucket] != static_cast<std::uint8_t>(hash_detail::ctrl_state_t::empty)) {
					if (idx.ctrl_bytes_[bucket] == fingerprint) {
						const auto physical_index = static_cast<std::uint32_t>(idx.buckets_[bucket]);
						if (keys[physical_index] == key) {
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

	std::pair<bool, const mixed_t *> find_int(std::uint32_t key) const {
		if (std::holds_alternative<std::monostate>(keys_)) {
			if (!hash_detail::dyn_keys::is_string(key) && key < values_.size()) {
				return {true, &values_[key]};
			}
			return {false, nullptr};
		}
		const auto &keys = std::get<native_keys_t>(keys_);
		std::pair<bool, const mixed_t *> result{false, nullptr};
		std::visit([&](const auto &idx) {
			using I = std::decay_t<decltype(idx)>;
			if constexpr (!std::is_same_v<I, std::monostate>) {
				if (!idx.capacity_) return;
				const auto hash = hash_detail::mix64(key);
				auto bucket = static_cast<std::uint32_t>(hash % idx.capacity_);
				const auto fingerprint = static_cast<std::uint8_t>(hash & 0x7F);
				while (idx.ctrl_bytes_[bucket] != static_cast<std::uint8_t>(hash_detail::ctrl_state_t::empty)) {
					if (idx.ctrl_bytes_[bucket] == fingerprint) {
						const auto physical_index = static_cast<std::uint32_t>(idx.buckets_[bucket]);
						if (keys[physical_index] == key) {
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

	bool erase_int(std::uint32_t key) {
		if (std::holds_alternative<std::monostate>(keys_)) return erase_packed(key);
		bool found = false;
		auto &keys = std::get<native_keys_t>(keys_);
		std::visit([&](auto &idx) {
			using I = std::decay_t<decltype(idx)>;
			if constexpr (!std::is_same_v<I, std::monostate>) {
				if (!idx.capacity_) return;
				const auto hash = hash_detail::mix64(key);
				auto bucket = static_cast<std::uint32_t>(hash % idx.capacity_);
				const auto fingerprint = static_cast<std::uint8_t>(hash & 0x7F);
				while (idx.ctrl_bytes_[bucket] != static_cast<std::uint8_t>(hash_detail::ctrl_state_t::empty)) {
					if (idx.ctrl_bytes_[bucket] == fingerprint) {
						const auto physical_index = static_cast<std::uint32_t>(idx.buckets_[bucket]);
						if (keys[physical_index] == key) {
							idx.ctrl_bytes_[bucket] = static_cast<std::uint8_t>(hash_detail::ctrl_state_t::deleted);
							idx.size_--;
							idx.deleted_count_++;
							keys[physical_index] = TOMBSTONE_KEY;
							values_[physical_index] = mixed_t{};
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

	[[nodiscard]] std::uint32_t next_append_key() const {
		if (std::holds_alternative<std::monostate>(keys_)) {
			return static_cast<std::uint32_t>(values_.size());
		}
		std::uint32_t max_key = 0;
		bool have_key = false;
		for (const auto key : std::get<native_keys_t>(keys_)) {
			if (key == TOMBSTONE_KEY || hash_detail::dyn_keys::is_string(key)) continue;
			if (!have_key || key > max_key) {
				max_key = key;
				have_key = true;
			}
		}
		return have_key ? (max_key + 1) : 0;
	}

	[[nodiscard]] bool is_live_physical_index(std::uint32_t index) const noexcept {
		if (std::holds_alternative<std::monostate>(keys_)) {
			return index < values_.size();
		}
		const auto &keys = std::get<native_keys_t>(keys_);
		return index < keys.size() && keys[index] != TOMBSTONE_KEY;
	}

	[[nodiscard]] mixed_t key_from_physical_index(std::uint32_t index) const {
		if (std::holds_alternative<std::monostate>(keys_)) {
			return mixed_t(int_t<>{static_cast<std::int64_t>(index)});
		}
		const auto &keys = std::get<native_keys_t>(keys_);
		if (index >= keys.size() || keys[index] == TOMBSTONE_KEY) {
			throw std::out_of_range("hash_t::key_from_physical_index: invalid entry index");
		}
		return hash_detail::dyn_keys::unpack(keys[index]);
	}

	[[nodiscard]] mixed_t &value_from_physical_index(std::uint32_t index) {
		if (!is_live_physical_index(index)) {
			throw std::out_of_range("hash_t::value_from_physical_index: invalid entry index");
		}
		return values_[index];
	}

	[[nodiscard]] const mixed_t &value_from_physical_index(std::uint32_t index) const {
		if (!is_live_physical_index(index)) {
			throw std::out_of_range("hash_t::value_from_physical_index const: invalid entry index");
		}
		return values_[index];
	}

public:
	hash_t()
		: keys_(std::monostate{}), hash_index_(std::monostate{}) {}

	hash_t(const hash_t &other)
		: values_(other.values_), keys_(std::monostate{}), hash_index_(std::monostate{}) {
		if (std::holds_alternative<native_keys_t>(other.keys_)) {
			keys_ = std::get<native_keys_t>(other.keys_);
			const auto count = static_cast<std::uint32_t>(values_.size());
			rehash(count > 0 ? count * 2 : 4);
		}
	}

	hash_t(hash_t &&) noexcept = default;

	hash_t &operator=(const hash_t &other) {
		if (this == &other) return *this;
		hash_t copy(other);
		*this = std::move(copy);
		return *this;
	}

	hash_t &operator=(hash_t &&) noexcept = default;
	~hash_t() = default;

	[[nodiscard]] bool_t empty() const noexcept {
		if (std::holds_alternative<std::monostate>(keys_)) return bool_t{values_.empty()};
		for (const auto key : std::get<native_keys_t>(keys_)) {
			if (key != TOMBSTONE_KEY) return bool_t{false};
		}
		return bool_t{true};
	}

	[[nodiscard]] std::size_t size() const noexcept {
		if (std::holds_alternative<std::monostate>(keys_)) return values_.size();
		std::size_t count = 0;
		for (const auto key : std::get<native_keys_t>(keys_)) {
			if (key != TOMBSTONE_KEY) ++count;
		}
		return count;
	}

	[[nodiscard]] std::size_t capacity() const noexcept {
		return values_.capacity();
	}

	[[nodiscard]] std::size_t key_capacity() const noexcept {
		if (std::holds_alternative<std::monostate>(keys_)) return 0;
		return std::get<native_keys_t>(keys_).capacity();
	}

	[[nodiscard]] std::size_t index_capacity() const noexcept {
		return hash_detail::index_capacity(hash_index_);
	}

	[[nodiscard]] std::size_t estimated_storage_bytes() const noexcept {
		const auto keys_bytes = std::holds_alternative<native_keys_t>(keys_)
			? std::get<native_keys_t>(keys_).capacity() * sizeof(native_keys_t::value_type)
			: std::size_t{0};
		return sizeof(*this)
			+ values_.capacity() * sizeof(mixed_t)
			+ keys_bytes
			+ hash_detail::estimated_index_storage_bytes(hash_index_);
	}

	[[nodiscard]] bool_t is_packed() const noexcept {
		return bool_t{std::holds_alternative<std::monostate>(keys_)};
	}

	void clear() noexcept {
		values_.clear();
		keys_ = std::monostate{};
		hash_index_ = std::monostate{};
	}

	class entry_view final {
	public:
		entry_view(hash_t *owner, std::uint32_t index) noexcept
			: owner_(owner), index_(index) {}

		[[nodiscard]] mixed_t key() const {
			return owner_->key_from_physical_index(index_);
		}

		[[nodiscard]] mixed_t value_copy() const {
			return owner_->value_from_physical_index(index_);
		}

		[[nodiscard]] mixed_t &value_ref() const {
			return owner_->value_from_physical_index(index_);
		}

	private:
		hash_t *owner_;
		std::uint32_t index_;
	};

	class const_entry_view final {
	public:
		const_entry_view(const hash_t *owner, std::uint32_t index) noexcept
			: owner_(owner), index_(index) {}

		[[nodiscard]] mixed_t key() const {
			return owner_->key_from_physical_index(index_);
		}

		[[nodiscard]] mixed_t value_copy() const {
			return owner_->value_from_physical_index(index_);
		}

		[[nodiscard]] const mixed_t &value_ref() const {
			return owner_->value_from_physical_index(index_);
		}

	private:
		const hash_t *owner_;
		std::uint32_t index_;
	};

	class entry_iterator final {
	public:
		using difference_type = std::ptrdiff_t;
		using value_type = entry_view;
		using iterator_category = std::forward_iterator_tag;

		entry_iterator(hash_t *owner, std::uint32_t index) noexcept
			: owner_(owner), index_(index) {
			skip_dead_entries();
		}

		[[nodiscard]] entry_view operator*() const noexcept {
			return entry_view(owner_, index_);
		}

		entry_iterator &operator++() noexcept {
			++index_;
			skip_dead_entries();
			return *this;
		}

		[[nodiscard]] bool operator==(const entry_iterator &other) const noexcept {
			return owner_ == other.owner_ && index_ == other.index_;
		}

		[[nodiscard]] bool operator!=(const entry_iterator &other) const noexcept {
			return !(*this == other);
		}

	private:
		void skip_dead_entries() noexcept {
			while (index_ < owner_->values_.size() && !owner_->is_live_physical_index(index_)) {
				++index_;
			}
		}

		hash_t *owner_;
		std::uint32_t index_;
	};

	class const_entry_iterator final {
	public:
		using difference_type = std::ptrdiff_t;
		using value_type = const_entry_view;
		using iterator_category = std::forward_iterator_tag;

		const_entry_iterator(const hash_t *owner, std::uint32_t index) noexcept
			: owner_(owner), index_(index) {
			skip_dead_entries();
		}

		[[nodiscard]] const_entry_view operator*() const noexcept {
			return const_entry_view(owner_, index_);
		}

		const_entry_iterator &operator++() noexcept {
			++index_;
			skip_dead_entries();
			return *this;
		}

		[[nodiscard]] bool operator==(const const_entry_iterator &other) const noexcept {
			return owner_ == other.owner_ && index_ == other.index_;
		}

		[[nodiscard]] bool operator!=(const const_entry_iterator &other) const noexcept {
			return !(*this == other);
		}

	private:
		void skip_dead_entries() noexcept {
			while (index_ < owner_->values_.size() && !owner_->is_live_physical_index(index_)) {
				++index_;
			}
		}

		const hash_t *owner_;
		std::uint32_t index_;
	};

	[[nodiscard]] entry_iterator begin_entries() noexcept {
		return entry_iterator(this, 0);
	}

	[[nodiscard]] entry_iterator end_entries() noexcept {
		return entry_iterator(this, static_cast<std::uint32_t>(values_.size()));
	}

	[[nodiscard]] const_entry_iterator begin_entries() const noexcept {
		return const_entry_iterator(this, 0);
	}

	[[nodiscard]] const_entry_iterator end_entries() const noexcept {
		return const_entry_iterator(this, static_cast<std::uint32_t>(values_.size()));
	}

	[[nodiscard]] int_t<> append(const mixed_t &value) {
		const auto key = next_append_key();
		insert_or_assign_int(key, mixed_t{value});
		return int_t<>{static_cast<std::int64_t>(key)};
	}

	[[nodiscard]] int_t<> append(mixed_t &&value) {
		const auto key = next_append_key();
		insert_or_assign_int(key, std::move(value));
		return int_t<>{static_cast<std::int64_t>(key)};
	}

	hash_t &set(const int_t<> &key, const mixed_t &value) {
		insert_or_assign_int(hash_detail::dyn_keys::pack(key), mixed_t{value});
		return *this;
	}

	hash_t &set(const string_t &key, const mixed_t &value) {
		insert_or_assign_int(hash_detail::dyn_keys::pack(key), mixed_t{value});
		return *this;
	}

	hash_t &set(const mixed_t &key, const mixed_t &value) {
		insert_or_assign_int(hash_detail::dyn_keys::pack(key), mixed_t{value});
		return *this;
	}

	hash_t &set(const int_t<> &key, mixed_t &&value) {
		insert_or_assign_int(hash_detail::dyn_keys::pack(key), std::move(value));
		return *this;
	}

	hash_t &set(const string_t &key, mixed_t &&value) {
		insert_or_assign_int(hash_detail::dyn_keys::pack(key), std::move(value));
		return *this;
	}

	hash_t &set(const mixed_t &key, mixed_t &&value) {
		insert_or_assign_int(hash_detail::dyn_keys::pack(key), std::move(value));
		return *this;
	}

	[[nodiscard]] bool_t has(const int_t<> &key) const {
		return bool_t{find_int(hash_detail::dyn_keys::pack(key)).first};
	}

	[[nodiscard]] bool_t has(const string_t &key) const {
		return bool_t{find_int(hash_detail::dyn_keys::pack(key)).first};
	}

	[[nodiscard]] bool_t has(const mixed_t &key) const {
		return bool_t{find_int(hash_detail::dyn_keys::pack(key)).first};
	}

	[[nodiscard]] nullable<mixed_t> find(const int_t<> &key) const {
		auto [found, ptr] = find_int(hash_detail::dyn_keys::pack(key));
		if (!found) return nullable<mixed_t>(nullopt);
		nullable<mixed_t> result;
		result.native_value() = *ptr;
		return result;
	}

	[[nodiscard]] nullable<mixed_t> find(const string_t &key) const {
		auto [found, ptr] = find_int(hash_detail::dyn_keys::pack(key));
		if (!found) return nullable<mixed_t>(nullopt);
		nullable<mixed_t> result;
		result.native_value() = *ptr;
		return result;
	}

	[[nodiscard]] nullable<mixed_t> find(const mixed_t &key) const {
		auto [found, ptr] = find_int(hash_detail::dyn_keys::pack(key));
		if (!found) return nullable<mixed_t>(nullopt);
		nullable<mixed_t> result;
		result.native_value() = *ptr;
		return result;
	}

	[[nodiscard]] mixed_t _find_val(const int_t<> &key) const;
	[[nodiscard]] mixed_t _find_val(const string_t &key) const;
	[[nodiscard]] mixed_t _find_val(const mixed_t &key) const;

	template <typename U = mixed_t>
	[[nodiscard]] U try_ref(const int_t<> &key) const
		requires(detail::is_shared_p_v<U> && std::copyable<U>)
	{
		return at(key);
	}

	template <typename U = mixed_t>
	[[nodiscard]] U try_ref(const string_t &key) const
		requires(detail::is_shared_p_v<U> && std::copyable<U>)
	{
		return at(key);
	}

	template <typename U = mixed_t>
	[[nodiscard]] U try_ref(const mixed_t &key) const
		requires(detail::is_shared_p_v<U> && std::copyable<U>)
	{
		return at(key);
	}

	template <typename U = mixed_t>
	[[nodiscard]] U try_ref(const int_t<> &) const
		requires((!detail::is_shared_p_v<U>) && std::copyable<U>)
	{
		throw std::runtime_error("hash_t::try_ref is supported only for shared_p<T> values in the current safe subset");
	}

	template <typename U = mixed_t>
	[[nodiscard]] U try_ref(const string_t &) const
		requires((!detail::is_shared_p_v<U>) && std::copyable<U>)
	{
		throw std::runtime_error("hash_t::try_ref is supported only for shared_p<T> values in the current safe subset");
	}

	template <typename U = mixed_t>
	[[nodiscard]] U try_ref(const mixed_t &) const
		requires((!detail::is_shared_p_v<U>) && std::copyable<U>)
	{
		throw std::runtime_error("hash_t::try_ref is supported only for shared_p<T> values in the current safe subset");
	}

	mixed_t &at(const int_t<> &key) {
		auto [found, ptr] = find_int(hash_detail::dyn_keys::pack(key));
		if (!found) throw std::out_of_range("hash_t::at(int_t<>): not found");
		return *ptr;
	}

	mixed_t &at(const string_t &key) {
		auto [found, ptr] = find_int(hash_detail::dyn_keys::pack(key));
		if (!found) throw std::out_of_range("hash_t::at(string_t): not found");
		return *ptr;
	}

	mixed_t &at(const mixed_t &key) {
		auto [found, ptr] = find_int(hash_detail::dyn_keys::pack(key));
		if (!found) throw std::out_of_range("hash_t::at(mixed_t): not found");
		return *ptr;
	}

	const mixed_t &at(const int_t<> &key) const {
		auto [found, ptr] = find_int(hash_detail::dyn_keys::pack(key));
		if (!found) throw std::out_of_range("hash_t::at(int_t<>) const: not found");
		return *ptr;
	}

	const mixed_t &at(const string_t &key) const {
		auto [found, ptr] = find_int(hash_detail::dyn_keys::pack(key));
		if (!found) throw std::out_of_range("hash_t::at(string_t) const: not found");
		return *ptr;
	}

	const mixed_t &at(const mixed_t &key) const {
		auto [found, ptr] = find_int(hash_detail::dyn_keys::pack(key));
		if (!found) throw std::out_of_range("hash_t::at(mixed_t) const: not found");
		return *ptr;
	}

	[[nodiscard]] bool remove(const int_t<> &key) {
		return erase_int(hash_detail::dyn_keys::pack(key));
	}

	[[nodiscard]] bool remove(const string_t &key) {
		return erase_int(hash_detail::dyn_keys::pack(key));
	}

	[[nodiscard]] bool remove(const mixed_t &key) {
		return erase_int(hash_detail::dyn_keys::pack(key));
	}

	mixed_t &operator[](const int_t<> &key) {
		const auto packed = hash_detail::dyn_keys::pack(key);
		auto [found, ptr] = find_int(packed);
		if (!found) {
			insert_or_assign_int(packed, mixed_t{});
			return *find_int(packed).second;
		}
		return *ptr;
	}

	mixed_t &operator[](const string_t &key) {
		const auto packed = hash_detail::dyn_keys::pack(key);
		auto [found, ptr] = find_int(packed);
		if (!found) {
			insert_or_assign_int(packed, mixed_t{});
			return *find_int(packed).second;
		}
		return *ptr;
	}

	mixed_t &operator[](const mixed_t &key) {
		const auto packed = hash_detail::dyn_keys::pack(key);
		auto [found, ptr] = find_int(packed);
		if (!found) {
			insert_or_assign_int(packed, mixed_t{});
			return *find_int(packed).second;
		}
		return *ptr;
	}

	const mixed_t &operator[](const int_t<> &key) const {
		auto [found, ptr] = find_int(hash_detail::dyn_keys::pack(key));
		if (!found) {
			static const mixed_t null_value{};
			return null_value;
		}
		return *ptr;
	}

	const mixed_t &operator[](const string_t &key) const {
		auto [found, ptr] = find_int(hash_detail::dyn_keys::pack(key));
		if (!found) {
			static const mixed_t null_value{};
			return null_value;
		}
		return *ptr;
	}

	const mixed_t &operator[](const mixed_t &key) const {
		auto [found, ptr] = find_int(hash_detail::dyn_keys::pack(key));
		if (!found) {
			static const mixed_t null_value{};
			return null_value;
		}
		return *ptr;
	}

	template <typename Fn>
	void debug_visit_entries(Fn &&fn) const {
		if (std::holds_alternative<std::monostate>(keys_)) {
			for (std::uint32_t i = 0; i < values_.size(); ++i) {
				fn(int_t<>{static_cast<std::int64_t>(i)}, values_[i]);
			}
			return;
		}
		const auto &keys = std::get<native_keys_t>(keys_);
		for (std::uint32_t i = 0; i < keys.size(); ++i) {
			const auto key = keys[i];
			if (key == TOMBSTONE_KEY) continue;
			if (hash_detail::dyn_keys::is_string(key)) {
				fn(global_string_pool::instance().resolve(key), values_[i]);
				continue;
			}
			fn(int_t<>{static_cast<std::int64_t>(key)}, values_[i]);
		}
	}
};

inline std::uint32_t hash_detail::dyn_keys::pack(const mixed_t &key) {
	switch (key.kind()) {
		case mixed_t::kind_t::int_v:
			return pack(key.int_value());
		case mixed_t::kind_t::string_v:
			return pack(*key.string_if());
		default:
			break;
	}
	throw std::runtime_error("hash_t<mixed_t>: key must be int or string");
}

inline mixed_t hash_detail::dyn_keys::unpack(std::uint32_t key) {
	if (is_string(key)) {
		return mixed_t(global_string_pool::instance().resolve(key));
	}
	return mixed_t(int_t<>{static_cast<std::int64_t>(key)});
}

struct table_build_item_t final {
	std::variant<std::monostate, int_t<>, string_t> key;
	mixed_t value;
};

template <typename V>
[[nodiscard]] inline table_build_item_t table_item_(V &&value) {
	return {std::monostate{}, mixed_t(std::forward<V>(value))};
}

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(const int_t<> &key, V &&value) {
	return {key, mixed_t(std::forward<V>(value))};
}

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(int key, V &&value) {
	return table_kv_(int_t<>{static_cast<std::int64_t>(key)}, std::forward<V>(value));
}

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(const string_t &key, V &&value) {
	return {key, mixed_t(std::forward<V>(value))};
}

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(const char *key, V &&value) {
	return table_kv_(string_t{key}, std::forward<V>(value));
}

inline void table_add_item_(hash_t<mixed_t> &table, const table_build_item_t &item) {
	if (std::holds_alternative<std::monostate>(item.key)) {
		(void)table.append(item.value);
		return;
	}
	if (std::holds_alternative<int_t<>>(item.key)) {
		table.set(std::get<int_t<>>(item.key), item.value);
		return;
	}
	table.set(std::get<string_t>(item.key), item.value);
}

template <typename... Items>
[[nodiscard]] inline unique_p<hash_t<mixed_t>> table_(Items &&...items) {
	auto table = unique_p<hash_t<mixed_t>>(std::make_unique<hash_t<mixed_t>>());
	(table_add_item_(*table, std::forward<Items>(items)), ...);
	return table;
}

template <typename... Items>
[[nodiscard]] inline shared_p<hash_t<mixed_t>> shared_table_(Items &&...items) {
	auto table = shared_p<hash_t<mixed_t>>(std::make_shared<hash_t<mixed_t>>());
	(table_add_item_(*table, std::forward<Items>(items)), ...);
	return table;
}

template <typename... Items>
[[nodiscard]] inline dynamic_init_t dynamic_(Items &&...items) {
	return dynamic_box(shared_table_(std::forward<Items>(items)...));
}

} // namespace scpp
