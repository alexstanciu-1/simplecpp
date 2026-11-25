#pragma once
#include <cstddef>
#include <cstdint>

namespace scpp::detail {
// Type-independent index mechanics. Typed recipes own key equality and entry lifetimes.
struct hash_candidate {
    std::uint32_t position;
    std::uint32_t bucket;
};
class concrete_hash_index final {
    unsigned char* controls_ = nullptr;
    void* buckets_ = nullptr;
    std::uint32_t capacity_ = 0;
    std::uint32_t size_ = 0;
    std::uint32_t deleted_ = 0;
    unsigned char width_ = 0;
public:
    concrete_hash_index() noexcept = default;
    concrete_hash_index(std::uint32_t capacity, std::size_t physical_count);
    concrete_hash_index(const concrete_hash_index&) = delete;
    concrete_hash_index& operator=(const concrete_hash_index&) = delete;
    ~concrete_hash_index();
    void swap(concrete_hash_index& other) noexcept;
    std::uint32_t capacity() const noexcept { return capacity_; }
    std::uint32_t size() const noexcept { return size_; }
    std::uint32_t deleted() const noexcept { return deleted_; }
    unsigned index_bytes() const noexcept { return width_; }
    bool holds(std::size_t physical_count) const noexcept {
        return width_ == 4 || (width_ == 2 && physical_count <= 65536) || (width_ == 1 && physical_count <= 256);
    }
    hash_candidate next(std::uint64_t hash, std::uint32_t& offset) const noexcept;
    void add(std::uint64_t hash, std::uint32_t position) noexcept;
    void erase(std::uint32_t bucket) noexcept;
};
std::uint64_t hash_pointer(const void* key) noexcept;
std::uint64_t hash_integer(std::int64_t key) noexcept;
std::uint64_t hash_string_bytes(const char* bytes, std::size_t size) noexcept;
}
