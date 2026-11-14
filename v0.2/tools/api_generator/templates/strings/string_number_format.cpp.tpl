namespace detail {
// The input is an unsigned fixed-point decimal. Round the digits before inserting literal separators.
static string format_number_digits(std::string digits, bool negative, int64 decimals, const string& point, const string& group) {
    const auto dot = digits.find('.');
    const int64 whole = static_cast<int64>(dot == std::string::npos ? digits.size() : dot);
    if (dot != std::string::npos) digits.erase(dot, 1);
    const int64 fraction = static_cast<int64>(digits.size()) - whole;
    if (decimals < -whole) {
        digits = "0";
    } else if (decimals < fraction) {
        const auto keep = static_cast<std::size_t>(whole + decimals);
        const bool up = digits[keep] >= '5';
        digits.resize(keep);
        if (up) {
            auto last = digits.find_last_not_of('9');
            if (last == std::string::npos) {
                digits.assign(keep + 1, '0');
                digits.front() = '1';
            } else {
                ++digits[last];
                std::fill(digits.begin() + last + 1, digits.end(), '0');
            }
        }
        if (decimals < 0) {
            if (digits.find_first_not_of('0') == std::string::npos) digits = "0";
            else digits.append(static_cast<std::size_t>(-decimals), '0');
        }
    }
    const auto precision = static_cast<std::uint64_t>(std::max<int64>(0, decimals));
    const auto significant_fraction = static_cast<std::size_t>(std::clamp<int64>(decimals, 0, fraction));
    const auto integral = digits.size() - significant_fraction;
    negative = negative && digits.find_first_not_of('0') != std::string::npos;
    const auto separator = string_access::read(group);
    const auto decimal = string_access::read(point);
    std::string out;
    const auto limit = std::min<std::uint64_t>(out.max_size(), std::numeric_limits<int64>::max());
    std::uint64_t size = integral + negative;
    const auto account = [&](std::uint64_t count, std::uint64_t width) {
        if (width && count > (limit - size) / width) throw std::length_error("number_format_too_large");
        size += count * width;
    };
    account(precision, 1);
    account(precision != 0, decimal.size());
    account((integral - 1) / 3, separator.size());
    out.reserve(static_cast<std::size_t>(size));
    if (negative) out.push_back('-');
    for (std::size_t i = 0; i < integral; ++i) {
        if (i && (integral - i) % 3 == 0) out += separator;
        out.push_back(digits[i]);
    }
    if (precision) {
        out += decimal;
        out.append(digits, integral, significant_fraction);
        out.append(static_cast<std::size_t>(precision - significant_fraction), '0');
    }
    return string_access::own(std::move(out));
}
}
namespace str {
string @name@(int64 value, int64 decimals, const string& point, const string& group) {
    auto digits = std::to_string(value);
    if (value < 0) digits.erase(0, 1);
    return detail::format_number_digits(std::move(digits), value < 0, decimals, point, group);
}
string @name@(float64 value, int64 decimals, const string& point, const string& group) {
    if (!std::isfinite(value)) return std::isnan(value) ? string{"nan", 3} : string{"inf", 3};
    const auto magnitude = std::fabs(value);
    // A binary64 has at most 309 integer and 1074 fractional decimal digits.
    char buffer[1400];
    auto formatted = std::to_chars(buffer, buffer + sizeof(buffer), magnitude, std::chars_format::fixed);
    const auto dot = std::find(buffer, formatted.ptr, '.');
    const auto fraction = dot == formatted.ptr ? 0 : formatted.ptr - dot - 1;
    // At visible decimal ties round the shortest fixed spelling. Beyond that spelling,
    // use its exact terminating decimal so the shared half-away rule rounds only once.
    if (decimals > fraction && magnitude != std::trunc(magnitude)) {
        int exponent;
        std::frexp(magnitude, &exponent);
        const int exact_places = std::min(1074, std::numeric_limits<float64>::digits - exponent);
        formatted = std::to_chars(buffer, buffer + sizeof(buffer), magnitude, std::chars_format::fixed, exact_places);
    }
    if (formatted.ec != std::errc{}) throw std::length_error("number_format_too_large");
    return detail::format_number_digits({buffer, formatted.ptr}, value < 0, decimals, point, group);
}
}
