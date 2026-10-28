#pragma once
#include <cstddef>
#include <charconv>
#include <cmath>
#include <limits>
#include <locale>
#include <sstream>
#include <stdexcept>
#include <string>
#include <type_traits>
#include <utility>

namespace scpp::detail {
// Keep signed indices intact until both bounds have been checked. This is shared
// by generated concrete sequences; it is not a source-level indexing protocol.
template<class Integer>
std::size_t sequence_index(Integer index, std::size_t size) {
    static_assert(std::is_integral_v<Integer>);
    if (std::cmp_less(index, 0) || std::cmp_greater_equal(index, size))
        throw std::out_of_range("sequence_index_out_of_range");
    return static_cast<std::size_t>(index);
}

template<class Integer>
std::size_t byte_count(Integer count) {
    static_assert(std::is_integral_v<Integer>);
    if (!std::in_range<std::size_t>(count))
        throw std::out_of_range("string_length_out_of_range");
    return static_cast<std::size_t>(count);
}

inline std::string string_from_c_string(const char *text) {
    if (!text) throw std::invalid_argument("null_string_pointer");
    return std::string(text);
}

template<class Integer>
std::string string_from_bytes(const char *bytes, Integer count) {
    // Null is not an empty-string spelling, even for zero length. A default
    // constructed string supplies the empty value without pointer ambiguity.
    if (!bytes) throw std::invalid_argument("null_string_pointer");
    return std::string(bytes, byte_count(count));
}

// Locale-independent explicit text conversion. These are private algorithms;
// metadata selects which concrete constructor signatures publish them.
inline bool decimal_floating_syntax(const std::string &text) {
    std::size_t i = 0;
    if (i < text.size() && text[i] == '-') ++i;
    const auto digits = [&] {
        const auto start = i;
        while (i < text.size() && text[i] >= '0' && text[i] <= '9') ++i;
        return i != start;
    };
    bool has_digits = digits();
    if (i < text.size() && text[i] == '.') { ++i; has_digits = digits() || has_digits; }
    if (!has_digits) return false;
    if (i < text.size() && (text[i] == 'e' || text[i] == 'E')) {
        ++i;
        if (i < text.size() && (text[i] == '+' || text[i] == '-')) ++i;
        if (!digits()) return false;
    }
    return i == text.size();
}

template<class Number>
Number parse_decimal(const std::string &text) {
    static_assert(std::is_integral_v<Number> || std::is_floating_point_v<Number>);
    static_assert(!std::is_same_v<Number, bool>);
    Number result{};
    const auto first = text.data(), last = first + text.size();
    if constexpr (std::is_floating_point_v<Number>) {
        if (!decimal_floating_syntax(text)) throw std::invalid_argument("invalid_decimal_text");
    }
    if constexpr (requires { std::from_chars(first, last, result); }) {
        const auto parsed = std::from_chars(first, last, result);
        if (parsed.ec == std::errc::result_out_of_range)
            throw std::out_of_range("text_number_out_of_range");
        if (parsed.ec != std::errc{} || parsed.ptr != last)
            throw std::invalid_argument("invalid_decimal_text");
    } else {
        // libc++ 18 lacks floating from_chars. Use its standard numeric parser
        // with the classic locale, after validating the same decimal grammar.
        std::istringstream input(text);
        input.imbue(std::locale::classic());
        input >> std::noskipws >> result;
        // libc++ marks representable subnormal results as ERANGE/failbit too.
        // Preserve them; zero underflow and overflow remain failures.
        const bool subnormal = std::fpclassify(result) == FP_SUBNORMAL;
        if ((input.fail() && !subnormal) || !input.eof())
            throw std::out_of_range("text_number_out_of_range");
        if (result == 0) {
            for (char ch : text) {
                if (ch == 'e' || ch == 'E') break;
                if (ch >= '1' && ch <= '9') throw std::out_of_range("text_number_out_of_range");
            }
        }
    }
    if constexpr (std::is_floating_point_v<Number>) {
        if (!std::isfinite(result)) throw std::out_of_range("nonfinite_decimal_text");
    }
    return result;
}

template<class Number>
std::string format_decimal(Number number) {
    static_assert(std::is_integral_v<Number> || std::is_floating_point_v<Number>);
    static_assert(!std::is_same_v<Number, bool>);
    if constexpr (std::is_floating_point_v<Number>) {
        if (!std::isfinite(number)) throw std::out_of_range("nonfinite_decimal_text");
    }
    // Integral decimal digits and shortest round-trip floating forms fit this
    // bound for the fixed-width integers and binary64 registered by metadata.
    char buffer[128];
    const auto formatted = std::to_chars(buffer, buffer + sizeof(buffer), number);
    if (formatted.ec != std::errc{}) throw std::length_error("decimal_text_too_long");
    return std::string(buffer, formatted.ptr);
}

inline bool parse_boolean_text(const std::string &text) {
    if (text == "true") return true;
    if (text == "false") return false;
    throw std::invalid_argument("invalid_boolean_text");
}
inline std::string format_boolean_text(bool value) {
    return value ? "true" : "false";
}
}
