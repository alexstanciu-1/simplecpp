#pragma once

#include "scpp/string.hpp"

namespace scpp
{
    namespace detail { struct error_value_access; }

    class error_value final
    {
    private:
        alignas(SCPP_ERROR_VALUE_ALIGNMENT) unsigned char storage_[SCPP_ERROR_VALUE_SIZE];
        friend struct detail::error_value_access;
        error_value(void (*construct)(void *, const void *), const void *context);

    public:
        error_value();
        error_value(const error_value &other);
        error_value(error_value &&other) noexcept;
        error_value &operator=(const error_value &other);
        error_value &operator=(error_value &&other) noexcept;
        ~error_value() noexcept;

        static const error_value no_error;
        static error_value make(const string &message,
                                int64 line = 0, const string &file = string());
        boolean has_error() const noexcept;
        explicit operator bool() const noexcept;
        boolean operator!() const noexcept;
        string get_message() const;
        int64 get_line() const noexcept;
        string get_file() const;
    };
}
