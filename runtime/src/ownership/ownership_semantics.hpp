#pragma once
#include <memory>
#include <new>
#include <stdexcept>
#include <type_traits>
#include <utility>

namespace scpp::detail {
// Only this private helper accesses generated opaque storage. No byte copying of
// live smart pointers: every wrapper starts and ends the native object's lifetime.
struct ownership_access {
    template<class Native, class Owner, class... Args>
    static void construct(Owner &owner, Args &&...args) noexcept(std::is_nothrow_constructible_v<Native, Args...>) {
        static_assert(sizeof(Native) == sizeof(owner.storage_));
        static_assert(alignof(Owner) == alignof(Native));
        std::construct_at(reinterpret_cast<Native *>(owner.storage_), std::forward<Args>(args)...);
    }
    template<class Native, class Owner> static Native &get(Owner &owner) noexcept {
        return *std::launder(reinterpret_cast<Native *>(owner.storage_));
    }
    template<class Native, class Owner> static const Native &get(const Owner &owner) noexcept {
        return *std::launder(reinterpret_cast<const Native *>(owner.storage_));
    }
    template<class Native, class Owner> static void destroy(Owner &owner) noexcept {
        std::destroy_at(&get<Native>(owner));
    }
    template<class Native> static auto &checked(const Native &owner) {
        if (!owner) throw std::runtime_error("ownership_empty_access");
        return *owner;
    }
};
}
