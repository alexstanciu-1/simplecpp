// Generated from reviewed runtime metadata. Do not edit.
#pragma once
#include "scpp/integers.hpp"
#include "scpp/values.hpp"
namespace scpp {
namespace detail { struct string_access; }
class string final {
public:
    string() noexcept = default;
    ~string();
    string(const string&);
    string(string&&) noexcept;
    string& operator=(const string&);
    string& operator=(string&&) noexcept;
    explicit string(const char*);
    explicit string(const char*, int64);
    explicit string(int) = delete;
    explicit string(unsigned int) = delete;
    explicit string(long) = delete;
    explicit string(unsigned long) = delete;
    explicit string(long long) = delete;
    explicit string(unsigned long long) = delete;
    explicit string(short) = delete;
    explicit string(unsigned short) = delete;
    explicit string(bool) = delete;
    explicit string(char) = delete;
    explicit string(signed char) = delete;
    explicit string(unsigned char) = delete;
    explicit string(wchar_t) = delete;
    explicit string(char8_t) = delete;
    explicit string(char16_t) = delete;
    explicit string(char32_t) = delete;
    explicit string(float) = delete;
    explicit string(double) = delete;
    explicit string(long double) = delete;
#ifdef __SIZEOF_INT128__
    explicit string(__int128) = delete;
    explicit string(unsigned __int128) = delete;
#endif
    explicit string(bool_t value);
    explicit string(float64 value);
    explicit string(int32 value);
    explicit string(int64 value);
    int64 size() const;
    bool_t empty() const noexcept;
    const char* data() const noexcept;
    void append(const string&);
    void clear() noexcept;
private:
    struct storage;
    storage* storage_ = nullptr;
    friend struct detail::string_access;
};
string operator+(string left, const string& right);
string& operator+=(string& left, const string& right);
bool_t operator==(const string& left, const string& right) noexcept;
bool_t operator>(const string& left, const string& right) noexcept;
bool_t operator>=(const string& left, const string& right) noexcept;
bool_t operator<(const string& left, const string& right) noexcept;
bool_t operator<=(const string& left, const string& right) noexcept;
bool_t operator!=(const string& left, const string& right) noexcept;
}
