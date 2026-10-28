// Generated from reviewed runtime metadata. Do not edit.
#pragma once
namespace scpp {
class int32;
class int64;
class bool_t;
class float64;
class null_t;
class bool_t final {
public:
    constexpr bool_t() noexcept = default;
    explicit bool_t(float64 value) noexcept;
    explicit bool_t(int32 value) noexcept;
    explicit bool_t(int64 value) noexcept;
    explicit constexpr bool_t(bool value) noexcept : value_(value) {}
    explicit bool_t(int) = delete;
    explicit bool_t(unsigned int) = delete;
    explicit bool_t(long) = delete;
    explicit bool_t(unsigned long) = delete;
    explicit bool_t(long long) = delete;
    explicit bool_t(unsigned long long) = delete;
    explicit bool_t(short) = delete;
    explicit bool_t(unsigned short) = delete;
    explicit bool_t(char) = delete;
    explicit bool_t(signed char) = delete;
    explicit bool_t(unsigned char) = delete;
    explicit bool_t(wchar_t) = delete;
    explicit bool_t(char8_t) = delete;
    explicit bool_t(char16_t) = delete;
    explicit bool_t(char32_t) = delete;
    explicit bool_t(float) = delete;
    explicit bool_t(double) = delete;
    explicit bool_t(long double) = delete;
#ifdef __SIZEOF_INT128__
    explicit bool_t(__int128) = delete;
    explicit bool_t(unsigned __int128) = delete;
#endif
    explicit bool_t(const void*) = delete;
    explicit constexpr operator bool() const noexcept { return value_; }
    constexpr bool native_value() const noexcept { return value_; }
private:
    bool value_ = false;
};
class float64 final {
public:
    constexpr float64() noexcept = default;
    explicit float64(bool_t value) noexcept;
    explicit float64(double value) noexcept;
    explicit float64(float value) noexcept;
    explicit float64(int value) noexcept;
    explicit float64(int32 value) noexcept;
    explicit float64(int64 value) noexcept;
    explicit float64(long value) noexcept;
    explicit float64(long long value) noexcept;
    explicit float64(unsigned int value) noexcept;
    explicit float64(unsigned long value) noexcept;
    explicit float64(unsigned long long value) noexcept;
    explicit float64(short) = delete;
    explicit float64(unsigned short) = delete;
    explicit float64(bool) = delete;
    explicit float64(char) = delete;
    explicit float64(signed char) = delete;
    explicit float64(unsigned char) = delete;
    explicit float64(wchar_t) = delete;
    explicit float64(char8_t) = delete;
    explicit float64(char16_t) = delete;
    explicit float64(char32_t) = delete;
    explicit float64(long double) = delete;
#ifdef __SIZEOF_INT128__
    explicit float64(__int128) = delete;
    explicit float64(unsigned __int128) = delete;
#endif
    constexpr double native_value() const noexcept { return value_; }
private:
    double value_ = 0.0;
};
class null_t final {
public:
    constexpr null_t() noexcept = default;
};
constexpr bool_t operator==(bool_t left, bool_t right) noexcept { return bool_t{left.native_value() == right.native_value()}; }
constexpr bool_t operator!(bool_t value) noexcept { return bool_t{!value.native_value()}; }
constexpr bool_t operator!=(bool_t left, bool_t right) noexcept { return bool_t{left.native_value() != right.native_value()}; }
inline constexpr null_t null{};
constexpr bool_t operator==(null_t, null_t) noexcept { return bool_t{true}; }
constexpr bool_t operator!=(null_t, null_t) noexcept { return bool_t{false}; }
using float_t = float64;
}
