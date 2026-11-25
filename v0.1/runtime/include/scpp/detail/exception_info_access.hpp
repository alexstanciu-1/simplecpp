#pragma once

#include "scpp/exception_info.hpp"
#include <exception>
#include <new>
#include <type_traits>
#include <utility>

namespace scpp::detail
{
    // Only the compiled runtime bridge may borrow the real object representation.
    struct exception_info_access final
    {
        static std::exception_ptr &get(exception_info &value) noexcept
        {
            return *std::launder(reinterpret_cast<std::exception_ptr *>(value.storage_));
        }

        static const std::exception_ptr &get(const exception_info &value) noexcept
        {
            return *std::launder(reinterpret_cast<const std::exception_ptr *>(value.storage_));
        }
    };

    static_assert(std::is_nothrow_copy_constructible_v<std::exception_ptr>);
    static_assert(std::is_nothrow_copy_assignable_v<std::exception_ptr>);
    static_assert(sizeof(std::exception_ptr) == SCPP_EXCEPTION_INFO_SIZE);
    static_assert(alignof(std::exception_ptr) == SCPP_EXCEPTION_INFO_ALIGNMENT);
    static_assert(sizeof(exception_info) == sizeof(std::exception_ptr));
    static_assert(alignof(exception_info) == alignof(std::exception_ptr));
    static_assert(std::is_nothrow_move_constructible_v<std::exception_ptr>);
    static_assert(std::is_nothrow_move_assignable_v<std::exception_ptr>);
    static_assert(std::is_nothrow_destructible_v<std::exception_ptr>);
}
