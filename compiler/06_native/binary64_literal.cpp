#include "binary64_literal.hpp"

#include <bit>
#include <cfenv>
#include <charconv>
#include <cmath>
#include <cstdint>
#include <cstdlib>
#include <limits>
#include <locale.h>
#include <stdexcept>

namespace {
// strtod_l also accepts hexadecimal, whitespace and nonfinite spellings. Admit only
// the Simple C++ floating token grammar, with its optional source-level unary sign.
bool decimal_spelling(const std::string& text) {
    std::size_t i = 0;
    auto sign = [&] {
        if (i < text.size() && (text[i] == '+' || text[i] == '-')) ++i;
    };
    auto digits = [&] {
        const auto start = i;
        while (i < text.size() && text[i] >= '0' && text[i] <= '9') ++i;
        return i != start;
    };
    sign();
    bool mantissa = digits();
    const bool point = i < text.size() && text[i] == '.';
    if (point) {
        ++i;
        if (digits()) mantissa = true;
    }
    const bool exponent = i < text.size() && (text[i] == 'e' || text[i] == 'E');
    if (exponent) {
        ++i;
        sign();
        if (!digits()) return false;
    }
    return mantissa && (point || exponent) && i == text.size();
}

struct NumericLocale {
    locale_t value = newlocale(LC_NUMERIC_MASK, "C", nullptr);
    NumericLocale() {
        if (!value) throw std::runtime_error("Cannot create compiler numeric locale");
    }
    ~NumericLocale() { freelocale(value); }
};
}

namespace scpp::compiler {
double parse_decimal_literal(const std::string& spelling) {
    if (!decimal_spelling(spelling)) throw std::invalid_argument("Expected decimal floating literal");
    static const NumericLocale locale;
    // Keep rounding and exception flags local to this conversion on the calling thread.
    std::fenv_t previous;
    if (std::feholdexcept(&previous) != 0) throw std::runtime_error("Cannot save floating environment");
    const bool nearest = std::fesetround(FE_TONEAREST) == 0;
    char* end = nullptr;
    const double value = nearest ? strtod_l(spelling.c_str(), &end, locale.value) : 0.0;
    if (std::fesetenv(&previous) != 0 || !nearest) throw std::runtime_error("Cannot set compiler rounding mode");
    if (end != spelling.data() + spelling.size()) throw std::invalid_argument("Expected decimal floating literal");
    // ERANGE also covers valid subnormals and signed underflow to zero.
    if (!std::isfinite(value)) throw std::out_of_range("Floating literal is outside float64 finite range");
    return value;
}

static_assert(sizeof(double) == 8 && std::numeric_limits<double>::is_iec559);

std::string cpp_binary64_literal(double value) {
    if (!std::isfinite(value)) throw std::domain_error("nonfinite");
    const auto bits = std::bit_cast<std::uint64_t>(value);
    const auto exponent = static_cast<int>((bits >> 52) & 0x7ff);
    std::string text = bits >> 63 ? "-0x" : "0x";
    text += exponent ? "1." : "0.";
    constexpr char hex[] = "0123456789abcdef";
    for (int shift = 48; shift >= 0; shift -= 4) text += hex[(bits >> shift) & 15];
    text += 'p';
    char power[8];
    const auto end = std::to_chars(power, power + sizeof(power), exponent ? exponent - 1023 : -1022);
    if (end.ec != std::errc{}) throw std::logic_error("binary64 exponent out of range");
    return text.append(power, end.ptr);
}
}
