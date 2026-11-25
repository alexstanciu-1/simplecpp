#pragma once
#include <cstddef>
#include <cstdint>
#include <limits>
#include <new>
#include <stdexcept>

namespace scpp_provider {
// An explicit allocation obligation, separate from the lifetime of this descriptor.
// The compiler proves release on normal exits. Failure across the bridge is fatal.
struct allocation {
    void* address = nullptr;
    std::size_t bytes = 0;
    std::size_t alignment = 0;
    allocation() = default;
    allocation(const allocation&) = delete;
    allocation& operator=(const allocation&) = delete;
};

inline void allocation_acquire(allocation& owner, std::int64_t bytes, std::int64_t alignment) {
    if (owner.address || bytes <= 0 || alignment <= 0
        || static_cast<std::uint64_t>(bytes) > std::numeric_limits<std::size_t>::max()
        || static_cast<std::uint64_t>(alignment) > std::numeric_limits<std::size_t>::max())
        throw std::invalid_argument("invalid allocation size, alignment or nonempty owner");
    auto align = static_cast<std::size_t>(alignment);
    if ((align & (align - 1)) != 0)
        throw std::invalid_argument("allocation alignment must be a power of two");
    // Aligned allocation and its matching release are both native provider policy.
    if (align < alignof(std::max_align_t)) align = alignof(std::max_align_t);
    owner.address = ::operator new(static_cast<std::size_t>(bytes), std::align_val_t(align));
    owner.bytes = static_cast<std::size_t>(bytes);
    owner.alignment = align;
}

inline void allocation_release(allocation& owner) noexcept {
    if (owner.address) ::operator delete(owner.address, std::align_val_t(owner.alignment));
    owner.address = nullptr;
    owner.bytes = 0;
    owner.alignment = 0;
}

inline void allocation_transfer(allocation& source, allocation& destination) {
    if (!source.address || destination.address || &source == &destination)
        throw std::invalid_argument("allocation transfer requires owned source and empty destination");
    destination.address = source.address;
    destination.bytes = source.bytes;
    destination.alignment = source.alignment;
    source.address = nullptr;
    source.bytes = 0;
    source.alignment = 0;
}

inline std::int64_t allocation_size(const allocation& owner) {
    if (!owner.address) throw std::invalid_argument("allocation inspection requires owned storage");
    return static_cast<std::int64_t>(owner.bytes);
}
}
