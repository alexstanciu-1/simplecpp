// PHP date tokens, using timelib's UTC proleptic Gregorian calendar.
#include "datetime_native.hpp"
#include "timelib/timelib.h"
#include <charconv>

namespace scpp::detail::datetime {
namespace {
constexpr std::string_view weekdays[]{"Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"};
constexpr std::string_view months[]{"January","February","March","April","May","June","July","August","September","October","November","December"};

void number(std::string& out, long long value, int width = 0) {
    char buffer[32];
    const auto end = std::to_chars(buffer, buffer + sizeof(buffer), value).ptr;
    const char* digits = buffer;
    const auto padding = width - (end - digits);
    if (*digits == '-') { out += '-'; ++digits; }
    if (padding > 0) out.append(padding, '0');
    out.append(digits, static_cast<std::size_t>(end - digits));
}

void format(std::string& out, std::string_view pattern, const timelib_time& t) {
    for (std::size_t i = 0; i < pattern.size(); ++i) {
        const char token = pattern[i];
        switch (token) {
        case 'D': case 'l': {
            const auto name = weekdays[timelib_day_of_week(t.y, t.m, t.d)];
            out += token == 'D' ? name.substr(0, 3) : name;
            break;
        }
        case 'd': case 'j': number(out, t.d, token == 'd' ? 2 : 0); break;
        case 'S':
            if (t.d < 11 || t.d > 13) {
                switch (t.d % 10) {
                case 1: out += "st"; break;
                case 2: out += "nd"; break;
                case 3: out += "rd"; break;
                default: out += "th"; break;
                }
            } else out += "th";
            break;
        case 'w': number(out, timelib_day_of_week(t.y, t.m, t.d)); break;
        case 'N': number(out, timelib_iso_day_of_week(t.y, t.m, t.d)); break;
        case 'z': number(out, timelib_day_of_year(t.y, t.m, t.d)); break;
        case 'W': case 'o': {
            timelib_sll week, year;
            timelib_isoweek_from_date(t.y, t.m, t.d, &week, &year);
            if (token == 'W') number(out, week, 2);
            else number(out, year);
            break;
        }
        case 'F': case 'M': {
            const auto name = months[t.m - 1];
            out += token == 'M' ? name.substr(0, 3) : name;
            break;
        }
        case 'm': case 'n': number(out, t.m, token == 'm' ? 2 : 0); break;
        case 't': number(out, timelib_days_in_month(t.y, t.m)); break;
        // PHP narrows the year for this token, even for very large timestamps.
        case 'L': number(out, timelib_days_in_month(static_cast<int>(t.y), 2) - 28); break;
        case 'y': number(out, t.y % 100, 2); break;
        case 'Y': case 'x': case 'X':
            if (t.y < 0) out += '-';
            else if (token == 'X' || (token == 'x' && t.y >= 10000)) out += '+';
            number(out, t.y < 0 ? -t.y : t.y, 4);
            break;
        case 'a': out += t.h < 12 ? "am" : "pm"; break;
        case 'A': out += t.h < 12 ? "AM" : "PM"; break;
        case 'B': number(out, ((t.sse % 86400 + 90000) % 86400) * 10 / 864, 3); break;
        case 'g': case 'h': number(out, (t.h + 11) % 12 + 1, token == 'h' ? 2 : 0); break;
        case 'G': case 'H': number(out, t.h, token == 'H' ? 2 : 0); break;
        case 'i': number(out, t.i, 2); break;
        case 's': number(out, t.s, 2); break;
        case 'u': out += "000000"; break;
        case 'v': out += "000"; break;
        case 'I': case 'Z': out += '0'; break;
        case 'e': case 'T': out += "UTC"; break;
        case 'O': out += "+0000"; break;
        case 'P': out += "+00:00"; break;
        case 'p': out += 'Z'; break;
        case 'c':
            number(out, t.y, 4);
            format(out, "-m-d\\TH:i:sP", t);
            break;
        case 'r':
            format(out, "D, d M ", t);
            number(out, t.y, 4);
            format(out, " H:i:s O", t);
            break;
        case 'U': number(out, t.sse); break;
        case '\\': out += ++i < pattern.size() ? pattern[i] : '\0'; break;
        default: out += token;
        }
    }
}
}

std::string php_date(std::string_view pattern, std::int64_t seconds) {
    timelib_time calendar{};
    timelib_unixtime2gmt(&calendar, seconds);
    std::string out;
    out.reserve(pattern.size());
    format(out, pattern, calendar);
    return out;
}
}
