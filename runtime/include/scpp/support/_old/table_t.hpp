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
#include "scpp/value_t.hpp"

#include <cstdint>
#include <memory>
#include <stdexcept>
#include <unordered_map>
#include <utility>
#include <variant>
#include <vector>

namespace scpp {

// ============================================================
// TableValue concept + whitelist
// ============================================================

template <typename T> struct is_table_value : std::false_type {};

// Monomorphic scalars
template <> struct is_table_value<null_t>   : std::true_type {};
template <> struct is_table_value<bool_t>   : std::true_type {};
template <> struct is_table_value<int_t>    : std::true_type {};
template <> struct is_table_value<float_t>  : std::true_type {};
template <> struct is_table_value<string_t> : std::true_type {};
// Polymorphic specialised after class declarations below
template <> struct is_table_value<value_t> : std::true_type {};
// Table pointer wrappers specialised after table_t is defined
template <typename T> struct is_table_value<std::shared_ptr<table_t<T>>> : std::true_type {};
template <typename T> struct is_table_value<std::unique_ptr<table_t<T>>> : std::true_type {};
template <typename T> struct is_table_value<std::weak_ptr<table_t<T>>>   : std::true_type {};

template <typename T>
concept TableValue = is_table_value<T>::value;

// ============================================================
// maybe_value_t find() result only
// ============================================================

using maybe_value_t = nullable<value_t>;

class table_slot_t;
class ref_int_t;
class ref_float_t;
class ref_bool_t;
class ref_string_t;
class ref_table_t;

// Generic over any nullable<T> works for maybe_value_t and typed find() results alike.
template <typename T>
[[nodiscard]] inline bool_t is_nullopt(const nullable<T> &v) noexcept {
    return bool_t{!v.has_value().native_value()};
}
template <typename T>
[[nodiscard]] inline bool_t was_found(const nullable<T> &v) noexcept {
    return v.has_value();
}

// ============================================================
// table_t<T_VALUE>
// ============================================================

class table_slot_t final {
private:
    table_t<value_t> *table_ = nullptr;
    value_t key_ = value_t{null_t{}};

    [[nodiscard]] value_t read_value_() const;
    [[nodiscard]] value_t &materialize_slot_();

public:
    table_slot_t() = default;
    table_slot_t(table_t<value_t> &table, value_t key)
        : table_(&table), key_(std::move(key)) {}

    [[nodiscard]] bool_t exists() const;

    operator value_t() const;
    operator int_t() const;
    operator float_t() const;
    operator bool_t() const;
    operator string_t() const;

    operator int_t&();
    operator float_t&();
    operator bool_t&();
    operator string_t&();

    table_slot_t operator[](const int_t &key);
    table_slot_t operator[](const string_t &key);

    [[nodiscard]] int_t append(const value_t &value);
    [[nodiscard]] int_t append(value_t &&value);
    [[nodiscard]] table_t<value_t> &as_table_ref();
    [[nodiscard]] table_t<value_t> table_copy() const;

    table_slot_t &operator=(const value_t &value);
    table_slot_t &operator=(value_t &&value);

    value_t &operator++();
    value_t operator++(int);
    value_t &operator--();
    value_t operator--(int);

    table_slot_t &operator+=(const value_t &right);
    table_slot_t &operator-=(const value_t &right);
    table_slot_t &operator*=(const value_t &right);
    table_slot_t &operator/=(const value_t &right);
    table_slot_t &operator%=(const value_t &right);
    table_slot_t &operator&=(const value_t &right);
    table_slot_t &operator|=(const value_t &right);
    table_slot_t &operator^=(const value_t &right);
    table_slot_t &operator<<=(const value_t &right);
    table_slot_t &operator>>=(const value_t &right);
};

class ref_int_t final {
private:
    std::variant<int_t *, table_slot_t> target_;

public:
    explicit ref_int_t(int_t &value) : target_(&value) {}
    explicit ref_int_t(table_slot_t slot);

    operator int_t() const;
    ref_int_t &operator=(const int_t &value);
    ref_int_t &operator=(const value_t &value);
    ref_int_t &operator+=(const value_t &right);
    ref_int_t &operator-=(const value_t &right);
    ref_int_t &operator*=(const value_t &right);
    ref_int_t &operator/=(const value_t &right);
    ref_int_t &operator%=(const value_t &right);
};

class ref_float_t final {
private:
    std::variant<float_t *, table_slot_t> target_;

public:
    explicit ref_float_t(float_t &value) : target_(&value) {}
    explicit ref_float_t(table_slot_t slot);

    operator float_t() const;
    ref_float_t &operator=(const float_t &value);
    ref_float_t &operator=(const value_t &value);
    ref_float_t &operator+=(const value_t &right);
    ref_float_t &operator-=(const value_t &right);
    ref_float_t &operator*=(const value_t &right);
    ref_float_t &operator/=(const value_t &right);
};

class ref_bool_t final {
private:
    std::variant<bool_t *, table_slot_t> target_;

public:
    explicit ref_bool_t(bool_t &value) : target_(&value) {}
    explicit ref_bool_t(table_slot_t slot);

    operator bool_t() const;
    ref_bool_t &operator=(const bool_t &value);
    ref_bool_t &operator=(const value_t &value);
};

class ref_string_t final {
private:
    std::variant<string_t *, table_slot_t> target_;

public:
    explicit ref_string_t(string_t &value) : target_(&value) {}
    explicit ref_string_t(table_slot_t slot);

    operator string_t() const;
    ref_string_t &operator=(const string_t &value);
    ref_string_t &operator=(const value_t &value);
    ref_string_t &operator+=(const value_t &right);
};

class ref_table_t final {
private:
    std::variant<table_t<value_t> *, table_slot_t> target_;
    [[nodiscard]] table_t<value_t> &get_();
    [[nodiscard]] const table_t<value_t> &get_() const;

public:
    explicit ref_table_t(table_t<value_t> &value) : target_(&value) {}
    explicit ref_table_t(table_slot_t slot);

    operator table_t<value_t>&();
    operator const table_t<value_t>&() const;
    table_slot_t operator[](const int_t &key);
    table_slot_t operator[](const string_t &key);
    [[nodiscard]] int_t append(const value_t &value);
    [[nodiscard]] int_t append(value_t &&value);
};

[[nodiscard]] inline ref_int_t ref_int(int_t &value) { return ref_int_t(value); }
[[nodiscard]] inline ref_int_t ref_int(table_slot_t slot);
[[nodiscard]] inline ref_float_t ref_float(float_t &value) { return ref_float_t(value); }
[[nodiscard]] inline ref_float_t ref_float(table_slot_t slot);
[[nodiscard]] inline ref_bool_t ref_bool(bool_t &value) { return ref_bool_t(value); }
[[nodiscard]] inline ref_bool_t ref_bool(table_slot_t slot);
[[nodiscard]] inline ref_string_t ref_string(string_t &value) { return ref_string_t(value); }
[[nodiscard]] inline ref_string_t ref_string(table_slot_t slot);
[[nodiscard]] inline ref_table_t ref_table(table_t<value_t> &value) { return ref_table_t(value); }
[[nodiscard]] inline ref_table_t ref_table(table_slot_t slot);


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

    // Per-instantiation string pool (singleton per T_VALUE type).
    class string_pool_t {
    public:
        static constexpr std::uint32_t string_key_flag = 0x80000000u;
        static constexpr std::uint32_t string_id_mask  = 0x7FFFFFFFu;

        static string_pool_t &instance() {
            static string_pool_t pool;
            return pool;
        }
        string_pool_t(const string_pool_t &)            = delete;
        string_pool_t &operator=(const string_pool_t &) = delete;

        std::uint32_t intern(const string_t &value) {
            auto it = lookup_.find(value.native_value());
            if (it != lookup_.end()) return it->second | string_key_flag;
            const auto id = static_cast<std::uint32_t>(strings_.size());
            if (id > string_id_mask) throw std::overflow_error("string pool full");
            auto [ins, ok] = lookup_.emplace(value.native_value(), id);
            (void)ok;
            strings_.push_back(&ins->first);
            return id | string_key_flag;
        }
        [[nodiscard]] string_t resolve(std::uint32_t k) const {
            if (!is_string_id(k)) throw std::logic_error("table_t::string_pool_t::resolve: key is not a string id");
            const auto id = (k & string_id_mask);
            if (id >= strings_.size()) throw std::out_of_range("table_t::string_pool_t::resolve: invalid string id");
            return string_t(*strings_[id]);
        }
        static bool is_string_id(std::uint32_t k) { return (k & string_key_flag) != 0; }

    private:
        string_pool_t() = default;
        std::unordered_map<std::string, std::uint32_t> lookup_;
        std::vector<const std::string *>               strings_;
    };

    native_values_t values_;
    std::variant<std::monostate, native_keys_t> keys_;
    std::variant<
        std::monostate,
        flat_hash_index_t<std::uint8_t>,
        flat_hash_index_t<std::uint16_t>,
        flat_hash_index_t<std::uint32_t>
    > hash_index_;

    static std::uint64_t fast_hash(std::uint32_t v) noexcept {
        std::uint64_t h = v;
        h ^= h >> 16; h *= 0x85ebca6bull;
        h ^= h >> 13; h *= 0xc2b2ae35ull;
        h ^= h >> 16; return h;
    }
    static std::uint32_t make_string_key(const string_t &k) {
        return string_pool_t::instance().intern(k);
    }

    void wake_up_associative_mode() {
        const auto n = static_cast<std::uint32_t>(values_.size());
        keys_.template emplace<native_keys_t>();
        auto &ks = std::get<native_keys_t>(keys_);
        ks.reserve(n > 0 ? n : 4);
        for (std::uint32_t i = 0; i < n; ++i) ks.push_back(i);
        rehash(n > 0 ? n * 2 : 4);
    }

    void add_to_index(std::uint32_t key, std::uint32_t phys) {
        check_and_rehash();
        std::visit([&](auto &idx) {
            using I = std::decay_t<decltype(idx)>;
            if constexpr (!std::is_same_v<I, std::monostate>) {
                const auto h = fast_hash(key);
                auto h1 = static_cast<std::uint32_t>(h % idx.capacity_);
                const auto h2 = static_cast<std::uint8_t>(h & 0x7F);
                while (idx.ctrl_bytes_[h1] < 128 &&
                       idx.ctrl_bytes_[h1] != static_cast<uint8_t>(ctrl_state_t::deleted))
                    h1 = (h1 + 1) % idx.capacity_;
                idx.ctrl_bytes_[h1] = h2;
                idx.buckets_[h1] = static_cast<typename decltype(idx.buckets_)::value_type>(phys);
                idx.size_++;
            }
        }, hash_index_);
    }

    bool erase_packed(std::uint32_t index) {
        if (index >= values_.size()) return false;
        wake_up_associative_mode();
        return erase_int(index);
    }

    void check_and_rehash() {
        if (std::holds_alternative<std::monostate>(hash_index_)) return;
        std::uint32_t cap = 0, sz = 0, del = 0;
        std::visit([&](auto &idx) {
            using I = std::decay_t<decltype(idx)>;
            if constexpr (!std::is_same_v<I, std::monostate>)
                { cap = idx.capacity_; sz = idx.size_; del = idx.deleted_count_; }
        }, hash_index_);
        if (sz + del >= (cap * 3) / 4) {
            auto nc = (sz >= cap / 2) ? cap * 2 : cap;
            rehash(nc == 0 ? 4 : nc);
        }
    }

    void rehash(std::uint32_t nc) {
        decltype(hash_index_) ni;
        if (nc <= 256)        ni.template emplace<flat_hash_index_t<std::uint8_t>>();
        else if (nc <= 65536) ni.template emplace<flat_hash_index_t<std::uint16_t>>();
        else                  ni.template emplace<flat_hash_index_t<std::uint32_t>>();
        auto &ks = std::get<native_keys_t>(keys_);
        std::visit([&](auto &idx) {
            using I = std::decay_t<decltype(idx)>;
            if constexpr (!std::is_same_v<I, std::monostate>) {
                idx.capacity_ = nc; idx.size_ = 0; idx.deleted_count_ = 0;
                idx.ctrl_bytes_.assign(nc, static_cast<uint8_t>(ctrl_state_t::empty));
                idx.buckets_.resize(nc);
                for (std::uint32_t i = 0; i < values_.size(); ++i) {
                    if (ks[i] == TOMBSTONE_KEY) continue;
                    const auto h = fast_hash(ks[i]);
                    auto h1 = static_cast<std::uint32_t>(h % nc);
                    const auto h2 = static_cast<std::uint8_t>(h & 0x7F);
                    while (idx.ctrl_bytes_[h1] < 128) h1 = (h1 + 1) % nc;
                    idx.ctrl_bytes_[h1] = h2;
                    idx.buckets_[h1] = static_cast<typename decltype(idx.buckets_)::value_type>(i);
                    idx.size_++;
                }
            }
        }, ni);
        hash_index_ = std::move(ni);
    }

    void insert_or_assign_int(std::uint32_t key, T_VALUE value) {
        if (std::holds_alternative<std::monostate>(keys_)) {
            if (!string_pool_t::is_string_id(key)) {
                if (key < values_.size())  { values_[key] = std::move(value); return; }
                if (key == values_.size()) { values_.push_back(std::move(value)); return; }
            }
            wake_up_associative_mode();
        }
        if (auto [found, ptr] = find_int(key); found) { *ptr = std::move(value); return; }
        auto &ks = std::get<native_keys_t>(keys_);
        const auto phys = static_cast<std::uint32_t>(values_.size());
        ks.push_back(key);
        values_.push_back(std::move(value));
        add_to_index(key, phys);
    }

    std::pair<bool, T_VALUE *> find_int(std::uint32_t key) {
        if (std::holds_alternative<std::monostate>(keys_)) {
            if (!string_pool_t::is_string_id(key) && key < values_.size())
                return {true, &values_[key]};
            return {false, nullptr};
        }
        auto &ks = std::get<native_keys_t>(keys_);
        std::pair<bool, T_VALUE *> res{false, nullptr};
        std::visit([&](auto &idx) {
            using I = std::decay_t<decltype(idx)>;
            if constexpr (!std::is_same_v<I, std::monostate>) {
                if (!idx.capacity_) return;
                const auto h = fast_hash(key);
                auto h1 = static_cast<std::uint32_t>(h % idx.capacity_);
                const auto h2 = static_cast<std::uint8_t>(h & 0x7F);
                while (idx.ctrl_bytes_[h1] != static_cast<uint8_t>(ctrl_state_t::empty)) {
                    if (idx.ctrl_bytes_[h1] == h2) {
                        const auto pi = static_cast<std::uint32_t>(idx.buckets_[h1]);
                        if (ks[pi] == key) { res = {true, &values_[pi]}; return; }
                    }
                    h1 = (h1 + 1) % idx.capacity_;
                }
            }
        }, hash_index_);
        return res;
    }

    std::pair<bool, const T_VALUE *> find_int(std::uint32_t key) const {
        if (std::holds_alternative<std::monostate>(keys_)) {
            if (!string_pool_t::is_string_id(key) && key < values_.size())
                return {true, &values_[key]};
            return {false, nullptr};
        }
        const auto &ks = std::get<native_keys_t>(keys_);
        std::pair<bool, const T_VALUE *> res{false, nullptr};
        std::visit([&](const auto &idx) {
            using I = std::decay_t<decltype(idx)>;
            if constexpr (!std::is_same_v<I, std::monostate>) {
                if (!idx.capacity_) return;
                const auto h = fast_hash(key);
                auto h1 = static_cast<std::uint32_t>(h % idx.capacity_);
                const auto h2 = static_cast<std::uint8_t>(h & 0x7F);
                while (idx.ctrl_bytes_[h1] != static_cast<uint8_t>(ctrl_state_t::empty)) {
                    if (idx.ctrl_bytes_[h1] == h2) {
                        const auto pi = static_cast<std::uint32_t>(idx.buckets_[h1]);
                        if (ks[pi] == key) { res = {true, &values_[pi]}; return; }
                    }
                    h1 = (h1 + 1) % idx.capacity_;
                }
            }
        }, hash_index_);
        return res;
    }

    bool erase_int(std::uint32_t key) {
        if (std::holds_alternative<std::monostate>(keys_)) return erase_packed(key);
        bool found = false;
        auto &ks = std::get<native_keys_t>(keys_);
        std::visit([&](auto &idx) {
            using I = std::decay_t<decltype(idx)>;
            if constexpr (!std::is_same_v<I, std::monostate>) {
                if (!idx.capacity_) return;
                const auto h = fast_hash(key);
                auto h1 = static_cast<std::uint32_t>(h % idx.capacity_);
                const auto h2 = static_cast<std::uint8_t>(h & 0x7F);
                while (idx.ctrl_bytes_[h1] != static_cast<uint8_t>(ctrl_state_t::empty)) {
                    if (idx.ctrl_bytes_[h1] == h2) {
                        const auto pi = static_cast<std::uint32_t>(idx.buckets_[h1]);
                        if (ks[pi] == key) {
                            idx.ctrl_bytes_[h1] = static_cast<uint8_t>(ctrl_state_t::deleted);
                            idx.size_--; idx.deleted_count_++;
                            ks[pi] = TOMBSTONE_KEY;
                            values_[pi] = T_VALUE{};
                            found = true; return;
                        }
                    }
                    h1 = (h1 + 1) % idx.capacity_;
                }
            }
        }, hash_index_);
        return found;
    }

    std::uint32_t next_append_key() const {
        if (std::holds_alternative<std::monostate>(keys_))
            return static_cast<std::uint32_t>(values_.size());
        std::uint32_t max_k = 0; bool have = false;
        for (const auto k : std::get<native_keys_t>(keys_)) {
            if (k == TOMBSTONE_KEY || string_pool_t::is_string_id(k)) continue;
            if (!have || k > max_k) { max_k = k; have = true; }
        }
        return have ? (max_k + 1) : 0;
    }

public:
    table_t() : keys_(std::monostate{}), hash_index_(std::monostate{}) {}

    table_t(const table_t &other) requires std::copyable<T_VALUE>
        : keys_(std::monostate{}), hash_index_(std::monostate{}) {
        values_.reserve(other.values_.size());
        for (const auto &v : other.values_) values_.push_back(v);
        if (std::holds_alternative<native_keys_t>(other.keys_)) {
            keys_ = std::get<native_keys_t>(other.keys_);
            const auto n = static_cast<std::uint32_t>(values_.size());
            rehash(n > 0 ? n * 2 : 4);
        }
    }
    table_t(table_t &&) noexcept = default;

    table_t &operator=(const table_t &other) requires std::copyable<T_VALUE> {
        if (this == &other) return *this;
        table_t copy(other); *this = std::move(copy); return *this;
    }
    table_t &operator=(table_t &&) noexcept = default;
    ~table_t() = default;

    [[nodiscard]] bool_t      empty()     const noexcept {
        if (std::holds_alternative<std::monostate>(keys_)) return bool_t{values_.empty()};
        for (const auto k : std::get<native_keys_t>(keys_))
            if (k != TOMBSTONE_KEY) return bool_t{false};
        return bool_t{true};
    }
    [[nodiscard]] std::size_t size()      const noexcept {
        if (std::holds_alternative<std::monostate>(keys_)) return values_.size();
        std::size_t n = 0;
        for (const auto k : std::get<native_keys_t>(keys_)) if (k != TOMBSTONE_KEY) ++n;
        return n;
    }
    [[nodiscard]] bool_t      is_packed() const noexcept {
        return bool_t{std::holds_alternative<std::monostate>(keys_)};
    }

    template <typename Fn>
    void debug_visit_entries(Fn &&fn) const {
        if (std::holds_alternative<std::monostate>(keys_)) {
            for (std::uint32_t i = 0; i < values_.size(); ++i) {
                fn(int_t{static_cast<std::int64_t>(i)}, values_[i]);
            }
            return;
        }

        const auto &ks = std::get<native_keys_t>(keys_);
        for (std::uint32_t i = 0; i < ks.size(); ++i) {
            const auto key = ks[i];
            if (key == TOMBSTONE_KEY) continue;
            if (string_pool_t::is_string_id(key)) {
                fn(string_pool_t::instance().resolve(key), values_[i]);
                continue;
            }
            fn(int_t{static_cast<std::int64_t>(key)}, values_[i]);
        }
    }

    void clear() noexcept { values_.clear(); keys_ = std::monostate{}; hash_index_ = std::monostate{}; }

    [[nodiscard]] int_t append(const T_VALUE &v) requires std::copyable<T_VALUE> {
        const auto k = next_append_key(); insert_or_assign_int(k, T_VALUE{v});
        return int_t{static_cast<int64_t>(k)};
    }
    [[nodiscard]] int_t append(T_VALUE &&v) {
        const auto k = next_append_key(); insert_or_assign_int(k, std::move(v));
        return int_t{static_cast<int64_t>(k)};
    }

    table_t &set(const int_t    &key, const T_VALUE &v) requires std::copyable<T_VALUE>
        { insert_or_assign_int(static_cast<std::uint32_t>(key.native_value()), T_VALUE{v}); return *this; }
    table_t &set(const string_t &key, const T_VALUE &v) requires std::copyable<T_VALUE>
        { insert_or_assign_int(make_string_key(key), T_VALUE{v}); return *this; }
    table_t &set(const int_t    &key, T_VALUE &&v)
        { insert_or_assign_int(static_cast<std::uint32_t>(key.native_value()), std::move(v)); return *this; }
    table_t &set(const string_t &key, T_VALUE &&v)
        { insert_or_assign_int(make_string_key(key), std::move(v)); return *this; }

    [[nodiscard]] bool_t has(const int_t    &key) const
        { return bool_t{find_int(static_cast<std::uint32_t>(key.native_value())).first}; }
    [[nodiscard]] bool_t has(const string_t &key) const
        { return bool_t{find_int(make_string_key(key)).first}; }

    [[nodiscard]] nullable<T_VALUE> find(const int_t &key) const requires std::copyable<T_VALUE> {
        auto [f, p] = find_int(static_cast<std::uint32_t>(key.native_value()));
        { if (!f) return nullable<T_VALUE>(nullopt); nullable<T_VALUE> r; r.native_value() = *p; return r; }
    }
    [[nodiscard]] nullable<T_VALUE> find(const string_t &key) const requires std::copyable<T_VALUE> {
        auto [f, p] = find_int(make_string_key(key));
        { if (!f) return nullable<T_VALUE>(nullopt); nullable<T_VALUE> r; r.native_value() = *p; return r; }
    }

    [[nodiscard]] value_t _find_val(const int_t &key) const requires std::same_as<T_VALUE, value_t> {
        auto [f, p] = find_int(static_cast<std::uint32_t>(key.native_value()));
        if (!f) return value_t{null_t{}};
        return p->clone();
    }
    [[nodiscard]] value_t _find_val(const string_t &key) const requires std::same_as<T_VALUE, value_t> {
        auto [f, p] = find_int(make_string_key(key));
        if (!f) return value_t{null_t{}};
        return p->clone();
    }

    value_t &_slot_ref(const int_t &key) requires std::same_as<T_VALUE, value_t> {
        const auto k = static_cast<std::uint32_t>(key.native_value());
        auto [f, p] = find_int(k);
        if (!f) { insert_or_assign_int(k, value_t{null_t{}}); return *find_int(k).second; }
        return *p;
    }
    value_t &_slot_ref(const string_t &key) requires std::same_as<T_VALUE, value_t> {
        const auto k = make_string_key(key);
        auto [f, p] = find_int(k);
        if (!f) { insert_or_assign_int(k, value_t{null_t{}}); return *find_int(k).second; }
        return *p;
    }

    T_VALUE &at(const int_t &key) {
        auto [f, p] = find_int(static_cast<std::uint32_t>(key.native_value()));
        if (!f) throw std::out_of_range("table_t::at(int_t): not found");
        return *p;
    }
    T_VALUE &at(const string_t &key) {
        auto [f, p] = find_int(make_string_key(key));
        if (!f) throw std::out_of_range("table_t::at(string_t): not found");
        return *p;
    }
    const T_VALUE &at(const int_t &key) const {
        auto [f, p] = find_int(static_cast<std::uint32_t>(key.native_value()));
        if (!f) throw std::out_of_range("table_t::at(int_t) const: not found");
        return *p;
    }
    const T_VALUE &at(const string_t &key) const {
        auto [f, p] = find_int(make_string_key(key));
        if (!f) throw std::out_of_range("table_t::at(string_t) const: not found");
        return *p;
    }

    T_VALUE &operator[](const int_t &key) requires (!std::same_as<T_VALUE, value_t>) {
        const auto k = static_cast<std::uint32_t>(key.native_value());
        auto [f, p] = find_int(k);
        if (!f) { insert_or_assign_int(k, T_VALUE{}); return *find_int(k).second; }
        return *p;
    }
    T_VALUE &operator[](const string_t &key) requires (!std::same_as<T_VALUE, value_t>) {
        const auto k = make_string_key(key);
        auto [f, p] = find_int(k);
        if (!f) { insert_or_assign_int(k, T_VALUE{}); return *find_int(k).second; }
        return *p;
    }

    [[nodiscard]] table_slot_t operator[](const int_t &key) requires std::same_as<T_VALUE, value_t> {
        return table_slot_t(*this, value_t{key});
    }
    [[nodiscard]] table_slot_t operator[](const string_t &key) requires std::same_as<T_VALUE, value_t> {
        return table_slot_t(*this, value_t{key});
    }
    [[nodiscard]] value_t operator[](const int_t &key) const requires std::same_as<T_VALUE, value_t> {
        return _find_val(key);
    }
    [[nodiscard]] value_t operator[](const string_t &key) const requires std::same_as<T_VALUE, value_t> {
        return _find_val(key);
    }

    [[nodiscard]] bool remove(const int_t    &key) { return erase_int(static_cast<std::uint32_t>(key.native_value())); }
    [[nodiscard]] bool remove(const string_t &key) { return erase_int(make_string_key(key)); }
};

// ============================================================
// table_slot_t definitions after table_t<value_t> is fully defined
// ============================================================

inline value_t table_slot_t::read_value_() const {
    if (table_ == nullptr) {
        return value_t{null_t{}};
    }

    if (key_.kind() == value_t::kind_t::int_v) {
        return table_->_find_val(key_.int_value());
    }
    if (const auto *string_key = key_.string_if(); string_key != nullptr) {
        return table_->_find_val(*string_key);
    }

    throw std::runtime_error("table_slot_t: unsupported key kind for read");
}

inline value_t &table_slot_t::materialize_slot_() {
    if (table_ == nullptr) {
        throw std::runtime_error("table_slot_t: null table in materialize_slot_");
    }

    if (key_.kind() == value_t::kind_t::int_v) {
        return table_->_slot_ref(key_.int_value());
    }
    if (const auto *string_key = key_.string_if(); string_key != nullptr) {
        return table_->_slot_ref(*string_key);
    }

    throw std::runtime_error("table_slot_t: unsupported key kind for materialize_slot_");
}

inline bool_t table_slot_t::exists() const {
    if (table_ == nullptr) {
        return bool_t{false};
    }
    if (key_.kind() == value_t::kind_t::int_v) {
        return table_->has(key_.int_value());
    }
    if (const auto *string_key = key_.string_if(); string_key != nullptr) {
        return table_->has(*string_key);
    }
    return bool_t{false};
}

inline table_slot_t::operator value_t() const { return read_value_(); }
inline table_slot_t::operator int_t() const { return cast<int_t>(read_value_()); }
inline table_slot_t::operator float_t() const { return cast<float_t>(read_value_()); }
inline table_slot_t::operator bool_t() const { return cast<bool_t>(read_value_()); }
inline table_slot_t::operator string_t() const { return cast<string_t>(read_value_()); }

inline table_slot_t::operator int_t&() { return materialize_slot_().as_int_ref(); }
inline table_slot_t::operator float_t&() { return materialize_slot_().as_float_ref(); }
inline table_slot_t::operator bool_t&() { return materialize_slot_().as_bool_ref(); }
inline table_slot_t::operator string_t&() { return materialize_slot_().as_string_ref(); }

inline table_slot_t table_slot_t::operator[](const int_t &key) {
    return table_slot_t(materialize_slot_().as_table_ref(), value_t{key});
}
inline table_slot_t table_slot_t::operator[](const string_t &key) {
    return table_slot_t(materialize_slot_().as_table_ref(), value_t{key});
}

inline int_t table_slot_t::append(const value_t &value) {
    return materialize_slot_().as_table_ref().append(value);
}
inline int_t table_slot_t::append(value_t &&value) {
    return materialize_slot_().as_table_ref().append(std::move(value));
}
inline table_t<value_t> &table_slot_t::as_table_ref() {
    return materialize_slot_().as_table_ref();
}

inline table_t<value_t> table_slot_t::table_copy() const {
    value_t current = read_value_();
    return current.as_table_ref();
}

inline table_slot_t &table_slot_t::operator=(const value_t &value) {
    materialize_slot_() = value;
    return *this;
}
inline table_slot_t &table_slot_t::operator=(value_t &&value) {
    materialize_slot_() = std::move(value);
    return *this;
}

inline value_t &table_slot_t::operator++() { return ++materialize_slot_(); }
inline value_t table_slot_t::operator++(int) { return materialize_slot_()++; }
inline value_t &table_slot_t::operator--() { return --materialize_slot_(); }
inline value_t table_slot_t::operator--(int) { return materialize_slot_()--; }

inline table_slot_t &table_slot_t::operator+=(const value_t &right) { materialize_slot_() += right; return *this; }
inline table_slot_t &table_slot_t::operator-=(const value_t &right) { materialize_slot_() -= right; return *this; }
inline table_slot_t &table_slot_t::operator*=(const value_t &right) { materialize_slot_() *= right; return *this; }
inline table_slot_t &table_slot_t::operator/=(const value_t &right) { materialize_slot_() /= right; return *this; }
inline table_slot_t &table_slot_t::operator%=(const value_t &right) { materialize_slot_() %= right; return *this; }
inline table_slot_t &table_slot_t::operator&=(const value_t &right) { materialize_slot_() &= right; return *this; }
inline table_slot_t &table_slot_t::operator|=(const value_t &right) { materialize_slot_() |= right; return *this; }
inline table_slot_t &table_slot_t::operator^=(const value_t &right) { materialize_slot_() ^= right; return *this; }
inline table_slot_t &table_slot_t::operator<<=(const value_t &right) { materialize_slot_() <<= right; return *this; }
inline table_slot_t &table_slot_t::operator>>=(const value_t &right) { materialize_slot_() >>= right; return *this; }

inline ref_int_t::ref_int_t(table_slot_t slot) : target_(std::move(slot)) {}
inline ref_float_t::ref_float_t(table_slot_t slot) : target_(std::move(slot)) {}
inline ref_bool_t::ref_bool_t(table_slot_t slot) : target_(std::move(slot)) {}
inline ref_string_t::ref_string_t(table_slot_t slot) : target_(std::move(slot)) {}
inline ref_table_t::ref_table_t(table_slot_t slot) : target_(std::move(slot)) {}

inline ref_int_t::operator int_t() const {
    if (auto *direct = std::get_if<int_t *>(&target_)) return **direct;
    return static_cast<value_t>(std::get<table_slot_t>(target_)).int_value();
}
inline ref_int_t &ref_int_t::operator=(const int_t &value) {
    if (auto *direct = std::get_if<int_t *>(&target_)) { **direct = value; return *this; }
    std::get<table_slot_t>(target_) = value_t(value);
    return *this;
}
inline ref_int_t &ref_int_t::operator=(const value_t &value) { return (*this = value.int_value()); }
inline ref_int_t &ref_int_t::operator+=(const value_t &right) { if (auto *direct = std::get_if<int_t *>(&target_)) { **direct += right.int_value(); return *this; } std::get<table_slot_t>(target_) += right; return *this; }
inline ref_int_t &ref_int_t::operator-=(const value_t &right) { if (auto *direct = std::get_if<int_t *>(&target_)) { **direct -= right.int_value(); return *this; } std::get<table_slot_t>(target_) -= right; return *this; }
inline ref_int_t &ref_int_t::operator*=(const value_t &right) { if (auto *direct = std::get_if<int_t *>(&target_)) { **direct *= right.int_value(); return *this; } std::get<table_slot_t>(target_) *= right; return *this; }
inline ref_int_t &ref_int_t::operator/=(const value_t &right) { if (auto *direct = std::get_if<int_t *>(&target_)) { **direct /= right.int_value(); return *this; } std::get<table_slot_t>(target_) /= right; return *this; }
inline ref_int_t &ref_int_t::operator%=(const value_t &right) { if (auto *direct = std::get_if<int_t *>(&target_)) { **direct %= right.int_value(); return *this; } std::get<table_slot_t>(target_) %= right; return *this; }

inline ref_float_t::operator float_t() const {
    if (auto *direct = std::get_if<float_t *>(&target_)) return **direct;
    return static_cast<value_t>(std::get<table_slot_t>(target_)).float_value();
}
inline ref_float_t &ref_float_t::operator=(const float_t &value) {
    if (auto *direct = std::get_if<float_t *>(&target_)) { **direct = value; return *this; }
    std::get<table_slot_t>(target_) = value_t(value);
    return *this;
}
inline ref_float_t &ref_float_t::operator=(const value_t &value) { return (*this = value.float_value()); }
inline ref_float_t &ref_float_t::operator+=(const value_t &right) { if (auto *direct = std::get_if<float_t *>(&target_)) { **direct += right.float_value(); return *this; } std::get<table_slot_t>(target_) += right; return *this; }
inline ref_float_t &ref_float_t::operator-=(const value_t &right) { if (auto *direct = std::get_if<float_t *>(&target_)) { **direct -= right.float_value(); return *this; } std::get<table_slot_t>(target_) -= right; return *this; }
inline ref_float_t &ref_float_t::operator*=(const value_t &right) { if (auto *direct = std::get_if<float_t *>(&target_)) { **direct *= right.float_value(); return *this; } std::get<table_slot_t>(target_) *= right; return *this; }
inline ref_float_t &ref_float_t::operator/=(const value_t &right) { if (auto *direct = std::get_if<float_t *>(&target_)) { **direct /= right.float_value(); return *this; } std::get<table_slot_t>(target_) /= right; return *this; }

inline ref_bool_t::operator bool_t() const {
    if (auto *direct = std::get_if<bool_t *>(&target_)) return **direct;
    return static_cast<value_t>(std::get<table_slot_t>(target_)).bool_value();
}
inline ref_bool_t &ref_bool_t::operator=(const bool_t &value) {
    if (auto *direct = std::get_if<bool_t *>(&target_)) { **direct = value; return *this; }
    std::get<table_slot_t>(target_) = value_t(value);
    return *this;
}
inline ref_bool_t &ref_bool_t::operator=(const value_t &value) { return (*this = value.bool_value()); }

inline ref_string_t::operator string_t() const {
    if (auto *direct = std::get_if<string_t *>(&target_)) return **direct;
    return *static_cast<value_t>(std::get<table_slot_t>(target_)).string_if();
}
inline ref_string_t &ref_string_t::operator=(const string_t &value) {
    if (auto *direct = std::get_if<string_t *>(&target_)) { **direct = value; return *this; }
    std::get<table_slot_t>(target_) = value_t(value);
    return *this;
}
inline ref_string_t &ref_string_t::operator=(const value_t &value) { return (*this = *value.string_if()); }
inline ref_string_t &ref_string_t::operator+=(const value_t &right) { if (auto *direct = std::get_if<string_t *>(&target_)) { (**direct).append(*right.string_if()); return *this; } std::get<table_slot_t>(target_) += right; return *this; }

inline table_t<value_t> &ref_table_t::get_() {
    if (auto *direct = std::get_if<table_t<value_t> *>(&target_)) return **direct;
    return std::get<table_slot_t>(target_).as_table_ref();
}
inline const table_t<value_t> &ref_table_t::get_() const {
    if (auto *direct = std::get_if<table_t<value_t> *>(&target_)) return **direct;
    return const_cast<ref_table_t *>(this)->get_();
}
inline ref_table_t::operator table_t<value_t>&() { return get_(); }
inline ref_table_t::operator const table_t<value_t>&() const { return get_(); }
inline table_slot_t ref_table_t::operator[](const int_t &key) { return get_()[key]; }
inline table_slot_t ref_table_t::operator[](const string_t &key) { return get_()[key]; }
inline int_t ref_table_t::append(const value_t &value) { return get_().append(value); }
inline int_t ref_table_t::append(value_t &&value) { return get_().append(std::move(value)); }

[[nodiscard]] inline ref_int_t ref_int(table_slot_t slot) { return ref_int_t(std::move(slot)); }
[[nodiscard]] inline ref_float_t ref_float(table_slot_t slot) { return ref_float_t(std::move(slot)); }
[[nodiscard]] inline ref_bool_t ref_bool(table_slot_t slot) { return ref_bool_t(std::move(slot)); }
[[nodiscard]] inline ref_string_t ref_string(table_slot_t slot) { return ref_string_t(std::move(slot)); }
[[nodiscard]] inline ref_table_t ref_table(table_slot_t slot) { return ref_table_t(std::move(slot)); }

[[nodiscard]] inline value_t operator+(const table_slot_t &value) { return +static_cast<value_t>(value); }
[[nodiscard]] inline value_t operator-(const table_slot_t &value) { return -static_cast<value_t>(value); }
[[nodiscard]] inline bool_t operator!(const table_slot_t &value) { return !static_cast<value_t>(value); }
[[nodiscard]] inline value_t operator~(const table_slot_t &value) { return ~static_cast<value_t>(value); }

[[nodiscard]] inline value_t operator+(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) + static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator+(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) + right; }
[[nodiscard]] inline value_t operator+(const value_t &left, const table_slot_t &right) { return left + static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator-(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) - static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator-(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) - right; }
[[nodiscard]] inline value_t operator-(const value_t &left, const table_slot_t &right) { return left - static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator*(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) * static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator*(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) * right; }
[[nodiscard]] inline value_t operator*(const value_t &left, const table_slot_t &right) { return left * static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator/(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) / static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator/(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) / right; }
[[nodiscard]] inline value_t operator/(const value_t &left, const table_slot_t &right) { return left / static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator%(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) % static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator%(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) % right; }
[[nodiscard]] inline value_t operator%(const value_t &left, const table_slot_t &right) { return left % static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator&(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) & static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator&(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) & right; }
[[nodiscard]] inline value_t operator&(const value_t &left, const table_slot_t &right) { return left & static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator|(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) | static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator|(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) | right; }
[[nodiscard]] inline value_t operator|(const value_t &left, const table_slot_t &right) { return left | static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator^(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) ^ static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator^(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) ^ right; }
[[nodiscard]] inline value_t operator^(const value_t &left, const table_slot_t &right) { return left ^ static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator<<(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) << static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator<<(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) << right; }
[[nodiscard]] inline value_t operator<<(const value_t &left, const table_slot_t &right) { return left << static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator>>(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) >> static_cast<value_t>(right); }
[[nodiscard]] inline value_t operator>>(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) >> right; }
[[nodiscard]] inline value_t operator>>(const value_t &left, const table_slot_t &right) { return left >> static_cast<value_t>(right); }

[[nodiscard]] inline bool_t operator==(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) == static_cast<value_t>(right); }
[[nodiscard]] inline bool_t operator==(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) == right; }
[[nodiscard]] inline bool_t operator==(const value_t &left, const table_slot_t &right) { return left == static_cast<value_t>(right); }
[[nodiscard]] inline bool_t operator!=(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) != static_cast<value_t>(right); }
[[nodiscard]] inline bool_t operator!=(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) != right; }
[[nodiscard]] inline bool_t operator!=(const value_t &left, const table_slot_t &right) { return left != static_cast<value_t>(right); }
[[nodiscard]] inline bool_t operator<(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) < static_cast<value_t>(right); }
[[nodiscard]] inline bool_t operator<(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) < right; }
[[nodiscard]] inline bool_t operator<(const value_t &left, const table_slot_t &right) { return left < static_cast<value_t>(right); }
[[nodiscard]] inline bool_t operator<=(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) <= static_cast<value_t>(right); }
[[nodiscard]] inline bool_t operator<=(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) <= right; }
[[nodiscard]] inline bool_t operator<=(const value_t &left, const table_slot_t &right) { return left <= static_cast<value_t>(right); }
[[nodiscard]] inline bool_t operator>(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) > static_cast<value_t>(right); }
[[nodiscard]] inline bool_t operator>(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) > right; }
[[nodiscard]] inline bool_t operator>(const value_t &left, const table_slot_t &right) { return left > static_cast<value_t>(right); }
[[nodiscard]] inline bool_t operator>=(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) >= static_cast<value_t>(right); }
[[nodiscard]] inline bool_t operator>=(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) >= right; }
[[nodiscard]] inline bool_t operator>=(const value_t &left, const table_slot_t &right) { return left >= static_cast<value_t>(right); }
[[nodiscard]] inline bool_t operator&&(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) && static_cast<value_t>(right); }
[[nodiscard]] inline bool_t operator&&(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) && right; }
[[nodiscard]] inline bool_t operator&&(const value_t &left, const table_slot_t &right) { return left && static_cast<value_t>(right); }
[[nodiscard]] inline bool_t operator||(const table_slot_t &left, const table_slot_t &right) { return static_cast<value_t>(left) || static_cast<value_t>(right); }
[[nodiscard]] inline bool_t operator||(const table_slot_t &left, const value_t &right) { return static_cast<value_t>(left) || right; }
[[nodiscard]] inline bool_t operator||(const value_t &left, const table_slot_t &right) { return left || static_cast<value_t>(right); }

template <typename T>
concept TableSlotScalarBridge =
    std::same_as<std::remove_cvref_t<T>, int_t>
    || std::same_as<std::remove_cvref_t<T>, float_t>
    || std::same_as<std::remove_cvref_t<T>, bool_t>
    || std::same_as<std::remove_cvref_t<T>, string_t>
    || std::same_as<std::remove_cvref_t<T>, ref_int_t>
    || std::same_as<std::remove_cvref_t<T>, ref_float_t>
    || std::same_as<std::remove_cvref_t<T>, ref_bool_t>
    || std::same_as<std::remove_cvref_t<T>, ref_string_t>;

// Bridge overloads for table_slot_t with typed scalar operands.
// Without these, expressions like table_slot_t + int_t can become ambiguous because
// table_slot_t can convert both to value_t and to concrete scalar references/values.
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator+(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) + value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator+(const T &left, const table_slot_t &right) { return value_t(left) + static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator-(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) - value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator-(const T &left, const table_slot_t &right) { return value_t(left) - static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator*(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) * value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator*(const T &left, const table_slot_t &right) { return value_t(left) * static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator/(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) / value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator/(const T &left, const table_slot_t &right) { return value_t(left) / static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator%(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) % value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator%(const T &left, const table_slot_t &right) { return value_t(left) % static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator&(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) & value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator&(const T &left, const table_slot_t &right) { return value_t(left) & static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator|(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) | value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator|(const T &left, const table_slot_t &right) { return value_t(left) | static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator^(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) ^ value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator^(const T &left, const table_slot_t &right) { return value_t(left) ^ static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator<<(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) << value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator<<(const T &left, const table_slot_t &right) { return value_t(left) << static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator>>(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) >> value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline value_t operator>>(const T &left, const table_slot_t &right) { return value_t(left) >> static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator==(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) == value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator==(const T &left, const table_slot_t &right) { return value_t(left) == static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator!=(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) != value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator!=(const T &left, const table_slot_t &right) { return value_t(left) != static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator<(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) < value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator<(const T &left, const table_slot_t &right) { return value_t(left) < static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator<=(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) <= value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator<=(const T &left, const table_slot_t &right) { return value_t(left) <= static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator>(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) > value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator>(const T &left, const table_slot_t &right) { return value_t(left) > static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator>=(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) >= value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator>=(const T &left, const table_slot_t &right) { return value_t(left) >= static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator&&(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) && value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator&&(const T &left, const table_slot_t &right) { return value_t(left) && static_cast<value_t>(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator||(const table_slot_t &left, const T &right) { return static_cast<value_t>(left) || value_t(right); }
template <TableSlotScalarBridge T>
[[nodiscard]] inline bool_t operator||(const T &left, const table_slot_t &right) { return value_t(left) || static_cast<value_t>(right); }

// ============================================================
// Free helpers - after table_t is fully defined
// ============================================================

[[nodiscard]] inline maybe_value_t table_find_(const table_t<value_t> &t, const int_t    &k) { return t.find(k); }
[[nodiscard]] inline maybe_value_t table_find_(const table_t<value_t> &t, const string_t &k) { return t.find(k); }

[[nodiscard]] inline maybe_value_t table_find_(const maybe_value_t &mv, const int_t &k) {
    if (!mv.has_value().native_value()) return maybe_value_t{nullopt};
    const auto *t = mv.value().table_if();
    return t ? t->find(k) : maybe_value_t{nullopt};
}
[[nodiscard]] inline maybe_value_t table_find_(const maybe_value_t &mv, const string_t &k) {
    if (!mv.has_value().native_value()) return maybe_value_t{nullopt};
    const auto *t = mv.value().table_if();
    return t ? t->find(k) : maybe_value_t{nullopt};
}

[[nodiscard]] inline value_t table_find_val_(const table_t<value_t> &t, const int_t    &k) { return t._find_val(k); }
[[nodiscard]] inline value_t table_find_val_(const table_t<value_t> &t, const string_t &k) { return t._find_val(k); }

[[nodiscard]] inline value_t table_find_val_(const maybe_value_t &mv, const int_t &k) {
    if (!mv.has_value().native_value()) return value_t{null_t{}};
    const auto *t = mv.value().table_if();
    return t ? t->_find_val(k) : value_t{null_t{}};
}
[[nodiscard]] inline value_t table_find_val_(const maybe_value_t &mv, const string_t &k) {
    if (!mv.has_value().native_value()) return value_t{null_t{}};
    const auto *t = mv.value().table_if();
    return t ? t->_find_val(k) : value_t{null_t{}};
}

[[nodiscard]] inline table_slot_t table_dim_(table_t<value_t> &t, const int_t &k) { return t[k]; }
[[nodiscard]] inline table_slot_t table_dim_(table_t<value_t> &t, const string_t &k) { return t[k]; }
[[nodiscard]] inline value_t table_dim_(const table_t<value_t> &t, const int_t &k) { return t[k]; }
[[nodiscard]] inline value_t table_dim_(const table_t<value_t> &t, const string_t &k) { return t[k]; }
[[nodiscard]] inline table_slot_t table_dim_(table_slot_t slot, const int_t &k) {
    return slot[k];
}
[[nodiscard]] inline table_slot_t table_dim_(table_slot_t slot, const string_t &k) {
    return slot[k];
}
[[nodiscard]] inline value_t table_dim_(const table_slot_t &slot, const int_t &k) {
    auto value = static_cast<value_t>(slot);
    return value._find_val(k);
}
[[nodiscard]] inline value_t table_dim_(const table_slot_t &slot, const string_t &k) {
    auto value = static_cast<value_t>(slot);
    return value._find_val(k);
}
[[nodiscard]] inline value_t table_dim_(const value_t &value, const int_t &k) { return value._find_val(k); }
[[nodiscard]] inline value_t table_dim_(const value_t &value, const string_t &k) { return value._find_val(k); }
[[nodiscard]] inline value_t table_dim_(const maybe_value_t &mv, const int_t &k) { return table_find_val_(mv, k); }
[[nodiscard]] inline value_t table_dim_(const maybe_value_t &mv, const string_t &k) { return table_find_val_(mv, k); }

[[nodiscard]] inline bool_t table_has_(const table_t<value_t> &t, const int_t    &k) { return t.has(k); }
[[nodiscard]] inline bool_t table_has_(const table_t<value_t> &t, const string_t &k) { return t.has(k); }

[[nodiscard]] inline bool_t table_has_(const maybe_value_t &mv, const int_t &k) {
    if (!mv.has_value().native_value()) return bool_t{false};
    const auto *t = mv.value().table_if();
    return t ? t->has(k) : bool_t{false};
}
[[nodiscard]] inline bool_t table_has_(const maybe_value_t &mv, const string_t &k) {
    if (!mv.has_value().native_value()) return bool_t{false};
    const auto *t = mv.value().table_if();
    return t ? t->has(k) : bool_t{false};
}

// ============================================================
// table_value_() - builder helpers
// ============================================================

[[nodiscard]] inline table_t<value_t> table_copy(const table_t<value_t> &table) { return table; }
[[nodiscard]] inline table_t<value_t> table_copy(const table_slot_t &slot) { return slot.table_copy(); }

[[nodiscard]] inline value_t table_value_(const value_t  &v) { return v.clone(); }
[[nodiscard]] inline value_t table_value_(const table_slot_t &v) { return static_cast<value_t>(v); }
[[nodiscard]] inline value_t table_value_(value_t       &&v) { return std::move(v); }
[[nodiscard]] inline value_t table_value_(null_t)            { return value_t{null_t{}}; }
[[nodiscard]] inline value_t table_value_(nullopt_t)         { return value_t{null_t{}}; }
[[nodiscard]] inline value_t table_value_(nullptr_t)         { return value_t{null_t{}}; }
[[nodiscard]] inline value_t table_value_(const bool_t   &v) { return value_t{v}; }
[[nodiscard]] inline value_t table_value_(const int_t    &v) { return value_t{v}; }
[[nodiscard]] inline value_t table_value_(const float_t  &v) { return value_t{v}; }
[[nodiscard]] inline value_t table_value_(const string_t &v) { return value_t{v}; }
[[nodiscard]] inline value_t table_value_(const char     *v) { return value_t{v}; }
[[nodiscard]] inline value_t table_value_(bool      v)       { return value_t{v}; }
[[nodiscard]] inline value_t table_value_(int       v)       { return value_t{static_cast<int64_t>(v)}; }
[[nodiscard]] inline value_t table_value_(long      v)       { return value_t{static_cast<int64_t>(v)}; }
[[nodiscard]] inline value_t table_value_(long long v)       { return value_t{static_cast<int64_t>(v)}; }
[[nodiscard]] inline value_t table_value_(double    v)       { return value_t{v}; }

[[nodiscard]] inline value_t table_value_(const table_t<value_t> &v) {
    return value_t{std::make_unique<table_t<value_t>>(v)};
}
[[nodiscard]] inline value_t table_value_(table_t<value_t> &&v) {
    return value_t{std::make_unique<table_t<value_t>>(std::move(v))};
}
[[nodiscard]] inline value_t table_value_(shared_p<table_t<value_t>> v) { return value_t{std::move(v)}; }
[[nodiscard]] inline value_t table_value_(std::shared_ptr<table_t<value_t>> v) { return value_t{shared_p<table_t<value_t>>(std::move(v))}; }
[[nodiscard]] inline value_t table_value_(weak_p<table_t<value_t>> v) { return value_t{std::move(v)}; }
[[nodiscard]] inline value_t table_value_(std::weak_ptr<table_t<value_t>> v) { return value_t{weak_p<table_t<value_t>>(std::move(v))}; }

// ============================================================
// table_build_item_t + table_new_()
// ============================================================

struct table_build_item_t final {
    std::variant<std::monostate, int_t, string_t> key;
    value_t value;
};

template <typename V>
[[nodiscard]] inline table_build_item_t table_item_(V &&v)
    { return {std::monostate{}, table_value_(std::forward<V>(v))}; }

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(const int_t &k, V &&v)
    { return {k, table_value_(std::forward<V>(v))}; }

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(int k, V &&v)
    { return table_kv_(int_t{static_cast<int64_t>(k)}, std::forward<V>(v)); }

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(const string_t &k, V &&v)
    { return {k, table_value_(std::forward<V>(v))}; }

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(const char *k, V &&v)
    { return table_kv_(string_t{k}, std::forward<V>(v)); }

inline void table_add_item_(table_t<value_t> &table, const table_build_item_t &item) {
    if (std::holds_alternative<std::monostate>(item.key)) { (void)table.append(item.value); return; }
    if (std::holds_alternative<int_t>(item.key))          { table.set(std::get<int_t>(item.key), item.value); return; }
    table.set(std::get<string_t>(item.key), item.value);
}

template <typename... Items>
[[nodiscard]] inline table_t<value_t> table_new_(Items &&...items) {
    table_t<value_t> t{};
    (table_add_item_(t, std::forward<Items>(items)), ...);
    return t;
}

} // namespace scpp
