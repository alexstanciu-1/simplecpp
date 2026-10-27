#pragma once

#include "scpp/error_value.hpp"
#include "scpp/error_t.hpp"
#include <new>
#include <type_traits>
#include <utility>

namespace scpp::detail
{
    // Only the compiled runtime bridge may borrow the real object representation.
    struct error_value_access final
    {
        static error_t &get(error_value &value) noexcept
        {
            return *std::launder(reinterpret_cast<error_t *>(value.storage_));
        }

        static const error_t &get(const error_value &value) noexcept
        {
            return *std::launder(reinterpret_cast<const error_t *>(value.storage_));
        }

        // Synchronous construction into final storage, with guaranteed prvalue elision.
        template<class Factory>
        static error_value make(const Factory &factory)
        {
            return error_value([](void *storage, const void *context) {
                ::new (storage) error_t((*static_cast<const Factory *>(context))());
            }, &factory);
        }
    };

    static_assert(sizeof(error_t) == SCPP_ERROR_VALUE_SIZE);
    static_assert(alignof(error_t) == SCPP_ERROR_VALUE_ALIGNMENT);
    static_assert(sizeof(error_value) == sizeof(error_t));
    static_assert(alignof(error_value) == alignof(error_t));
    static_assert(std::is_nothrow_move_constructible_v<error_t>);
    static_assert(std::is_nothrow_move_assignable_v<error_t>);
    static_assert(std::is_nothrow_destructible_v<error_t>);
}
