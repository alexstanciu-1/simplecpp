#pragma once

#include "scpp/string.hpp"
#include "scpp/string_t.hpp"
#include <new>
#include <type_traits>
#include <utility>

namespace scpp::detail
{
    // Only the compiled runtime bridge may borrow the real object representation.
    struct string_access final
    {
        static string_t &get(string &value) noexcept
        {
            return *std::launder(reinterpret_cast<string_t *>(value.storage_));
        }

        static const string_t &get(const string &value) noexcept
        {
            return *std::launder(reinterpret_cast<const string_t *>(value.storage_));
        }

        // Synchronous construction into final storage, with guaranteed prvalue elision.
        template<class Factory>
        static string make(const Factory &factory)
        {
            return string([](void *storage, const void *context) {
                ::new (storage) string_t((*static_cast<const Factory *>(context))());
            }, &factory);
        }
    };

    static_assert(sizeof(string_t) == SCPP_STRING_SIZE);
    static_assert(alignof(string_t) == SCPP_STRING_ALIGNMENT);
    static_assert(sizeof(string) == sizeof(string_t));
    static_assert(alignof(string) == alignof(string_t));
    static_assert(std::is_nothrow_move_constructible_v<string_t>);
    static_assert(std::is_nothrow_move_assignable_v<string_t>);
    static_assert(std::is_nothrow_destructible_v<string_t>);
}
