#pragma once
#include <new>
#include <utility>
#include <limits>
#include "scpp/integers.hpp"
#include "scpp/failure.hpp"

namespace scpp::detail
{
    // Only generated compiled adapters borrow the actual inline native object.
    struct concrete_access final
    {
        template<class Native, class Public>
        static Native &get(Public &value) noexcept
        {
            return *std::launder(reinterpret_cast<Native *>(value.storage_));
        }
        template<class Native, class Public>
        static const Native &get(const Public &value) noexcept
        {
            return *std::launder(reinterpret_cast<const Native *>(value.storage_));
        }
        template<class Public, class Native, class Factory>
        static Public make(const Factory &factory)
        {
            return Public([](void *storage, const void *context) {
                ::new (storage) Native((*static_cast<const Factory *>(context))());
            }, &factory);
        }
    };

    inline std::size_t concrete_size(uint64 value)
    {
        if (value.native_value() > std::numeric_limits<std::size_t>::max()) {
            raise_runtime_error("Size exceeds host range", "size_out_of_range", "core", "collection");
        }
        return static_cast<std::size_t>(value.native_value());
    }
}
