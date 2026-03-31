#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/unique_p.hpp"

#include <cstdint>
#include <vector>
#include <variant>

namespace scpp {

class value_t;

template <typename T_VALUE>
class table_t final {
private:
    using key_storage_t   = std::uint32_t;
    using native_values_t = std::vector<T_VALUE>;
    using native_keys_t   = std::vector<key_storage_t>;

    static constexpr std::uint32_t TOMBSTONE_KEY = 0xFFFFFFFFu;

    enum class ctrl_state_t : std::uint8_t {
        empty    = 0b10000000,
        deleted  = 0b11111110,
        sentinel = 0b11111111,
    };

    template <typename idx_t>
    struct flat_hash_index_t {
        std::vector<std::uint8_t> ctrl_bytes_;
        std::vector<idx_t>        buckets_;
        std::uint32_t capacity_      = 0;
        std::uint32_t size_          = 0;
        std::uint32_t deleted_count_ = 0;
    };

    // The core storage for PHP-style ordered arrays
    native_values_t values_;
    native_keys_t   keys_;
    
    // Your flat hash indices for O(1) lookup
    flat_hash_index_t<std::uint32_t> int_index_;
    flat_hash_index_t<std::uint32_t> string_index_;

    int_t next_int_key_{0};

    // Internal lookup logic (restored from your design)
    std::uint32_t find_int_slot(const int_t& key) const;
    std::uint32_t find_string_slot(const string_t& key) const;
    void rehash_if_needed();

public:
    table_t() = default;
    table_t(const table_t& other);
    table_t(table_t&& other) noexcept;
    table_t& operator=(const table_t& other);
    table_t& operator=(table_t&& other) noexcept;

    [[nodiscard]] std::size_t size() const noexcept { return values_.size(); }

    // --- Array Access (The new "Fat" logic) ---
    
    // Returns existing value or creates a new entry (Write Context)
    T_VALUE& operator[](const int_t& key);
    T_VALUE& operator[](const string_t& key);

    // Returns value or reference to static null (Read Context)
    const T_VALUE& operator[](const int_t& key) const;
    const T_VALUE& operator[](const string_t& key) const;

    T_VALUE& append(const T_VALUE& value);
    void insert_or_assign(const int_t& key, const T_VALUE& value);
    void insert_or_assign(const string_t& key, const T_VALUE& value);

    template <typename Visitor>
    void debug_visit_entries(Visitor visitor) const;
};

// Ergonomic helpers for generated literals
template <typename TKey, typename TValue>
std::pair<TKey, TValue> table_kv_(TKey &&key, TValue &&value) {
    return {std::forward<TKey>(key), std::forward<TValue>(value)};
}

template <typename... TArgs>
unique_p<table_t<value_t>> table_new_(TArgs &&...args);

} // namespace scpp
