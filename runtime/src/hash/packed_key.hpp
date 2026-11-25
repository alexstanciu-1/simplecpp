#pragma once
#include <cstdint>
#include <cstddef>
#include <limits>
#include <optional>
#include <stdexcept>
#include <string>
#include <string_view>
#include <unordered_map>
#include <vector>

namespace scpp::detail {
// Physical identity only. Source-language key normalization belongs to its adapter.
struct packed_key final {
    static constexpr std::uint32_t string_flag = 0x80000000u;
    static constexpr std::uint32_t max_integer = 0x7fffffffu;
    static constexpr std::uint32_t tombstone = 0xffffffffu;
    static std::uint32_t integer(std::int64_t value) {
        if (value < 0 || value > max_integer) throw std::out_of_range("packed_integer_key_limit");
        return static_cast<std::uint32_t>(value);
    }
    static bool is_string(std::uint32_t code) noexcept { return (code & string_flag) != 0; }
    static std::uint32_t string_id(std::size_t size, std::size_t limit = max_integer) {
        if (size >= limit || size >= max_integer) throw std::overflow_error("packed_string_pool_limit");
        return static_cast<std::uint32_t>(size) | string_flag;
    }
};

// Map-owned strings keep stable addresses across rehash. The reverse vector owns
// no strings; copying/moving this owner is forbidden. IDs last for the pool lifetime.
class packed_string_pool final {
    struct byte_hash {
        using is_transparent = void;
        std::size_t operator()(std::string_view bytes) const noexcept {
            return std::hash<std::string_view>{}(bytes);
        }
    };
    std::unordered_map<std::string, std::uint32_t, byte_hash, std::equal_to<>> lookup_;
    std::vector<const std::string*> strings_;
    std::size_t limit_;
public:
    // Smaller limits permit deterministic exhaustion proofs without huge allocations.
    explicit packed_string_pool(std::size_t limit = packed_key::max_integer) : limit_(limit) {
        lookup_.max_load_factor(0.4f);
    }
    packed_string_pool(const packed_string_pool&) = delete;
    packed_string_pool& operator=(const packed_string_pool&) = delete;
    static packed_string_pool& instance();
    std::optional<std::uint32_t> find(std::string_view value) const {
        auto it = lookup_.find(value);
        if (it == lookup_.end()) return std::nullopt;
        return it->second;
    }
    std::uint32_t intern(std::string_view value) {
        if (auto code = find(value)) return *code;
        const auto code = packed_key::string_id(strings_.size(), limit_);
        auto [it, inserted] = lookup_.emplace(std::string(value), code);
        if (!inserted) return it->second;
        try { strings_.push_back(&it->first); }
        catch (...) { lookup_.erase(it); throw; }
        return code;
    }
    std::string_view resolve(std::uint32_t code) const {
        if (!packed_key::is_string(code) || code == packed_key::tombstone)
            throw std::out_of_range("packed_string_identity");
        const auto id = code & packed_key::max_integer;
        if (id >= strings_.size()) throw std::out_of_range("packed_string_identity");
        return *strings_[id];
    }
    std::size_t size() const noexcept { return strings_.size(); }
};
} // namespace scpp::detail
