#include "scpp/regex/string_grapheme_count.hpp"
#include "scpp/regex/string_grapheme_slice.hpp"
#include "../sequence/utf8_codepoints.hpp"

#define PCRE2_CODE_UNIT_WIDTH 8
#include <pcre2.h>
#include <memory>
#include <stdexcept>

namespace scpp::regex {
namespace {
const pcre2_code* cluster_pattern() {
    static const std::unique_ptr<pcre2_code, decltype(&pcre2_code_free)> code([] {
        static constexpr char pattern[] =
#include "grapheme_pattern.inc"
        ;
        int error;
        PCRE2_SIZE offset;
        auto* compiled = pcre2_compile(reinterpret_cast<PCRE2_SPTR>(pattern), sizeof(pattern) - 1,
                                      PCRE2_UTF | PCRE2_ANCHORED, &error, &offset, nullptr);
        if (!compiled) throw std::runtime_error("cannot compile Unicode grapheme pattern");
        return compiled;
    }(), pcre2_code_free);
    return code.get();
}

struct window {
    int64 count = 0;
    std::string_view bytes;
};

window scan(std::string_view bytes, int64 start, int64 length) {
    // Validate even the suffix outside the requested window. A malformed byte
    // anywhere changes the unit for the entire input to individual bytes.
    for (auto rest = bytes; !rest.empty();) {
        const auto width = detail::utf8::validated_width(rest);
        if (!width) {
            const auto first = std::min<std::size_t>(start, bytes.size());
            return {static_cast<int64>(bytes.size()), bytes.substr(first, length)};
        }
        rest.remove_prefix(width);
    }
    if (bytes.empty()) return {};
    const auto* pattern = cluster_pattern();
    std::unique_ptr<pcre2_match_data, decltype(&pcre2_match_data_free)> match(
        pcre2_match_data_create(2, nullptr), pcre2_match_data_free);
    if (!match) throw std::bad_alloc();

    int64 count = 0;
    std::size_t pos = 0, first = bytes.size();
    while (pos < bytes.size()) {
        if (count == start) first = pos;
        int rc;
        do {
            rc = pcre2_match(pattern, reinterpret_cast<PCRE2_SPTR>(bytes.data()), bytes.size(),
                             pos, PCRE2_NO_UTF_CHECK, match.get(), nullptr);
            if (rc < 1) throw std::runtime_error("Unicode grapheme matching failed");
            pos = pcre2_get_ovector_pointer(match.get())[1];
        } while (rc == 2); // GB11 joined another pictograph to this cluster.
        ++count;
        if (count >= start && count - start == length) break;
    }
    return {count, bytes.substr(first, pos - first)};
}
}

int64 grapheme_count(const string& text) {
    return scan({text.data(), static_cast<std::size_t>(text.size())}, 0, text.size()).count;
}

string grapheme_slice(const string& text, int64 start, int64 length) {
    if (start < 0 || length <= 0) return {};
    const auto range = scan({text.data(), static_cast<std::size_t>(text.size())}, start, length).bytes;
    if (range.empty()) return {};
    return string{range.data(), static_cast<int64>(range.size())};
}
}
