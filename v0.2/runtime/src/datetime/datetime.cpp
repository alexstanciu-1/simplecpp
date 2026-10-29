#include "datetime_native.hpp"
#include "datetime_semantics.hpp"
#include <ctime>
#include <thread>
#include <utility>

namespace scpp::detail::datetime {
namespace {
using seconds_t = std::chrono::duration<std::int64_t>;
using millis_t = std::chrono::duration<std::int64_t, std::milli>;
using micros_t = std::chrono::duration<std::uint64_t, std::micro>;
using nanos_t = std::chrono::duration<std::uint64_t, std::nano>;

// Donor's exact-width ASCII parser and calendar validation, now borrowing bytes.
bool parse_fixed_uint(std::string_view text, std::size_t offset, std::size_t width, int& out) noexcept {
    if (offset > text.size() || width > text.size() - offset) return false;
    int value = 0;
    for (std::size_t i = 0; i < width; ++i) {
        const char ch = text[offset + i];
        if (ch < '0' || ch > '9') return false;
        value = value * 10 + ch - '0';
    }
    out = value;
    return true;
}
bool is_leap_year(int year) noexcept {
    return year % 400 == 0 || (year % 4 == 0 && year % 100 != 0);
}
int days_in_month(int year, int month) noexcept {
    switch (month) {
        case 2: return is_leap_year(year) ? 29 : 28;
        case 4: case 6: case 9: case 11: return 30;
        default: return month >= 1 && month <= 12 ? 31 : 0;
    }
}
std::tm utc_tm_from_unix_seconds(std::int64_t seconds) {
    if (!std::in_range<std::time_t>(seconds))
        throw std::runtime_error("datetime value cannot be represented as host time");
    const auto native = static_cast<std::time_t>(seconds);
    std::tm result{};
#if defined(_WIN32)
    if (gmtime_s(&result, &native) != 0)
#else
    if (gmtime_r(&native, &result) == nullptr)
#endif
        throw std::runtime_error("datetime value cannot be represented as UTC time");
    return result;
}
void write_digits(std::array<char,20>& out, std::size_t offset, unsigned value, unsigned width) noexcept {
    while (width != 0) {
        --width;
        out[offset + width] = static_cast<char>('0' + value % 10);
        value /= 10;
    }
}
}
std::int64_t now_unix_seconds() {
    return checked_duration_count<seconds_t>(std::chrono::system_clock::now().time_since_epoch());
}
std::int64_t now_unix_millis() {
    return checked_duration_count<millis_t>(std::chrono::system_clock::now().time_since_epoch());
}
std::int64_t monotonic_millis() {
    return checked_duration_count<millis_t>(std::chrono::steady_clock::now().time_since_epoch());
}
std::uint64_t monotonic_micros() {
    return checked_duration_count<micros_t>(std::chrono::steady_clock::now().time_since_epoch());
}
std::uint64_t monotonic_nanos() {
    return checked_duration_count<nanos_t>(std::chrono::steady_clock::now().time_since_epoch());
}
void sleep_millis(std::int64_t millis) {
    while (millis > 0) {
        const auto chunk = sleep_chunk_millis(millis);
        // At most one day, safely representable even as signed nanoseconds.
        std::this_thread::sleep_for(std::chrono::milliseconds{chunk});
        millis -= chunk;
    }
}
std::array<char,20> format_iso_utc(std::int64_t seconds) {
    // Proleptic Gregorian year 0000 through 9999; guard before host conversion.
    if (seconds < -62167219200LL || seconds > 253402300799LL)
        throw std::runtime_error("datetime UTC year must be in 0000..9999");
    const auto tm = utc_tm_from_unix_seconds(seconds);
    if (tm.tm_year < -1900 || tm.tm_year > 8099 || tm.tm_mon < 0 || tm.tm_mon > 11 ||
        tm.tm_mday < 1 || tm.tm_mday > 31 || tm.tm_hour < 0 || tm.tm_hour > 23 ||
        tm.tm_min < 0 || tm.tm_min > 59 || tm.tm_sec < 0 || tm.tm_sec > 59)
        throw std::runtime_error("datetime host UTC components are outside fixed format");
    std::array<char,20> out{'0','0','0','0','-','0','0','-','0','0','T','0','0',':','0','0',':','0','0','Z'};
    write_digits(out,0,static_cast<unsigned>(tm.tm_year+1900),4);
    write_digits(out,5,static_cast<unsigned>(tm.tm_mon+1),2);
    write_digits(out,8,static_cast<unsigned>(tm.tm_mday),2);
    write_digits(out,11,static_cast<unsigned>(tm.tm_hour),2);
    write_digits(out,14,static_cast<unsigned>(tm.tm_min),2);
    write_digits(out,17,static_cast<unsigned>(tm.tm_sec),2);
    return out;
}
parse_result parse_iso_utc(std::string_view text) noexcept {
    if (text.size() != 20 || text[4]!='-' || text[7]!='-' || text[10]!='T' ||
        text[13]!=':' || text[16]!=':' || text[19]!='Z')
        return {0,"expected ISO-8601 UTC value in YYYY-MM-DDTHH:MM:SSZ form"};
    int year=0,month=0,day=0,hour=0,minute=0,second=0;
    if (!parse_fixed_uint(text,0,4,year) || !parse_fixed_uint(text,5,2,month) ||
        !parse_fixed_uint(text,8,2,day) || !parse_fixed_uint(text,11,2,hour) ||
        !parse_fixed_uint(text,14,2,minute) || !parse_fixed_uint(text,17,2,second))
        return {0,"ISO-8601 UTC value contains a non-numeric component"};
    if (month < 1 || month > 12) return {0,"ISO-8601 UTC month is out of range"};
    if (day < 1 || day > days_in_month(year,month)) return {0,"ISO-8601 UTC day is out of range"};
    if (hour > 23 || minute > 59 || second > 59) return {0,"ISO-8601 UTC time component is out of range"};
    const std::chrono::year_month_day ymd{std::chrono::year{year},
        std::chrono::month{static_cast<unsigned>(month)}, std::chrono::day{static_cast<unsigned>(day)}};
    // The fixed four-digit year bounds the day/second arithmetic within int64.
    const auto days = std::chrono::sys_days{ymd}.time_since_epoch().count();
    return {static_cast<std::int64_t>(days)*86400 + hour*3600 + minute*60 + second,nullptr};
}
}
