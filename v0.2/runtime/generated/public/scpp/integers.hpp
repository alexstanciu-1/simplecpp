// Generated from reviewed runtime metadata. Do not edit.
#pragma once
namespace scpp {
namespace detail { struct integer_access; }
class string;
class int32;
class int64;
class bool_t;
class float64;
class null_t;
class int32 final {
public:
    constexpr int32() noexcept = default;
    explicit int32(bool_t value) noexcept;
    explicit int32(float64 value);
    explicit int32(int value) noexcept;
    explicit int32(int64 value);
    explicit int32(long value);
    explicit int32(long long value);
    explicit int32(const string& value);
    explicit int32(unsigned int value);
    explicit int32(unsigned long value);
    explicit int32(unsigned long long value);
    explicit int32(short) = delete;
    explicit int32(unsigned short) = delete;
    explicit int32(bool) = delete;
    explicit int32(char) = delete;
    explicit int32(signed char) = delete;
    explicit int32(unsigned char) = delete;
    explicit int32(wchar_t) = delete;
    explicit int32(char8_t) = delete;
    explicit int32(char16_t) = delete;
    explicit int32(char32_t) = delete;
    explicit int32(float) = delete;
    explicit int32(double) = delete;
    explicit int32(long double) = delete;
#ifdef __SIZEOF_INT128__
    explicit int32(__int128) = delete;
    explicit int32(unsigned __int128) = delete;
#endif
    constexpr __INT32_TYPE__ native_value() const noexcept { return value_; }
private:
    friend struct detail::integer_access;
    __INT32_TYPE__ value_ = 0;
};
class int64 final {
public:
    constexpr int64() noexcept = default;
    explicit int64(bool_t value) noexcept;
    explicit int64(float64 value);
    explicit int64(int value) noexcept;
    explicit int64(int32 value) noexcept;
    explicit int64(long value) noexcept;
    explicit int64(long long value) noexcept;
    explicit int64(const string& value);
    explicit int64(unsigned int value) noexcept;
    explicit int64(unsigned long value);
    explicit int64(unsigned long long value);
    explicit int64(short) = delete;
    explicit int64(unsigned short) = delete;
    explicit int64(bool) = delete;
    explicit int64(char) = delete;
    explicit int64(signed char) = delete;
    explicit int64(unsigned char) = delete;
    explicit int64(wchar_t) = delete;
    explicit int64(char8_t) = delete;
    explicit int64(char16_t) = delete;
    explicit int64(char32_t) = delete;
    explicit int64(float) = delete;
    explicit int64(double) = delete;
    explicit int64(long double) = delete;
#ifdef __SIZEOF_INT128__
    explicit int64(__int128) = delete;
    explicit int64(unsigned __int128) = delete;
#endif
    constexpr __INT64_TYPE__ native_value() const noexcept { return value_; }
private:
    friend struct detail::integer_access;
    __INT64_TYPE__ value_ = 0;
};
using int_t = int64;
}
