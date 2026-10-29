#pragma once
// Adapted from runtime/include/scpp/support/hash_t.hpp at 71925dd8b.
// Retains implicit dense keys, four-byte codes and the donor custom probing.
#include "hash_index.hpp"
#include "packed_key.hpp"
#include <utility>
namespace scpp::detail {
template<class Value> class packed_table final {
    static_assert(std::is_nothrow_default_constructible_v<Value>);
    static_assert(std::is_nothrow_move_constructible_v<Value>);
    static_assert(std::is_nothrow_move_assignable_v<Value>);
    using key_storage_t = std::uint32_t;
    using native_values_t = std::vector<Value>;
    using native_keys_t = std::vector<key_storage_t>;

    static constexpr std::uint32_t TOMBSTONE_KEY = packed_key::tombstone;

    native_values_t values_;
    std::variant<std::monostate, native_keys_t> keys_;
    hash_detail::hash_index_variant_t hash_index_;

    void wake_up_associative_mode() {
        native_keys_t keys;
        keys.reserve(values_.empty() ? 4 : values_.size());
        for (std::size_t i = 0; i < values_.size(); ++i) keys.push_back(static_cast<std::uint32_t>(i));
        auto index = make_index(keys, checked_capacity(values_.empty() ? 4 : values_.size() * 2ull));
        // Both moves are nonthrowing. Nothing is published until all allocations succeed.
        keys_ = std::move(keys);
        hash_index_ = std::move(index);
    }

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

    static hash_detail::hash_index_variant_t make_index(const native_keys_t& keys, std::uint32_t next_capacity) {
        auto next_index = hash_detail::index_for_entries(keys.size());
        std::visit([&](auto &idx) {
            using I = std::decay_t<decltype(idx)>;
            if constexpr (!std::is_same_v<I, std::monostate>) {
                idx.capacity_ = next_capacity;
                idx.size_ = 0;
                idx.deleted_count_ = 0;
                idx.ctrl_bytes_.assign(next_capacity, static_cast<std::uint8_t>(hash_detail::ctrl_state_t::empty));
                idx.buckets_.resize(next_capacity);
                for (std::uint32_t i = 0; i < keys.size(); ++i) {
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

        return next_index;
    }

    void rehash(std::uint32_t capacity) {
        hash_index_ = make_index(std::get<native_keys_t>(keys_), capacity);
    }

    void add_to_index(std::uint32_t key, std::uint32_t physical_index) {
        // Rebuilding includes the newly appended live entry already.
        if (check_and_rehash()) return;
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
                if (idx.ctrl_bytes_[bucket] == static_cast<std::uint8_t>(hash_detail::ctrl_state_t::deleted)) {
                    --idx.deleted_count_;
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
        return erase_code(index);
    }

    void insert_or_assign_code(std::uint32_t key, Value value) {
        // Replacement retains its physical position.
        if (auto [found, ptr] = find_code(key); found) {
            *ptr = std::move(value);
            return;
        }
        if (values_.size() >= std::numeric_limits<std::uint32_t>::max())
            throw std::length_error("packed_physical_entry_limit");
        if (std::holds_alternative<std::monostate>(keys_)) {
            if (!packed_key::is_string(key)) {
                if (key == values_.size()) {
                    values_.push_back(std::move(value));
                    return;
                }
            }
            wake_up_associative_mode();
        }
        auto &keys = std::get<native_keys_t>(keys_);
        const auto physical_index = static_cast<std::uint32_t>(values_.size());
        try {
            keys.push_back(key);
            values_.push_back(std::move(value));
            add_to_index(key, physical_index);
        } catch (...) {
            if (values_.size() > physical_index) values_.pop_back();
            if (keys.size() > physical_index) keys.pop_back();
            throw;
        }
    }

    std::pair<bool, Value *> find_code(std::uint32_t key) {
        if (std::holds_alternative<std::monostate>(keys_)) {
            if (!packed_key::is_string(key) && key < values_.size()) {
                return {true, &values_[key]};
            }
            return {false, nullptr};
        }
        auto &keys = std::get<native_keys_t>(keys_);
        std::pair<bool, Value *> result{false, nullptr};
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

    std::pair<bool, const Value *> find_code(std::uint32_t key) const {
        if (std::holds_alternative<std::monostate>(keys_)) {
            if (!packed_key::is_string(key) && key < values_.size()) {
                return {true, &values_[key]};
            }
            return {false, nullptr};
        }
        const auto &keys = std::get<native_keys_t>(keys_);
        std::pair<bool, const Value *> result{false, nullptr};
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

    bool erase_code(std::uint32_t key) {
        if (!find_code(key).first) return false;
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
                            values_[physical_index] = Value{};
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
            if (values_.size() > packed_key::max_integer)
                throw std::overflow_error("packed_append_key_limit");
            return static_cast<std::uint32_t>(values_.size());
        }
        std::uint32_t max_key = 0;
        bool have_key = false;
        for (const auto key : std::get<native_keys_t>(keys_)) {
            if (key == TOMBSTONE_KEY || packed_key::is_string(key)) continue;
            if (!have_key || key > max_key) {
                max_key = key;
                have_key = true;
            }
        }
        if (have_key && max_key == packed_key::max_integer)
            throw std::overflow_error("packed_append_key_limit");
        return have_key ? (max_key + 1) : 0;
    }

public:
    static std::uint32_t checked_capacity(std::uint64_t count) {
        if (count > std::numeric_limits<std::uint32_t>::max())
            throw std::length_error("packed_index_capacity_limit");
        return static_cast<std::uint32_t>(count);
    }
    packed_table() = default;
    packed_table(const packed_table& other) : values_(other.values_) {
        if (!other.is_packed()) {
            // Keep throwing vector copies outside the representation variant.
            // Publish only complete storage, as in the packed transition.
            auto keys = std::get<native_keys_t>(other.keys_);
            auto index = make_index(keys, checked_capacity(values_.empty() ? 4 : values_.size() * 2ull));
            keys_ = std::move(keys);
            hash_index_ = std::move(index);
        }
    }
    packed_table(packed_table&& other) noexcept { swap(other); }
    packed_table& operator=(const packed_table& other) {
        if (this != &other) { packed_table copy(other); swap(copy); }
        return *this;
    }
    packed_table& operator=(packed_table&& other) noexcept {
        if (this != &other) { packed_table discarded; swap(discarded); swap(other); }
        return *this;
    }
    void swap(packed_table& other) noexcept {
        values_.swap(other.values_); keys_.swap(other.keys_); hash_index_.swap(other.hash_index_);
    }
    bool is_packed() const noexcept { return std::holds_alternative<std::monostate>(keys_); }
    std::size_t size() const noexcept {
        if (is_packed()) return values_.size();
        std::size_t count = 0;
        for (auto key : std::get<native_keys_t>(keys_)) if (key != TOMBSTONE_KEY) ++count;
        return count;
    }
    void clear() noexcept { packed_table empty; swap(empty); }
    void set(std::int64_t key, Value value) { insert_or_assign_code(packed_key::integer(key), std::move(value)); }
    void set(std::string_view key, Value value) {
        insert_or_assign_code(packed_string_pool::instance().intern(key), std::move(value));
    }
    const Value* find(std::int64_t key) const { return find_code(packed_key::integer(key)).second; }
    const Value* find(std::string_view key) const {
        auto code = packed_string_pool::instance().find(key);
        return code ? find_code(*code).second : nullptr;
    }
    bool remove(std::int64_t key) { return erase_code(packed_key::integer(key)); }
    bool remove(std::string_view key) {
        auto code = packed_string_pool::instance().find(key);
        return code && erase_code(*code);
    }
    std::int64_t append(Value value) {
        auto key = next_append_key();
        insert_or_assign_code(key, std::move(value));
        return key;
    }
    // Private callback only: callers must not mutate this table during visitation.
    template<class Visitor> void visit_entries(Visitor&& visitor) const {
        for (std::size_t i = 0; i < values_.size(); ++i) {
            auto code = is_packed() ? static_cast<std::uint32_t>(i) : std::get<native_keys_t>(keys_)[i];
            if (code != TOMBSTONE_KEY) visitor(code, values_[i]);
        }
    }
    std::size_t physical_size() const noexcept { return values_.size(); }
    std::size_t key_capacity() const noexcept { return is_packed() ? 0 : std::get<native_keys_t>(keys_).capacity(); }
    unsigned index_bytes() const noexcept {
        return std::visit([](const auto& idx) -> unsigned {
            using I = std::decay_t<decltype(idx)>;
            if constexpr (std::is_same_v<I, std::monostate>) return 0;
            else return sizeof(typename std::decay_t<decltype(idx.buckets_)>::value_type);
        }, hash_index_);
    }
    std::size_t estimated_storage_bytes() const noexcept {
        return sizeof(*this) + values_.capacity() * sizeof(Value) + key_capacity() * sizeof(key_storage_t)
            + std::visit([](const auto& idx) -> std::size_t {
                using I = std::decay_t<decltype(idx)>;
                if constexpr (std::is_same_v<I, std::monostate>) return 0;
                else return idx.ctrl_bytes_.capacity() + idx.buckets_.capacity() * sizeof(typename std::decay_t<decltype(idx.buckets_)>::value_type);
            }, hash_index_);
    }
};
} // namespace scpp::detail
