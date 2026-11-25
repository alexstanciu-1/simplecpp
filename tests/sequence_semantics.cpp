#include "sequence_semantics.hpp"
#include <cstdint>
#include <limits>
#include <vector>

namespace {
struct comma_decimal : std::numpunct<char> {
    char do_decimal_point() const override { return ','; }
};
void require(bool ok) {
    if (!ok) throw std::runtime_error("Sequence boundary proof failed");
}
template<class Error, class F> void fails(F action) {
    try { action(); } catch (const Error &) { return; }
    throw std::runtime_error("Expected sequence boundary failure");
}
}
int main() {
    using namespace scpp::detail;
    const char bytes[] = {'A', '\0', 'B'};
    const auto text = string_from_bytes(bytes, std::int64_t{3});
    require(text.size() == 3 && text[1] == '\0' && text[2] == 'B');
    require(string_from_bytes(bytes, 0).empty());
    require(string_from_c_string("Ada") == "Ada");
    fails<std::invalid_argument>([] { string_from_c_string(nullptr); });
    fails<std::invalid_argument>([] { string_from_bytes(nullptr, 0); });
    fails<std::out_of_range>([&] { string_from_bytes(bytes, -1); });
    fails<std::out_of_range>([] { byte_count(std::numeric_limits<std::int64_t>::min()); });
    require(sequence_index(0, 1) == 0 && sequence_index(2, 3) == 2);
    fails<std::out_of_range>([] { sequence_index(-1, 3); });
    fails<std::out_of_range>([] { sequence_index(0, 0); });
    fails<std::out_of_range>([] { sequence_index(3, 3); });
    fails<std::out_of_range>([] { sequence_index(std::numeric_limits<std::uint64_t>::max(), 3); });
    // The chosen standard storage preserves byte strings and value copies. No
    // UTF-8 dependency, iterator snapshots or legacy wrapper ownership is needed.
    auto independent = text;
    independent += independent;
    require(text.size() == 3 && independent == text + text);
    std::vector<std::string> names{string_from_c_string("Ada")};
    auto copy = names;
    copy[sequence_index(0, copy.size())] = "Grace";
    require(names[0] == "Ada" && copy[0] == "Grace");
    require(parse_decimal<std::int8_t>("-128") == -128);
    require(parse_decimal<std::uint64_t>("18446744073709551615") == std::numeric_limits<std::uint64_t>::max());
    require(format_decimal(std::numeric_limits<std::int64_t>::min()) == "-9223372036854775808");
    for (const auto &bad : {"", " 1", "1 ", "+1", "0x10", "1.2"})
        fails<std::invalid_argument>([&] { parse_decimal<std::int64_t>(bad); });
    fails<std::invalid_argument>([] { parse_decimal<std::int64_t>(std::string("1\0", 2)); });
    fails<std::invalid_argument>([] { parse_decimal<std::uint64_t>("-1"); });
    fails<std::out_of_range>([] { parse_decimal<std::int8_t>("128"); });
    fails<std::out_of_range>([] { parse_decimal<std::uint64_t>("18446744073709551616"); });
    require(parse_decimal<double>("1.25e2") == 125.0);
    require(format_decimal(1.25) == "1.25");
    const auto previous_locale = std::locale::global(std::locale(std::locale::classic(), new comma_decimal));
    require(parse_decimal<double>("1.25") == 1.25 && format_decimal(1.25) == "1.25");
    fails<std::invalid_argument>([] { parse_decimal<double>("1,25"); });
    std::locale::global(previous_locale);
    for (double number : {0.1, -0.0, std::numeric_limits<double>::denorm_min(),
                          std::numeric_limits<double>::min(), std::numeric_limits<double>::max()}) {
        const auto parsed = parse_decimal<double>(format_decimal(number));
        require(parsed == number && std::signbit(parsed) == std::signbit(number));
    }
    for (const auto &bad : {"nan", "inf", "-inf", "+1.0", " 1.0", "1.0 ", "1e", "0x1p2"})
        fails<std::invalid_argument>([&] { parse_decimal<double>(bad); });
    for (const auto &bad : {"1e999", "1e-999"})
        fails<std::out_of_range>([&] { parse_decimal<double>(bad); });
    fails<std::out_of_range>([] { format_decimal(std::numeric_limits<double>::infinity()); });
    require(parse_boolean_text("true") && !parse_boolean_text("false"));
    require(format_boolean_text(true) == "true" && format_boolean_text(false) == "false");
    for (const auto &bad : {"", "0", "1", "TRUE", " false"})
        fails<std::invalid_argument>([&] { parse_boolean_text(bad); });
}
