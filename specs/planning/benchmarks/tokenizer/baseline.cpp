#include "baseline.hpp"
#include <string_view>

static bool letter(char c) {
    return (c >= 'A' && c <= 'Z') || (c >= 'a' && c <= 'z') || c == '_';
}

Scan scan(std::string source, std::size_t prior_count) {
    Scan out;
    out.source = std::move(source);
    std::string_view text(out.source);
    out.tokens.reserve(prior_count ? prior_count : (text.empty() ? 0 : 4));
    for (std::size_t i = 0; i < text.size();) {
        auto start = i;
        char c = text[i++];
        int kind = 1;
        if (c == ' ' || c == '\t' || c == '\r' || c == '\n') continue;
        if (letter(c)) {
            while (i < text.size() && (letter(text[i]) || (text[i] >= '0' && text[i] <= '9'))) ++i;
            auto word = text.substr(start, i - start);
            if (word == "if" || word == "while"
#ifdef EXTRA_WORD
                || word == EXTRA_WORD
#endif
            ) kind = 62;
        } else if (c == '=') {
            kind = 61;
            if (i < text.size() && text[i] == '=') {
                ++i; kind = 15;
                if (i < text.size() && text[i] == '=') { ++i; kind = 11; }
            }
        } else {
            out.error = start;
            out.reason = "Unsupported token";
            out.tokens.clear();
            return out;
        }
        out.tokens.push_back({static_cast<std::int64_t>(start), static_cast<std::int64_t>(i-start), kind});
    }
    return out;
}
