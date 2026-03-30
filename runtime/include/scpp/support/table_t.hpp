#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/vector_t.hpp"

#include <any>
#include <cstdint>
#include <memory>
#include <stdexcept>
#include <unordered_map>
#include <utility>
#include <variant>
#include <vector>

namespace scpp {

class table_t;

// Ordered key/value runtime container value used by table_t.
//
// Notes:
// - public semantics stay in scpp terms
// - storage keeps the donor mem_container tagged-union layout style
// - copy support is explicit because lookup returns value objects, not references
class value_t final {
public:
	// Runtime tag for the active stored payload.
	enum class kind_t : std::uint8_t {
		null_v = 0,
		bool_v,
		int_v,
		float_v,
		string_v,
		table_v,
		shared_table_v,
		weak_table_v,
		any_v
	};

private:
	// Internal tagged-union storage reused from the donor design.
	kind_t type_;

	union {
		bool bool_value_;
		std::int64_t int_value_;
		double float_value_;
		std::unique_ptr<string_t> string_value_;
		std::unique_ptr<table_t> table_value_;
		std::shared_ptr<table_t> shared_table_value_;
		std::weak_ptr<table_t> weak_table_value_;
		std::any any_value_;
	};

	// Destroys the currently active payload branch.
	void destroy() noexcept;

	// Moves one active payload from another value into this object.
	void move_construct(value_t &&other) noexcept;

public:
	// Stable zero-state constructor: stores runtime null.
	value_t() noexcept;
	value_t(null_t) noexcept;

	// Value constructors keep public I/O in scpp terms.
	value_t(const bool_t &value) noexcept;
	value_t(const int_t &value) noexcept;
	value_t(const float_t &value) noexcept;
	value_t(const string_t &value);
	value_t(const char *value);

	// Native convenience constructors used by assignment-capable access such as table["x"] = 12.
	value_t(bool value) noexcept;
	value_t(std::int64_t value) noexcept;
	value_t(double value) noexcept;

	// Table/object-like constructors remain available for future runtime growth.
	value_t(std::unique_ptr<table_t> value) noexcept;
	value_t(std::shared_ptr<table_t> value) noexcept;
	value_t(std::weak_ptr<table_t> value) noexcept;
	value_t(std::any value);

	// Rule-of-five support is explicit because the type owns a manual tagged union.
	~value_t();

	value_t(const value_t &other);
	value_t(value_t &&other) noexcept;
	value_t &operator=(const value_t &other);
	value_t &operator=(value_t &&other) noexcept;

	// Convenience assignments keep generated/runtime code short at the call site.
	value_t &operator=(null_t) noexcept;
	value_t &operator=(const bool_t &value) noexcept;
	value_t &operator=(const int_t &value) noexcept;
	value_t &operator=(const float_t &value) noexcept;
	value_t &operator=(const string_t &value);
	value_t &operator=(const char *value);
	value_t &operator=(bool value) noexcept;
	value_t &operator=(std::int64_t value) noexcept;
	value_t &operator=(double value) noexcept;

	// Deep-copy helper used by table_t copy and non-reference lookup results.
	[[nodiscard]] value_t clone() const;

	// Minimal public inspection helpers.
	[[nodiscard]] kind_t kind() const noexcept;
	[[nodiscard]] bool_t is_null() const noexcept;

	// Scalar access helpers used by runtime services such as PHP echo/string coercion.
	[[nodiscard]] bool_t bool_value() const;
	[[nodiscard]] int_t int_value() const;
	[[nodiscard]] float_t float_value() const;
	[[nodiscard]] const string_t *string_if() const noexcept;

	[[nodiscard]] table_t *table_if() noexcept;
	[[nodiscard]] const table_t *table_if() const noexcept;
};

// Public non-throwing lookup result used by table_t::find().
// It reuses the existing runtime optional wrapper while keeping found-null distinct
// from not-found by storing value_t(null) as a present value.
using maybe_value_t = nullable<value_t>;

// Generic empty-result helper used by generated/runtime code.
[[nodiscard]] inline bool_t is_nullopt(const maybe_value_t &value) noexcept {
	return bool_t(!value.has_value().native_value());
}

// Semantic alias used specifically for table lookups.
[[nodiscard]] inline bool_t was_found(const maybe_value_t &value) noexcept {
	return value.has_value();
}

// Ordered key/value runtime container used as the public Simple C++ array runtime type.
//
// Notes:
// - public semantics follow runtime/specs/table_t.md
// - implementation is adapted from mem_container and keeps the packed + associative storage model
// - public methods expose scpp types; storage stays internal
class table_t final {
private:
	using key_storage_t = std::uint32_t;
	using native_values_t = std::vector<value_t>;
	using native_keys_t = std::vector<key_storage_t>;

	// Internal sentinel used for erased associative slots.
	static constexpr std::uint32_t TOMBSTONE_KEY = 0xFFFFFFFFu;

	// Internal flat-hash control states reused from the donor structure.
	enum class ctrl_state_t : std::uint8_t {
		empty = 0b10000000,
		deleted = 0b11111110,
		sentinel = 0b11111111
	};

	template <typename index_type>
	struct flat_hash_index_t {
		std::vector<std::uint8_t> ctrl_bytes_;
		std::vector<index_type> buckets_;
		std::uint32_t capacity_ = 0;
		std::uint32_t size_ = 0;
		std::uint32_t deleted_count_ = 0;
	};

	// Internal string interning pool used to preserve the donor compact-key strategy.
	class global_string_pool_t {
	public:
		static constexpr std::uint32_t string_key_flag = 0x80000000u;
		static constexpr std::uint32_t string_id_mask = 0x7FFFFFFFu;

		static global_string_pool_t &instance();

		global_string_pool_t(const global_string_pool_t &) = delete;
		global_string_pool_t &operator=(const global_string_pool_t &) = delete;

		std::uint32_t intern(const string_t &value);
		const std::string &get_string_native(std::uint32_t tagged_id) const;
		static bool is_string_id(std::uint32_t tagged_id);

	private:
		global_string_pool_t() = default;

		std::unordered_map<std::string, std::uint32_t> lookup_;
		std::vector<const std::string *> strings_;
	};

	// Physical value storage reused from the donor structure.
	native_values_t values_;

	// Packed mode: monostate.
	// Associative mode: explicit logical key for each physical slot.
	std::variant<std::monostate, native_keys_t> keys_;

	// SwissTable-like index reused from the donor structure.
	std::variant<
		std::monostate,
		flat_hash_index_t<std::uint8_t>,
		flat_hash_index_t<std::uint16_t>,
		flat_hash_index_t<std::uint32_t>
	> hash_index_;

	// Promotes packed storage into associative mode while preserving current values.
	void wake_up_associative_mode();

	// Adds one logical key -> physical slot mapping into the active hash index.
	void add_to_index(std::uint32_t key, std::uint32_t physical_index);

	// Deletes one packed-mode physical slot.
	bool erase_packed(std::uint32_t index);

	// Rehash helpers keep the donor flat-hash structure healthy after growth/deletes.
	void check_and_rehash();
	void rehash(std::uint32_t new_capacity);

	// Core integer-key operations shared by int and string public overloads.
	void insert_or_assign_int(std::uint32_t key, value_t value);
	std::pair<bool, value_t *> find_int(std::uint32_t key);
	std::pair<bool, const value_t *> find_int(std::uint32_t key) const;
	bool erase_int(std::uint32_t key);

	// Implements append-by-max-int-key-plus-one.
	[[nodiscard]] std::uint32_t next_append_key() const;

	// Shared string-key conversion helper.
	[[nodiscard]] static std::uint32_t make_string_key(const string_t &key);

	// Donor hash helper preserved for index probing.
	[[nodiscard]] static std::uint64_t fast_int_hash(std::uint32_t value) {
		std::uint64_t h = value;
		h ^= h >> 16;
		h *= 0x85ebca6bull;
		h ^= h >> 13;
		h *= 0xc2b2ae35ull;
		h ^= h >> 16;
		return h;
	}

public:
	// Default state is packed-empty.
	table_t();

	// Explicit copy/move are defined because storage contains custom value_t payloads.
	table_t(const table_t &other);
	table_t(table_t &&other) noexcept = default;
	table_t &operator=(const table_t &other);
	table_t &operator=(table_t &&other) noexcept = default;
	~table_t() = default;

	// Capacity/state inspection.
	[[nodiscard]] bool_t empty() const noexcept;
	[[nodiscard]] std::size_t size() const noexcept;
	[[nodiscard]] bool_t is_packed() const noexcept;
	void clear() noexcept;

	// Append semantics follow the table_t contract: max-int-key + 1.
	[[nodiscard]] int_t append(const value_t &value);

	// Explicit keyed write API. Existing keys are overwritten.
	table_t &set(const int_t &key, const value_t &value);
	table_t &set(const string_t &key, const value_t &value);

	// Presence checks never mutate the container.
	[[nodiscard]] bool_t has(const int_t &key) const;
	[[nodiscard]] bool_t has(const string_t &key) const;

	// Non-inserting lookup for generator/runtime read paths.
	[[nodiscard]] maybe_value_t find(const int_t &key) const;
	[[nodiscard]] maybe_value_t find(const string_t &key) const;

	// Checked access with industry-standard at() semantics.
	value_t &at(const int_t &key);
	value_t &at(const string_t &key);
	const value_t &at(const int_t &key) const;
	const value_t &at(const string_t &key) const;

	// Mutating/inserting access used only when creation is intended.
	value_t &operator[](const int_t &key);
	value_t &operator[](const string_t &key);

	// Removal follows the documented bool return contract.
	[[nodiscard]] bool remove(const int_t &key);
	[[nodiscard]] bool remove(const string_t &key);
};


// Builder item used by generated table literals.
// It keeps keyed vs append-style items explicit so generator output stays deterministic.
struct table_build_item_t final {
	std::variant<std::monostate, int_t, string_t> key;
	value_t value;
};

// Converts supported runtime/public inputs into table_t payload values.
[[nodiscard]] inline value_t table_value_(const value_t &value) {
	return value.clone();
}

[[nodiscard]] inline value_t table_value_(value_t &&value) {
	return std::move(value);
}

[[nodiscard]] inline value_t table_value_(const table_t &value) {
	return value_t(std::make_unique<table_t>(value));
}

[[nodiscard]] inline value_t table_value_(table_t &&value) {
	return value_t(std::make_unique<table_t>(std::move(value)));
}

[[nodiscard]] inline value_t table_value_(const null_t &value) {
	return value_t(value);
}

[[nodiscard]] inline value_t table_value_(const nullopt_t &value) {
	return value_t(std::any(value));
}

[[nodiscard]] inline value_t table_value_(const bool_t &value) {
	return value_t(value);
}

[[nodiscard]] inline value_t table_value_(const int_t &value) {
	return value_t(value);
}

[[nodiscard]] inline value_t table_value_(const float_t &value) {
	return value_t(value);
}

[[nodiscard]] inline value_t table_value_(const string_t &value) {
	return value_t(value);
}

[[nodiscard]] inline value_t table_value_(const char *value) {
	return value_t(value);
}

[[nodiscard]] inline value_t table_value_(bool value) {
	return value_t(value);
}

[[nodiscard]] inline value_t table_value_(int value) {
	return value_t(static_cast<std::int64_t>(value));
}

[[nodiscard]] inline value_t table_value_(long value) {
	return value_t(static_cast<std::int64_t>(value));
}

[[nodiscard]] inline value_t table_value_(long long value) {
	return value_t(static_cast<std::int64_t>(value));
}

[[nodiscard]] inline value_t table_value_(double value) {
	return value_t(value);
}

template <typename T>
[[nodiscard]] inline value_t table_value_(const vector_t<T> &value) {
	return value_t(std::any(value));
}

template <typename T>
[[nodiscard]] inline value_t table_value_(vector_t<T> &&value) {
	return value_t(std::any(std::move(value)));
}

template <typename V>
[[nodiscard]] inline table_build_item_t table_item_(V &&value) {
	return table_build_item_t{std::monostate{}, table_value_(std::forward<V>(value))};
}

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(const int_t &key, V &&value) {
	return table_build_item_t{key, table_value_(std::forward<V>(value))};
}

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(int key, V &&value) {
	return table_kv_(int_t(static_cast<std::int64_t>(key)), std::forward<V>(value));
}

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(const string_t &key, V &&value) {
	return table_build_item_t{key, table_value_(std::forward<V>(value))};
}

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(const char *key, V &&value) {
	return table_kv_(string_t(key), std::forward<V>(value));
}

inline void table_add_item_(table_t &table, const table_build_item_t &item) {
	if (std::holds_alternative<std::monostate>(item.key)) {
		(void)table.append(item.value);
		return;
	}
	if (std::holds_alternative<int_t>(item.key)) {
		table.set(std::get<int_t>(item.key), item.value);
		return;
	}
	table.set(std::get<string_t>(item.key), item.value);
}

template <typename... Items>
[[nodiscard]] inline table_t table_new_(Items &&...items) {
	table_t table{};
	(table_add_item_(table, std::forward<Items>(items)), ...);
	return table;
}


[[nodiscard]] inline maybe_value_t table_find_(const table_t &table, const int_t &key) {
	return table.find(key);
}

[[nodiscard]] inline maybe_value_t table_find_(const table_t &table, const string_t &key) {
	return table.find(key);
}

[[nodiscard]] inline maybe_value_t table_find_(const maybe_value_t &maybe_value, const int_t &key) {
	if (!maybe_value.has_value().native_value()) {
		return maybe_value_t(nullopt);
	}
	const auto *table = maybe_value.value().table_if();
	if (table == nullptr) {
		return maybe_value_t(nullopt);
	}
	return table->find(key);
}

[[nodiscard]] inline maybe_value_t table_find_(const maybe_value_t &maybe_value, const string_t &key) {
	if (!maybe_value.has_value().native_value()) {
		return maybe_value_t(nullopt);
	}
	const auto *table = maybe_value.value().table_if();
	if (table == nullptr) {
		return maybe_value_t(nullopt);
	}
	return table->find(key);
}

[[nodiscard]] inline bool_t table_has_(const table_t &table, const int_t &key) {
	return table.has(key);
}

[[nodiscard]] inline bool_t table_has_(const table_t &table, const string_t &key) {
	return table.has(key);
}

[[nodiscard]] inline bool_t table_has_(const maybe_value_t &maybe_value, const int_t &key) {
	if (!maybe_value.has_value().native_value()) {
		return bool_t(false);
	}
	const auto *table = maybe_value.value().table_if();
	if (table == nullptr) {
		return bool_t(false);
	}
	return table->has(key);
}

[[nodiscard]] inline bool_t table_has_(const maybe_value_t &maybe_value, const string_t &key) {
	if (!maybe_value.has_value().native_value()) {
		return bool_t(false);
	}
	const auto *table = maybe_value.value().table_if();
	if (table == nullptr) {
		return bool_t(false);
	}
	return table->has(key);
}

} // namespace scpp
