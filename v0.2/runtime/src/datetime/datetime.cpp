#include "datetime_native.hpp"
#include "datetime_semantics.hpp"
#include <ctime>
#include <charconv>
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
enum class time_basis { utc, local };
bool host_calendar(std::time_t native, time_basis basis, std::tm& result) noexcept {
#if defined(_WIN32)
    return (basis == time_basis::utc ? gmtime_s(&result, &native) : localtime_s(&result, &native)) == 0;
#else
    return (basis == time_basis::utc ? gmtime_r(&native, &result) : localtime_r(&native, &result)) != nullptr;
#endif
}
std::tm calendar_from_unix_seconds(std::int64_t seconds, time_basis basis) {
    if (!std::in_range<std::time_t>(seconds))
        throw std::runtime_error("datetime value cannot be represented as host time");
    std::tm result{};
    if (!host_calendar(static_cast<std::time_t>(seconds), basis, result))
        throw std::runtime_error("datetime value cannot be represented as host calendar time");
    return result;
}
struct calendar_fields {
    int year=0, month=0, day=0, hour=0, minute=0, second=0;
};
// Both syntax owners validate their separators/length before calling this helper.
const char* parse_calendar(std::string_view text, bool date_only, calendar_fields& fields) noexcept {
    if (!parse_fixed_uint(text,0,4,fields.year) || !parse_fixed_uint(text,5,2,fields.month) ||
        !parse_fixed_uint(text,8,2,fields.day) || (!date_only &&
        (!parse_fixed_uint(text,11,2,fields.hour) || !parse_fixed_uint(text,14,2,fields.minute) ||
         !parse_fixed_uint(text,17,2,fields.second))))
        return "datetime value contains a non-numeric component";
    if (fields.month < 1 || fields.month > 12) return "datetime month is out of range";
    if (fields.day < 1 || fields.day > days_in_month(fields.year,fields.month)) return "datetime day is out of range";
    if (fields.hour > 23 || fields.minute > 59 || fields.second > 59) return "datetime time component is out of range";
    return nullptr;
}
bool same_calendar(const std::tm& left, const std::tm& right) noexcept {
    return left.tm_year==right.tm_year && left.tm_mon==right.tm_mon && left.tm_mday==right.tm_mday &&
        left.tm_hour==right.tm_hour && left.tm_min==right.tm_min && left.tm_sec==right.tm_sec;
}
parse_result local_timestamp(const calendar_fields& fields) noexcept {
    std::tm normalized{};
    normalized.tm_year=fields.year-1900; normalized.tm_mon=fields.month-1; normalized.tm_mday=fields.day;
    normalized.tm_hour=fields.hour; normalized.tm_min=fields.minute; normalized.tm_sec=fields.second;
    normalized.tm_isdst=-1; // The host owns gap normalization and overlap selection.
    const auto timestamp=std::mktime(&normalized);
    if (timestamp == static_cast<std::time_t>(-1)) {
        std::tm roundtrip{};
        // -1 is also a real epoch value. Compare with the host-normalized fields,
        // not the original request (which may have been in a DST gap).
        if (!host_calendar(timestamp,time_basis::local,roundtrip) || !same_calendar(normalized,roundtrip))
            return {0,"datetime value cannot be represented as local time"};
    }
    if (!std::in_range<std::int64_t>(timestamp)) return {0,"datetime timestamp is outside int64 range"};
    return {static_cast<std::int64_t>(timestamp),nullptr};
}
void append_number(std::string& out, std::int64_t value, std::size_t width=0) {
    char buffer[32];
    const auto converted=std::to_chars(buffer,buffer+sizeof(buffer),value);
    const auto count=static_cast<std::size_t>(converted.ptr-buffer);
    if (count < width) out.append(width-count,'0');
    out.append(buffer,count);
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
    const auto tm = calendar_from_unix_seconds(seconds,time_basis::utc);
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
    calendar_fields fields;
    if (const auto error=parse_calendar(text,false,fields)) return {0,error};
    const std::chrono::year_month_day ymd{std::chrono::year{fields.year},
        std::chrono::month{static_cast<unsigned>(fields.month)}, std::chrono::day{static_cast<unsigned>(fields.day)}};
    // The fixed four-digit year bounds the day/second arithmetic within int64.
    const auto days = std::chrono::sys_days{ymd}.time_since_epoch().count();
    return {static_cast<std::int64_t>(days)*86400 + fields.hour*3600 + fields.minute*60 + fields.second,nullptr};
}
std::string format_local(std::string_view pattern, std::int64_t seconds) {
    const auto tm=calendar_from_unix_seconds(seconds,time_basis::local);
    const auto year=static_cast<std::int64_t>(tm.tm_year)+1900;
    std::string out;
    out.reserve(pattern.size());
    bool escaped=false;
    for (const char token : pattern) {
        if (escaped) { out.push_back(token); escaped=false; continue; }
        if (token == '\\') { escaped=true; continue; }
        switch (token) {
            case 'Y': append_number(out,year,4); break;
            case 'y': append_number(out,year%100,2); break;
            case 'm': append_number(out,tm.tm_mon+1,2); break;
            case 'n': append_number(out,tm.tm_mon+1); break;
            case 'd': append_number(out,tm.tm_mday,2); break;
            case 'j': append_number(out,tm.tm_mday); break;
            case 'H': append_number(out,tm.tm_hour,2); break;
            case 'G': append_number(out,tm.tm_hour); break;
            case 'i': append_number(out,tm.tm_min,2); break;
            case 's': append_number(out,tm.tm_sec,2); break;
            case 'U': append_number(out,seconds); break;
            default: out.push_back(token); break;
        }
    }
    if (escaped) out.push_back('\\');
    return out;
}
std::string format_local_now(std::string_view pattern) {
    return format_local(pattern,now_unix_seconds());
}
parse_result parse_common_local(std::string_view text) noexcept {
    if (text.size()==20 && text[19]=='Z') return parse_iso_utc(text);
    const bool date_only=text.size()==10;
    if ((!date_only && text.size()!=19) || text[4]!='-' || text[7]!='-' ||
        (!date_only && ((text[10]!=' ' && text[10]!='T') || text[13]!=':' || text[16]!=':')))
        return {0,"unsupported datetime format"};
    calendar_fields fields;
    if (const auto error=parse_calendar(text,date_only,fields)) return {0,error};
    return local_timestamp(fields);
}
}
