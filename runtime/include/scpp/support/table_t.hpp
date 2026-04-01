#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/value_t.hpp"

#include <cstdint>
#include <limits>
#include <memory>
#include <stdexcept>
#include <unordered_map>
#include <utility>
#include <variant>
#include <vector>

namespace scpp {

class value_t;

// ============================================================
// TableValue concept + whitelist
// ============================================================

template <typename T> struct is_table_value : std::false_type {};

template <> struct is_table_value<null_t>   : std::true_type {};
template <> struct is_table_value<bool_t>   : std::true_type {};
template <> struct is_table_value<int_t>    : std::true_type {};
template <> struct is_table_value<float_t>  : std::true_type {};
template <> struct is_table_value<string_t> : std::true_type {};
template <> struct is_table_value<value_t>  : std::true_type {};
template <typename T> struct is_table_value<std::shared_ptr<table_t<T>>> : std::true_type {};
template <typename T> struct is_table_value<std::unique_ptr<table_t<T>>> : std::true_type {};
template <typename T> struct is_table_value<std::weak_ptr<table_t<T>>>   : std::true_type {};

template <typename T>
concept TableValue = is_table_value<T>::value;

// ============================================================
// maybe_value_t  (find() result type)
// ============================================================

using maybe_value_t = nullable<value_t>;

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

    static bool php_target_parse_non_negative_int_key(const std::string &text, std::uint32_t &out) {
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

    static std::uint32_t make_string_key(const string_t &k) {
        std::uint32_t normalized_int_key = 0;
        if (php_target_parse_non_negative_int_key(k.native_value(), normalized_int_key)) {
            return normalized_int_key;
        }
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

    [[nodiscard]] bool_t      empty() const noexcept {
        if (std::holds_alternative<std::monostate>(keys_)) return bool_t{values_.empty()};
        for (const auto k : std::get<native_keys_t>(keys_))
            if (k != TOMBSTONE_KEY) return bool_t{false};
        return bool_t{true};
    }
    [[nodiscard]] std::size_t size() const noexcept {
        if (std::holds_alternative<std::monostate>(keys_)) return values_.size();
        std::size_t n = 0;
        for (const auto k : std::get<native_keys_t>(keys_)) if (k != TOMBSTONE_KEY) ++n;
        return n;
    }
    [[nodiscard]] bool_t      is_packed() const noexcept {
        return bool_t{std::holds_alternative<std::monostate>(keys_)};
    }

    void clear() noexcept { values_.clear(); keys_ = std::monostate{}; hash_index_ = std::monostate{}; }

    // --------------------------------------------------------
    // append  — PHP-style push (next integer key)
    // --------------------------------------------------------
    [[nodiscard]] int_t append(const T_VALUE &v) requires std::copyable<T_VALUE> {
        const auto k = next_append_key(); insert_or_assign_int(k, T_VALUE{v});
        return int_t{static_cast<int64_t>(k)};
    }
    [[nodiscard]] int_t append(T_VALUE &&v) {
        const auto k = next_append_key(); insert_or_assign_int(k, std::move(v));
        return int_t{static_cast<int64_t>(k)};
    }

    // --------------------------------------------------------
    // set  — explicit insert/assign
    // --------------------------------------------------------
    table_t &set(const int_t    &key, const T_VALUE &v) requires std::copyable<T_VALUE>
        { insert_or_assign_int(static_cast<std::uint32_t>(key.native_value()), T_VALUE{v}); return *this; }
    table_t &set(const string_t &key, const T_VALUE &v) requires std::copyable<T_VALUE>
        { insert_or_assign_int(make_string_key(key), T_VALUE{v}); return *this; }
    table_t &set(const int_t    &key, T_VALUE &&v)
        { insert_or_assign_int(static_cast<std::uint32_t>(key.native_value()), std::move(v)); return *this; }
    table_t &set(const string_t &key, T_VALUE &&v)
        { insert_or_assign_int(make_string_key(key), std::move(v)); return *this; }

    // --------------------------------------------------------
    // has  — existence check
    // --------------------------------------------------------
    [[nodiscard]] bool_t has(const int_t    &key) const
        { return bool_t{find_int(static_cast<std::uint32_t>(key.native_value())).first}; }
    [[nodiscard]] bool_t has(const string_t &key) const
        { return bool_t{find_int(make_string_key(key)).first}; }

    // --------------------------------------------------------
    // find  — returns nullable<T_VALUE> (no autovivification)
    // --------------------------------------------------------
    [[nodiscard]] nullable<T_VALUE> find(const int_t &key) const requires std::copyable<T_VALUE> {
        auto [f, p] = find_int(static_cast<std::uint32_t>(key.native_value()));
        if (!f) return nullable<T_VALUE>(nullopt); nullable<T_VALUE> r; r.native_value() = *p; return r;
    }
    [[nodiscard]] nullable<T_VALUE> find(const string_t &key) const requires std::copyable<T_VALUE> {
        auto [f, p] = find_int(make_string_key(key));
        if (!f) return nullable<T_VALUE>(nullopt); nullable<T_VALUE> r; r.native_value() = *p; return r;
    }

    // --------------------------------------------------------
    // _find_val  — returns value_t by value (null if missing)
    //              only available for table_t<value_t>
    // --------------------------------------------------------
    [[nodiscard]] value_t _find_val(const int_t &key) const requires std::same_as<T_VALUE, value_t>;
    [[nodiscard]] value_t _find_val(const string_t &key) const requires std::same_as<T_VALUE, value_t>;

    // --------------------------------------------------------
    // at  — throws if key absent
    // --------------------------------------------------------
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

    // --------------------------------------------------------
    // remove
    // --------------------------------------------------------
    [[nodiscard]] bool remove(const int_t    &key) { return erase_int(static_cast<std::uint32_t>(key.native_value())); }
    [[nodiscard]] bool remove(const string_t &key) { return erase_int(make_string_key(key)); }

    // --------------------------------------------------------
    // operator[]  — autovivifying, returns T_VALUE& directly.
    //               No table_slot_t proxy.  Works for all T_VALUE.
    //
    //  Mutable:  insert null slot if absent, return ref.
    //  Const:    return ref to static null if absent (no insert).
    // --------------------------------------------------------
    T_VALUE &operator[](const int_t &key) {
        const auto k = static_cast<std::uint32_t>(key.native_value());
        auto [f, p] = find_int(k);
        if (!f) { insert_or_assign_int(k, T_VALUE{}); return *find_int(k).second; }
        return *p;
    }
    T_VALUE &operator[](const string_t &key) {
        const auto k = make_string_key(key);
        auto [f, p] = find_int(k);
        if (!f) { insert_or_assign_int(k, T_VALUE{}); return *find_int(k).second; }
        return *p;
    }

    const T_VALUE &operator[](const int_t &key) const {
        auto [f, p] = find_int(static_cast<std::uint32_t>(key.native_value()));
        if (!f) { static const T_VALUE null_val{}; return null_val; }
        return *p;
    }
    const T_VALUE &operator[](const string_t &key) const {
        auto [f, p] = find_int(make_string_key(key));
        if (!f) { static const T_VALUE null_val{}; return null_val; }
        return *p;
    }

    // --------------------------------------------------------
    // debug_visit_entries
    // --------------------------------------------------------
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
};

template <>
[[nodiscard]] value_t table_t<value_t>::_find_val(const int_t &key) const;

template <>
[[nodiscard]] value_t table_t<value_t>::_find_val(const string_t &key) const;

// ============================================================
// Ergonomic helpers for generated literals
// ============================================================

struct table_build_item_t final {
	std::variant<std::monostate, int_t, string_t> key;
	value_t value;
};

template <typename V>
[[nodiscard]] inline table_build_item_t table_item_(V &&value) {
	return {std::monostate{}, value_t(std::forward<V>(value))};
}

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(const int_t &key, V &&value) {
	return {key, value_t(std::forward<V>(value))};
}

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(int key, V &&value) {
	return table_kv_(int_t{static_cast<std::int64_t>(key)}, std::forward<V>(value));
}

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(const string_t &key, V &&value) {
	return {key, value_t(std::forward<V>(value))};
}

template <typename V>
[[nodiscard]] inline table_build_item_t table_kv_(const char *key, V &&value) {
	return table_kv_(string_t{key}, std::forward<V>(value));
}

inline void table_add_item_(table_t<value_t> &table, const table_build_item_t &item) {
	if (std::holds_alternative<std::monostate>(item.key)) {
		(void) table.append(item.value);
		return;
	}
	if (std::holds_alternative<int_t>(item.key)) {
		table.set(std::get<int_t>(item.key), item.value);
		return;
	}
	table.set(std::get<string_t>(item.key), item.value);
}

template <typename... TArgs>
[[nodiscard]] inline shared_p<table_t<value_t>> shared_table_(TArgs &&...args) {
	auto table = shared_p<table_t<value_t>>(std::make_shared<table_t<value_t>>());
	(table_add_item_(*table, std::forward<TArgs>(args)), ...);
	return table;
}

template <typename... TArgs>
[[nodiscard]] inline unique_p<table_t<value_t>> table_(TArgs &&...args) {
	auto table = unique_p<table_t<value_t>>(std::make_unique<table_t<value_t>>());
	(table_add_item_(*table, std::forward<TArgs>(args)), ...);
	return table;
}

} // namespace scpp
