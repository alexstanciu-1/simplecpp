#include "regex_provider.hpp"

#define PCRE2_CODE_UNIT_WIDTH 8
#include <pcre2.h>
#include <memory>
#include <string_view>

namespace {
using namespace std::literals;

struct pattern_body {
    std::string_view bytes;
    uint32_t options = 0;
};

bool parse(std::string_view pattern, pattern_body& body) {
    if (pattern.size() < 2) return false;
    const char open = pattern.front();
    if (open == '\0' || open == '\\' || " \t\r\n\f\v"sv.find(open) != std::string_view::npos ||
        (open >= 'a' && open <= 'z') || (open >= 'A' && open <= 'Z') ||
        (open >= '0' && open <= '9')) return false;
    const auto paired = "([{<"sv.find(open);
    const char close = paired == std::string_view::npos ? open : ")]}>"[paired];
    std::size_t depth = 1;
    for (std::size_t i = 1; i < pattern.size(); ++i) {
        const char c = pattern[i];
        if (c == '\\') { ++i; continue; }
        if (open != close && c == open) ++depth;
        if (c != close || --depth != 0) continue;
        body.bytes = pattern.substr(1, i - 1);
        for (char modifier : pattern.substr(i + 1)) {
            switch (modifier) {
            case 'i': body.options |= PCRE2_CASELESS; break;
            case 'm': body.options |= PCRE2_MULTILINE; break;
            case 's': body.options |= PCRE2_DOTALL; break;
            case 'u': body.options |= PCRE2_UTF | PCRE2_UCP; break;
            case 'x': body.options |= PCRE2_EXTENDED; break;
            case 'A': body.options |= PCRE2_ANCHORED; break;
            case 'D': body.options |= PCRE2_DOLLAR_ENDONLY; break;
            case 'U': body.options |= PCRE2_UNGREEDY; break;
            default: return false;
            }
        }
        return true;
    }
    return false;
}

enum class outcome { match, miss, invalid_pattern, engine_error };

outcome search(const scpp::string& pattern, const scpp::string& subject) {
    pattern_body body;
    if (!parse({pattern.data(), static_cast<std::size_t>(pattern.size())}, body))
        return outcome::invalid_pattern;
    int error;
    PCRE2_SIZE offset;
    std::unique_ptr<pcre2_code, decltype(&pcre2_code_free)> code(
        pcre2_compile(reinterpret_cast<PCRE2_SPTR>(body.bytes.data()), body.bytes.size(),
                      body.options, &error, &offset, nullptr), pcre2_code_free);
    if (!code) return outcome::invalid_pattern;
    // A single output pair suffices. PCRE2 returns zero for a successful match with excess captures.
    std::unique_ptr<pcre2_match_data, decltype(&pcre2_match_data_free)> data(
        pcre2_match_data_create(1, nullptr), pcre2_match_data_free);
    if (!data) return outcome::engine_error;
    const int rc = pcre2_match(code.get(), reinterpret_cast<PCRE2_SPTR>(subject.data()),
                               subject.size(), 0, 0, data.get(), nullptr);
    if (rc >= 0) return outcome::match;
    return rc == PCRE2_ERROR_NOMATCH ? outcome::miss : outcome::engine_error;
}
}

scpp::result_bool_t scpp::regex::test(const string& pattern, const string& subject) {
    const auto result = search(pattern, subject);
    if (result == outcome::invalid_pattern || result == outcome::engine_error)
        return result_bool_t{error_t{string{result == outcome::invalid_pattern ?
            "regex_test: invalid pattern" : "regex_test: matching failed"}}};
    return result_bool_t{result == outcome::match};
}

scpp::result_or_false_int64 scpp::regex::preg_match(const string& pattern, const string& subject) {
    const auto result = search(pattern, subject);
    if (result == outcome::invalid_pattern || result == outcome::engine_error)
        return result_or_false_int64::from_false();
    return result_or_false_int64{int64{result == outcome::match}};
}
