// Included once by generated hash.cpp. Native-width buckets need no typed table instantiations.
#include "concrete_index.hpp"
#include "hash_index.hpp"
#include <cstring>
#include <functional>
#include <string_view>
#include <stdexcept>
#include <utility>

namespace scpp::detail {
concrete_hash_index::concrete_hash_index(std::uint32_t capacity, std::size_t physical_count)
    : capacity_(capacity), width_(physical_count <= 256 ? 1 : physical_count <= 65536 ? 2 : 4) {
    if (capacity > static_cast<std::size_t>(PTRDIFF_MAX) / width_) throw std::length_error("hash_index_capacity_limit");
    controls_ = new unsigned char[capacity];
    try { buckets_ = ::operator new(static_cast<std::size_t>(capacity) * width_); }
    catch (...) { delete[] controls_; throw; }
    std::memset(controls_, static_cast<unsigned char>(hash_detail::ctrl_state_t::empty), capacity);
}
concrete_hash_index::~concrete_hash_index() { delete[] controls_; ::operator delete(buckets_); }
void concrete_hash_index::swap(concrete_hash_index& other) noexcept {
    std::swap(controls_, other.controls_); std::swap(buckets_, other.buckets_);
    std::swap(capacity_, other.capacity_); std::swap(size_, other.size_);
    std::swap(deleted_, other.deleted_); std::swap(width_, other.width_);
}
hash_candidate concrete_hash_index::next(std::uint64_t hash, std::uint32_t& offset) const noexcept {
    if (!capacity_) return {UINT32_MAX, 0};
    auto bucket = static_cast<std::uint32_t>((hash % capacity_ + offset) % capacity_);
    while (offset < capacity_) {
        ++offset;
        const auto control = controls_[bucket];
        if (control == static_cast<unsigned char>(hash_detail::ctrl_state_t::empty)) { offset = capacity_; break; }
        if (control == (hash & 0x7F)) {
            const std::uint32_t position = width_ == 1 ? static_cast<const std::uint8_t*>(buckets_)[bucket]
                : width_ == 2 ? static_cast<const std::uint16_t*>(buckets_)[bucket] : static_cast<const std::uint32_t*>(buckets_)[bucket];
            return {position, bucket};
        }
        if (++bucket == capacity_) bucket = 0;
    }
    return {UINT32_MAX, 0};
}
void concrete_hash_index::add(std::uint64_t hash, std::uint32_t position) noexcept {
    auto bucket = static_cast<std::uint32_t>(hash % capacity_);
    while (controls_[bucket] < 128) { if (++bucket == capacity_) bucket = 0; }
    if (controls_[bucket] == static_cast<unsigned char>(hash_detail::ctrl_state_t::deleted)) --deleted_;
    controls_[bucket] = static_cast<unsigned char>(hash & 0x7F);
    if (width_ == 1) static_cast<std::uint8_t*>(buckets_)[bucket] = static_cast<std::uint8_t>(position);
    else if (width_ == 2) static_cast<std::uint16_t*>(buckets_)[bucket] = static_cast<std::uint16_t>(position);
    else static_cast<std::uint32_t*>(buckets_)[bucket] = position;
    ++size_;
}
void concrete_hash_index::erase(std::uint32_t bucket) noexcept {
    controls_[bucket] = static_cast<unsigned char>(hash_detail::ctrl_state_t::deleted);
    --size_; ++deleted_;
}
std::uint64_t hash_integer(std::int64_t key) noexcept { return hash_detail::mix64(static_cast<std::uint64_t>(key)); }
std::uint64_t hash_string_bytes(const char* bytes, std::size_t size) noexcept {
    return hash_detail::mix64(static_cast<std::uint64_t>(std::hash<std::string_view>{}({bytes, size})));
}
}
