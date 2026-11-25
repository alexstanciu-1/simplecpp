#pragma once

#include "scpp/integers.hpp"
#include "detail/value_layout.hpp"

namespace scpp
{
    namespace detail { struct string_access; }

    class string final
    {
    private:
        alignas(SCPP_STRING_ALIGNMENT) unsigned char storage_[SCPP_STRING_SIZE];
        friend struct detail::string_access;
        string(void (*construct)(void *, const void *), const void *context);

    public:
        string();
        explicit string(const char *text);
        string(const char *bytes, uint64 length);
        string(const string &other);
        string(string &&other) noexcept;
        string &operator=(const string &other);
        string &operator=(string &&other) noexcept;
        ~string() noexcept;

        uint64 size() const noexcept;
        uint64 byte_size() const noexcept;
        uint64 byte_capacity() const noexcept;
        uint64 estimated_storage_bytes() const noexcept;
        boolean empty() const noexcept;
        uint32 byte_at(uint64 index) const;
        boolean is_valid_utf8() const;
        uint64 length_cp() const;
        string slice(uint64 start, uint64 end_exclusive) const;
        string substr_cp(uint64 start, uint64 length) const;
        string substr_cp(uint64 start) const;

        boolean equals(const string &other) const;
        boolean equals(const char *text) const;
        boolean is_one_of(const char *vocabulary) const;
        void append(const string &other);
        void swap(string &other) noexcept;
        void clear() noexcept;
        void _unset_() noexcept;
    };
    string operator+(const string &left, const string &right);
    string &operator+=(string &left, const string &right);
    boolean operator==(const string &left, const string &right);
    boolean operator!=(const string &left, const string &right);
    boolean operator<(const string &left, const string &right);
    boolean operator<=(const string &left, const string &right);
    boolean operator>(const string &left, const string &right);
    boolean operator>=(const string &left, const string &right);
    uint64 strlen(const string &value);
    boolean empty(const string &value);
    boolean isset(const string &value);
}
