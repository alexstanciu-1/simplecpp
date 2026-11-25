#pragma once

#include "scpp/string.hpp"

namespace scpp
{
    namespace detail { struct exception_info_access; }

    class exception_info final
    {
    private:
        alignas(SCPP_EXCEPTION_INFO_ALIGNMENT) unsigned char storage_[SCPP_EXCEPTION_INFO_SIZE];
        friend struct detail::exception_info_access;
        friend exception_info capture_runtime_exception();
        exception_info(void (*construct)(void *, const void *), const void *context);

    public:
        exception_info(const exception_info &other) noexcept;
        exception_info &operator=(const exception_info &other) noexcept;
        ~exception_info() noexcept;

        string message() const;
        string code() const;
        string component() const;
        string operation() const;
        int64 detail_count() const;
        string detail_key(int64 index) const;
        string detail_value(int64 index) const;
        int64 trace_count() const;
        string trace_module(int64 index) const;
        string trace_address(int64 index) const;
        [[noreturn]] void rethrow() const;
    };
    exception_info capture_runtime_exception();
}
