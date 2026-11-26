#include "regex_provider.hpp"
#include "scpp/value_error.hpp"
#include <algorithm>

#define PCRE2_CODE_UNIT_WIDTH 8
#include <pcre2.h>
#include <memory>
#include <optional>
#include <string>
#include <string_view>
#include <vector>
#include <type_traits>

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

struct match_span {
    scpp::int64 start;
    scpp::int64 end;
};

outcome search(const scpp::string& pattern, const scpp::string& subject,
               PCRE2_SIZE start_offset = 0, match_span* span = nullptr) {
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
                               subject.size(), start_offset, 0, data.get(), nullptr);
    if (rc >= 0) {
        if (span) {
            const auto* bounds = pcre2_get_ovector_pointer(data.get());
            span->start = static_cast<scpp::int64>(bounds[0]);
            span->end = static_cast<scpp::int64>(bounds[1]);
        }
        return outcome::match;
    }
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

scpp::result_int64 scpp::regex::find_span(const string& pattern, const string& subject, int64 offset, int64& end) {
    if (offset < 0 || offset > subject.size())
        return result_int64{error_t{string{"regex_find_span: offset outside subject"}}};
    match_span span;
    const auto result = search(pattern, subject, static_cast<PCRE2_SIZE>(offset), &span);
    if (result == outcome::miss) return result_int64{int64{-1}};
    if (result != outcome::match)
        return result_int64{error_t{string{result == outcome::invalid_pattern ?
            "regex_find_span: invalid pattern" : "regex_find_span: matching failed"}}};
    result_int64 start{span.start};
    end = span.end;
    return start;
}

namespace scpp::regex {
bool_t jit_available() {
    static const bool available = [] {
        uint32_t configured = 0;
        if (pcre2_config(PCRE2_CONFIG_JIT, &configured) != 0 || !configured) return false;
        int error;
        PCRE2_SIZE offset;
        std::unique_ptr<pcre2_code, decltype(&pcre2_code_free)> code(
            pcre2_compile(reinterpret_cast<PCRE2_SPTR>("a"), 1, 0, &error, &offset, nullptr), pcre2_code_free);
        return code && pcre2_jit_compile(code.get(), PCRE2_JIT_COMPLETE) == 0;
    }();
    return available;
}

string quote(const string& text, const string& delimiter) {
    constexpr std::string_view special = R"(.\+*?[^]$(){}=!<>|:-#)";
    std::string escaped;
    escaped.reserve(text.size());
    for (char byte : std::string_view(text.data(), text.size())) {
        if (special.find(byte) != special.npos || (delimiter.size() && delimiter.data()[0] == byte))
            escaped.push_back('\\');
        escaped.push_back(byte);
    }
    return string{escaped.data(), static_cast<int64>(escaped.size())};
}

string quote(const string& text) {
    return quote(text, string{});
}
}

namespace {
using namespace scpp;
std::string_view bytes(const string& value) {
    return {value.data(), static_cast<std::size_t>(value.size())};
}

string owned(std::string_view value) {
    return value.empty() ? string{} : string{value.data(), static_cast<int64>(value.size())};
}

// Capturing operations share a compiled pattern and a full ovector for one source call.
struct capture_pattern {
    std::unique_ptr<pcre2_code, decltype(&pcre2_code_free)> code{nullptr, pcre2_code_free};
    std::unique_ptr<pcre2_match_data, decltype(&pcre2_match_data_free)> data{nullptr, pcre2_match_data_free};
    bool utf = false;

    explicit capture_pattern(const string& pattern) {
        pattern_body body;
        if (!parse(bytes(pattern), body)) return;
        int error;
        PCRE2_SIZE offset;
        code.reset(pcre2_compile(reinterpret_cast<PCRE2_SPTR>(body.bytes.data()), body.bytes.size(),
                                 body.options, &error, &offset, nullptr));
        if (!code) return;
        data.reset(pcre2_match_data_create_from_pattern(code.get(), nullptr));
        uint32_t options = 0;
        pcre2_pattern_info(code.get(), PCRE2_INFO_ALLOPTIONS, &options);
        utf = (options & PCRE2_UTF) != 0;
    }

    int find(std::string_view text, size_t offset, uint32_t flags) const {
        return pcre2_match(code.get(), reinterpret_cast<PCRE2_SPTR>(text.data()), text.size(),
                           offset, flags, data.get(), nullptr);
    }

    std::string_view capture(std::string_view text, int index) const {
        const auto* pair = pcre2_get_ovector_pointer(data.get()) + index * 2;
        if (pair[0] == PCRE2_UNSET) return {};
        return text.substr(pair[0], pair[1] - pair[0]);
    }

    // The callback returns false once its limit is satisfied.
    template<class Visit>
    bool each(std::string_view text, size_t offset, Visit visit) const {
        uint32_t flags = 0;
        while (offset <= text.size()) {
            const int count = find(text, offset, flags);
            if (count == PCRE2_ERROR_NOMATCH) {
                if (!flags || offset == text.size()) break;
                ++offset;
                while (utf && offset < text.size() && (static_cast<unsigned char>(text[offset]) & 0xc0) == 0x80)
                    ++offset;
                flags = 0;
                continue;
            }
            if (count < 0) return false;
            const auto* bounds = pcre2_get_ovector_pointer(data.get());
            if (!visit(count, bounds[0], bounds[1])) break;
            offset = bounds[1];
            flags = bounds[0] == bounds[1] ? PCRE2_NOTEMPTY_ATSTART | PCRE2_ANCHORED : 0;
        }
        return true;
    }
};

void validate_limit(int64 limit) {
    if (limit < -1) throw ValueError("regex limit must be -1 or nonnegative");
}

void expand(std::string& output, std::string_view replacement, const capture_pattern& pattern,
            std::string_view subject, int count) {
    for (size_t i = 0; i < replacement.size(); ++i) {
        char ch = replacement[i];
        if (ch == '$' || ch == '\\') {
            size_t pos = i + 1;
            bool braced = ch == '$' && pos < replacement.size() && replacement[pos] == '{';
            if (braced) ++pos;
            const auto first = pos;
            int index = 0;
            while (pos < replacement.size() && replacement[pos] >= '0' && replacement[pos] <= '9') {
                if (index < count) index = index * 10 + replacement[pos] - '0';
                ++pos;
            }
            if (pos > first && (!braced || (pos < replacement.size() && replacement[pos] == '}'))) {
                if (index < count) output.append(pattern.capture(subject, index));
                i = braced ? pos : pos - 1;
                continue;
            }
        }
        output.push_back(ch);
    }
}

template<class Render>
bool rewrite(const capture_pattern& pattern, std::string_view text, int64 limit,
             std::string& output, int64& changed, Render render) {
    size_t copied = 0;
    if (limit != 0 && !pattern.each(text, 0, [&](int count, size_t start, size_t end) {
        output.append(text.substr(copied, start - copied));
        render(output, count);
        copied = end;
        return ++changed != limit;
    })) return false;
    output.append(text.substr(copied));
    return true;
}

bool rewrite_string(const capture_pattern& compiled, const string& replacement, const string& subject,
                    int64 limit, std::string& output, int64& changed) {
    const auto text = bytes(subject);
    return rewrite(compiled, text, limit, output, changed, [&](std::string& out, int count) {
        expand(out, bytes(replacement), compiled, text, count);
    });
}

size_t capture_offset(int64 offset, int64 size) {
    return offset < 0 ? static_cast<size_t>(std::max(int64{0}, size + offset)) : static_cast<size_t>(offset);
}
}

namespace scpp::regex {
result_or_false_vector_string match(const string& pattern, const string& subject, int64 offset) {
    capture_pattern compiled(pattern);
    if (!compiled.data) return result_or_false_vector_string::from_false();
    vector_string row;
    auto text = bytes(subject);
    if (!compiled.each(text, capture_offset(offset, subject.size()), [&](int count, size_t, size_t) {
        for (int i = 0; i < count; ++i) row.append(owned(compiled.capture(text, i)));
        return false;
    })) return result_or_false_vector_string::from_false();
    return result_or_false_vector_string{std::move(row)};
}

result_or_false_vector_string match(const string& pattern, const string& subject) {
    return match(pattern, subject, 0);
}

namespace {
template<class Keep>
bool grep_strings(const string& pattern, const vector_string& input, Keep keep) {
    capture_pattern compiled(pattern);
    if (!compiled.data) return false;
    for (int64 i = 0; i < input.size(); ++i) {
        auto value = input.at(i);
        const auto count = compiled.find(bytes(value), 0, 0);
        if (count == PCRE2_ERROR_NOMATCH) continue;
        if (count < 0) return false;
        keep(i, std::move(value));
    }
    return true;
}
}

result_or_false_vector_string grep(const string& pattern, const vector_string& input) {
    vector_string output;
    if (!grep_strings(pattern, input, [&](int64, string&& value) { output.append(std::move(value)); }))
        return result_or_false_vector_string::from_false();
    return result_or_false_vector_string{std::move(output)};
}

result_or_false_hash_string_int64 preg_grep_vector(const string& pattern, const vector_string& input) {
    hash_string_int64 output;
    if (!grep_strings(pattern, input, [&](int64 index, string&& value) { output.set(index, std::move(value)); }))
        return result_or_false_hash_string_int64::from_false();
    return result_or_false_hash_string_int64{std::move(output)};
}

result_or_false_string replace(const string& pattern, const string& replacement, const string& subject, int64 limit, int64& changed) {
    validate_limit(limit);
    changed = 0;
    capture_pattern compiled(pattern);
    if (!compiled.data) return result_or_false_string::from_false();
    std::string output;
    if (!rewrite_string(compiled, replacement, subject, limit, output, changed)) return result_or_false_string::from_false();
    return result_or_false_string{owned(output)};
}

result_or_false_string replace(const string& pattern, const string& replacement, const string& subject, int64 limit) {
    int64 changed;
    return replace(pattern, replacement, subject, limit, changed);
}

result_or_false_string replace(const string& pattern, const string& replacement, const string& subject) {
    return replace(pattern, replacement, subject, -1);
}

nullable_string preg_filter_string(const string& pattern, const string& replacement, const string& subject, int64 limit, int64& changed) {
    validate_limit(limit);
    changed = 0;
    capture_pattern compiled(pattern);
    if (!compiled.data) throw ValueError("preg_filter: invalid pattern");
    std::string output;
    if (!rewrite_string(compiled, replacement, subject, limit, output, changed))
        throw ValueError("preg_filter: matching failed");
    return changed ? nullable_string{owned(output)} : nullable_string{};
}

nullable_string preg_filter_string(const string& pattern, const string& replacement, const string& subject, int64 limit) {
    int64 changed;
    return preg_filter_string(pattern, replacement, subject, limit, changed);
}

nullable_string preg_filter_string(const string& pattern, const string& replacement, const string& subject) {
    return preg_filter_string(pattern, replacement, subject, -1);
}

namespace {
template<bool KeepMisses, class Key, class Input, class Keep>
bool rewrite_rows(const string& pattern, const string& replacement, const Input& input, int64 limit, int64& total, Keep keep) {
    validate_limit(limit);
    total = 0;
    capture_pattern compiled(pattern);
    if (!compiled.data) return false;
    int64 position = 0;
    const auto stop = input.iteration_limit();
    Key key{};
    string subject;
    while (input.iteration_next(position, stop, key, subject)) {
        int64 changed = 0;
        std::string row;
        const bool ok = rewrite_string(compiled, replacement, subject, limit, row, changed);
        total += changed;
        if (!ok) return false;
        if (changed) keep(key, owned(row));
        else if constexpr (KeepMisses) keep(key, std::move(subject));
    }
    return true;
}

template<bool KeepMisses, class Key, class Hash, class Result, class Input>
Result rewrite_keyed(const string& pattern, const string& replacement, const Input& input, int64 limit, int64& total) {
    Hash output;
    if (!rewrite_rows<KeepMisses, Key>(pattern, replacement, input, limit, total, [&](const Key& key, string value) {
        output.set(key, std::move(value));
    })) return Result::from_false();
    return Result{std::move(output)};
}
}

result_or_false_vector_string filter(const string& pattern, const string& replacement, const vector_string& input, int64 limit) {
    vector_string output;
    int64 total;
    if (!rewrite_rows<false, int64>(pattern, replacement, input, limit, total, [&](int64, string value) {
        output.append(std::move(value));
    })) return result_or_false_vector_string::from_false();
    return result_or_false_vector_string{std::move(output)};
}

result_or_false_vector_string filter(const string& pattern, const string& replacement, const vector_string& input) {
    return filter(pattern, replacement, input, -1);
}

result_or_false_vector_string preg_replace_vector(const string& pattern, const string& replacement, const vector_string& input, int64 limit, int64& changed) {
    vector_string output;
    if (!rewrite_rows<true, int64>(pattern, replacement, input, limit, changed, [&](int64, string value) {
        output.append(std::move(value));
    })) return result_or_false_vector_string::from_false();
    return result_or_false_vector_string{std::move(output)};
}

result_or_false_vector_string preg_replace_vector(const string& pattern, const string& replacement, const vector_string& input, int64 limit) {
    int64 changed;
    return preg_replace_vector(pattern, replacement, input, limit, changed);
}

result_or_false_vector_string preg_replace_vector(const string& pattern, const string& replacement, const vector_string& input) {
    return preg_replace_vector(pattern, replacement, input, -1);
}

result_or_false_hash_string_int64 preg_replace_int_keys(const string& pattern, const string& replacement, const hash_string_int64& input, int64 limit, int64& changed) {
    return rewrite_keyed<true, int64, hash_string_int64, result_or_false_hash_string_int64>(pattern, replacement, input, limit, changed);
}

result_or_false_hash_string_int64 preg_replace_int_keys(const string& pattern, const string& replacement, const hash_string_int64& input, int64 limit) {
    int64 changed;
    return preg_replace_int_keys(pattern, replacement, input, limit, changed);
}

result_or_false_hash_string_int64 preg_replace_int_keys(const string& pattern, const string& replacement, const hash_string_int64& input) {
    return preg_replace_int_keys(pattern, replacement, input, -1);
}

result_or_false_hash_string_string preg_replace_string_keys(const string& pattern, const string& replacement, const hash_string_string& input, int64 limit, int64& changed) {
    return rewrite_keyed<true, string, hash_string_string, result_or_false_hash_string_string>(pattern, replacement, input, limit, changed);
}

result_or_false_hash_string_string preg_replace_string_keys(const string& pattern, const string& replacement, const hash_string_string& input, int64 limit) {
    int64 changed;
    return preg_replace_string_keys(pattern, replacement, input, limit, changed);
}

result_or_false_hash_string_string preg_replace_string_keys(const string& pattern, const string& replacement, const hash_string_string& input) {
    return preg_replace_string_keys(pattern, replacement, input, -1);
}

result_or_false_hash_string_int64 preg_filter_vector(const string& pattern, const string& replacement, const vector_string& input, int64 limit, int64& changed) {
    return rewrite_keyed<false, int64, hash_string_int64, result_or_false_hash_string_int64>(pattern, replacement, input, limit, changed);
}

result_or_false_hash_string_int64 preg_filter_vector(const string& pattern, const string& replacement, const vector_string& input, int64 limit) {
    int64 changed;
    return preg_filter_vector(pattern, replacement, input, limit, changed);
}

result_or_false_hash_string_int64 preg_filter_vector(const string& pattern, const string& replacement, const vector_string& input) {
    return preg_filter_vector(pattern, replacement, input, -1);
}

result_or_false_hash_string_int64 preg_filter_int_keys(const string& pattern, const string& replacement, const hash_string_int64& input, int64 limit, int64& changed) {
    return rewrite_keyed<false, int64, hash_string_int64, result_or_false_hash_string_int64>(pattern, replacement, input, limit, changed);
}

result_or_false_hash_string_int64 preg_filter_int_keys(const string& pattern, const string& replacement, const hash_string_int64& input, int64 limit) {
    int64 changed;
    return preg_filter_int_keys(pattern, replacement, input, limit, changed);
}

result_or_false_hash_string_int64 preg_filter_int_keys(const string& pattern, const string& replacement, const hash_string_int64& input) {
    return preg_filter_int_keys(pattern, replacement, input, -1);
}

result_or_false_hash_string_string preg_filter_string_keys(const string& pattern, const string& replacement, const hash_string_string& input, int64 limit, int64& changed) {
    return rewrite_keyed<false, string, hash_string_string, result_or_false_hash_string_string>(pattern, replacement, input, limit, changed);
}

result_or_false_hash_string_string preg_filter_string_keys(const string& pattern, const string& replacement, const hash_string_string& input, int64 limit) {
    int64 changed;
    return preg_filter_string_keys(pattern, replacement, input, limit, changed);
}

result_or_false_hash_string_string preg_filter_string_keys(const string& pattern, const string& replacement, const hash_string_string& input) {
    return preg_filter_string_keys(pattern, replacement, input, -1);
}

namespace {
template<class Result, class Fields, class Make>
Result split_fields(const string& pattern, const string& subject, int64 limit, int64 flags, Make make) {
    capture_pattern compiled(pattern);
    if (!compiled.data) return Result::from_false();
    Fields fields;
    const auto text = bytes(subject);
    size_t copied = 0;
    int64 pieces = 0;
    auto append = [&](std::string_view field, int64 offset) {
        if ((flags & 1) && field.empty()) return false;
        fields.append(make(owned(field), offset));
        return true;
    };
    if (limit != 1 && !compiled.each(text, 0, [&](int count, size_t start, size_t end) {
        if (append(text.substr(copied, start - copied), copied)) ++pieces;
        if (flags & 2) {
            const auto* pairs = pcre2_get_ovector_pointer(compiled.data.get());
            for (int i = 1; i < count; ++i) {
                const auto from = pairs[2 * i];
                if (from == PCRE2_UNSET) append({}, -1);
                else append(text.substr(from, pairs[2 * i + 1] - from), from);
            }
        }
        copied = end;
        return limit <= 0 || pieces < limit - 1;
    })) return Result::from_false();
    append(text.substr(copied), copied);
    return Result{std::move(fields)};
}
}

#if __has_include(<scpp/regex/preg_split_offsets.hpp>)
result_or_false_vector_regex_offset_capture split_offsets(const string& pattern, const string& subject, int64 limit, int64 flags) {
    validate_limit(limit);
    return split_fields<result_or_false_vector_regex_offset_capture, vector_regex_offset_capture>(
        pattern, subject, limit, flags, [](string field, int64 offset) { return regex_offset_capture{std::move(field), offset}; });
}
result_or_false_vector_regex_offset_capture split_offsets(const string& pattern, const string& subject, int64 limit) {
    return split_offsets(pattern, subject, limit, 0);
}
result_or_false_vector_regex_offset_capture split_offsets(const string& pattern, const string& subject) {
    return split_offsets(pattern, subject, -1, 0);
}
#endif

result_or_false_vector_string split(const string& pattern, const string& subject, int64 limit, int64 flags) {
    validate_limit(limit);
    if (flags & 4) throw ValueError("regex_split offset captures are unsupported");
    return split_fields<result_or_false_vector_string, vector_string>(
        pattern, subject, limit, flags, [](string field, int64) { return field; });
}
result_or_false_vector_string split(const string& pattern, const string& subject, int64 limit) {
    return split(pattern, subject, limit, 0);
}
result_or_false_vector_string split(const string& pattern, const string& subject) {
    return split(pattern, subject, -1, 0);
}
}

namespace scpp::regex {
namespace {
constexpr int capture_offsets = 256;
constexpr int unmatched_as_null = 512;
constexpr int capture_modifiers = capture_offsets | unmatched_as_null;
result_or_false_int64 flagged_mixed_matches(const string&, const string&, mixed&, int64, int64, bool);
}
}

namespace {
template<class Field>
void capture_fields(const capture_pattern& pattern, std::string_view subject, int count, Field field) {
    for (int i = 0; i < count; ++i)
        field(static_cast<int64>(i), owned(pattern.capture(subject, i)));
    uint32_t names = 0, stride = 0;
    PCRE2_SPTR table = nullptr;
    pcre2_pattern_info(pattern.code.get(), PCRE2_INFO_NAMECOUNT, &names);
    pcre2_pattern_info(pattern.code.get(), PCRE2_INFO_NAMEENTRYSIZE, &stride);
    pcre2_pattern_info(pattern.code.get(), PCRE2_INFO_NAMETABLE, &table);
    for (uint32_t i = 0; i < names; ++i) {
        const auto* entry = table + i * stride;
        const int group = entry[0] * 256 + entry[1];
        field(string{reinterpret_cast<const char*>(entry + 2)},
            group < count ? owned(pattern.capture(subject, group)) : string{});
    }
}

void validate_match_flags(int64 flags, bool all) {
    if ((!all && flags != 0) || (all && flags != 0 && flags != 1 && flags != 2))
        throw ValueError("unsupported match flags");
}

result_or_false_int64 php_matches(const string& pattern, const string& subject, mixed* matches,
                                  int64 flags, int64 offset, bool all) {
    if (flags & scpp::regex::capture_modifiers) return scpp::regex::flagged_mixed_matches(pattern, subject, *matches, flags, offset, all);
    validate_match_flags(flags, all);
    auto output = mixed::container();
    capture_pattern compiled(pattern);
    int64 hits = 0;
    int widest = 0;
    auto text = bytes(subject);
    bool ok = compiled.data && compiled.each(text, capture_offset(offset, subject.size()), [&](int count, size_t, size_t) {
        ++hits;
        if (matches) {
            auto row = mixed::container();
            if (all && flags != 2) {
                for (int64 i = count; i < widest; ++i) {
                    auto column = output.read(i);
                    column.append(mixed::from_string(string{}));
                    output.set(i, std::move(column));
                }
                widest = std::max(widest, count);
            }
            capture_fields(compiled, text, count, [&](const auto& key, const string& capture) {
                auto value = mixed::from_string(capture);
                if (all && flags != 2) {
                    auto column = output.has(key) ? output.read(key) : mixed::container();
                    while (column.container_size() < hits - 1) column.append(mixed::from_string(string{}));
                    column.append(std::move(value));
                    output.set(key, std::move(column));
                } else row.set(key, std::move(value));
            });
            if (!all) output = std::move(row);
            else if (flags == 2) output.append(std::move(row));
        }
        return all;
    });
    if (matches) *matches = ok ? std::move(output) : mixed::container();
    return ok ? result_or_false_int64{hits} : result_or_false_int64::from_false();
}
}

namespace scpp::regex {
result_or_false_int64 preg_match(const string& pattern, const string& subject, mixed& matches, int64 flags, int64 offset) {
    return php_matches(pattern, subject, &matches, flags, offset, false);
}
result_or_false_int64 preg_match(const string& pattern, const string& subject, mixed& matches, int64 flags) {
    return preg_match(pattern, subject, matches, flags, 0);
}
result_or_false_int64 preg_match(const string& pattern, const string& subject, mixed& matches) {
    return preg_match(pattern, subject, matches, 0, 0);
}
result_or_false_int64 preg_match_all(const string& pattern, const string& subject, mixed& matches, int64 flags, int64 offset) {
    return php_matches(pattern, subject, &matches, flags, offset, true);
}
result_or_false_int64 preg_match_all(const string& pattern, const string& subject, mixed& matches, int64 flags) {
    return preg_match_all(pattern, subject, matches, flags, 0);
}
result_or_false_int64 preg_match_all(const string& pattern, const string& subject, mixed& matches) {
    return preg_match_all(pattern, subject, matches, 0, 0);
}
result_or_false_int64 preg_match_all(const string& pattern, const string& subject) {
    return php_matches(pattern, subject, nullptr, 0, 0, true);
}
}

namespace scpp::regex {
namespace {
result_or_false_mixed select_rows(const string& pattern, const mixed& input, const string* replacement, int64 limit, int64& total) {
    validate_limit(limit);
    total = 0;
    capture_pattern compiled(pattern);
    if (!compiled.data) return result_or_false_mixed::from_false();
    auto output = mixed::container();
    if (input.kind() != boxed_kind::container) return result_or_false_mixed{std::move(output)};
    int64 position = 0, numeric_key;
    string text_key;
    mixed entry;
    const auto stop = input.iteration_limit();
    while (input.iteration_next(position, stop, numeric_key, text_key, entry)) {
        auto subject = boxed_mixed_explicit_cast_string(entry);
        if (replacement) {
            int64 changed = 0;
            std::string rewritten;
            const bool ok = rewrite_string(compiled, *replacement, subject, limit, rewritten, changed);
            total += changed;
            if (!ok) return result_or_false_mixed::from_false();
            if (!changed) continue;
            entry = mixed::from_string(owned(rewritten));
        } else {
            int count = compiled.find(bytes(subject), 0, 0);
            if (count == PCRE2_ERROR_NOMATCH) continue;
            if (count < 0) return result_or_false_mixed::from_false();
            entry = mixed::from_string(subject);
        }
        if (numeric_key < 0) output.set(text_key, entry);
        else output.set(numeric_key, entry);
    }
    return result_or_false_mixed{std::move(output)};
}
}

result_or_false_mixed preg_grep(const string& pattern, const mixed& input) {
    int64 unused;
    return select_rows(pattern, input, nullptr, -1, unused);
}
result_or_false_mixed preg_filter(const string& pattern, const string& replacement, const mixed& input, int64 limit, int64& changed) {
    return select_rows(pattern, input, &replacement, limit, changed);
}

result_or_false_mixed preg_filter(const string& pattern, const string& replacement, const mixed& input, int64 limit) {
    int64 changed;
    return preg_filter(pattern, replacement, input, limit, changed);
}
result_or_false_mixed preg_filter(const string& pattern, const string& replacement, const mixed& input) {
    return preg_filter(pattern, replacement, input, -1);
}
}

namespace {
string capture_key(int64 number) { return owned(std::to_string(number)); }
const string& capture_key(const string& name) { return name; }

hash_string_string named_row(const capture_pattern& compiled, std::string_view text, int count) {
    hash_string_string row;
    capture_fields(compiled, text, count, [&](const auto& key, string value) {
        row.set(capture_key(key), std::move(value));
    });
    return row;
}

vector_string numbered_row(const capture_pattern& compiled, std::string_view text, int count) {
    vector_string row;
    for (int i = 0; i < count; ++i) row.append(owned(compiled.capture(text, i)));
    return row;
}

template<class Result, class Rows, class Collect>
Result collect_matches(const string& pattern, const string& subject, int64 offset, Collect collect) {
    capture_pattern compiled(pattern);
    Rows rows;
    const auto text = bytes(subject);
    if (!compiled.data || !compiled.each(text, capture_offset(offset, subject.size()), [&](int count, size_t, size_t) {
        return collect(rows, compiled, text, count);
    })) return Result::from_false();
    return Result{std::move(rows)};
}
}

namespace scpp::regex {
result_or_false_int64 preg_match_numbered(const string& pattern, const string& subject, vector_string& matches,
                                          int64 flags, int64 offset) {
    validate_match_flags(flags, false);
    capture_pattern compiled(pattern);
    auto text = bytes(subject);
    vector_string row;
    bool hit = false;
    const bool ok = compiled.data && compiled.each(text, capture_offset(offset, subject.size()), [&](int count, size_t, size_t) {
        hit = true;
        row = numbered_row(compiled, text, count);
        return false;
    });
    matches = std::move(row);
    return ok ? result_or_false_int64{int64{hit}} : result_or_false_int64::from_false();
}

namespace {
result_or_false_int64 collect_numbered_matches(const string& pattern, const string& subject, vector_vector_string& matches,
                                               bool columns, int64 offset) {
    capture_pattern compiled(pattern);
    vector_vector_string output;
    if (compiled.data && columns) {
        uint32_t groups = 0;
        pcre2_pattern_info(compiled.code.get(), PCRE2_INFO_CAPTURECOUNT, &groups);
        for (uint32_t i = 0; i <= groups; ++i) output.append(vector_string{});
    }
    auto text = bytes(subject);
    int64 hits = 0;
    const bool ok = compiled.data && compiled.each(text, capture_offset(offset, subject.size()), [&](int count, size_t, size_t) {
        ++hits;
        if (columns) {
            for (int64 i = 0; i < output.size(); ++i) {
                output.iteration_reference(i).append(i < count ? owned(compiled.capture(text, static_cast<int>(i))) : string{});
            }
        } else output.append(numbered_row(compiled, text, count));
        return true;
    });
    matches = ok ? std::move(output) : vector_vector_string{};
    return ok ? result_or_false_int64{hits} : result_or_false_int64::from_false();
}

} // namespace

result_or_false_int64 preg_match_all_numbered(const string& pattern, const string& subject, vector_vector_string& matches,
                                              int64 flags, int64 offset) {
    validate_match_flags(flags, true);
    return collect_numbered_matches(pattern, subject, matches, flags != 2, offset);
}

result_or_false_int64 preg_match_numbered(const string& pattern, const string& subject, vector_string& matches, int64 flags) {
    return preg_match_numbered(pattern, subject, matches, flags, 0);
}
result_or_false_int64 preg_match_numbered(const string& pattern, const string& subject, vector_string& matches) {
    return preg_match_numbered(pattern, subject, matches, 0, 0);
}

result_or_false_int64 preg_match_all_numbered(const string& pattern, const string& subject, vector_vector_string& matches, int64 flags) {
    return preg_match_all_numbered(pattern, subject, matches, flags, 0);
}
result_or_false_int64 preg_match_all_numbered(const string& pattern, const string& subject, vector_vector_string& matches) {
    return preg_match_all_numbered(pattern, subject, matches, 0, 0);
}

namespace {
// PCRE2 lists names alphabetically. PHP visits each name immediately before its group number.
std::vector<string> names_by_group(const capture_pattern& compiled) {
    uint32_t groups = 0, names = 0, stride = 0;
    PCRE2_SPTR table = nullptr;
    pcre2_pattern_info(compiled.code.get(), PCRE2_INFO_CAPTURECOUNT, &groups);
    pcre2_pattern_info(compiled.code.get(), PCRE2_INFO_NAMECOUNT, &names);
    pcre2_pattern_info(compiled.code.get(), PCRE2_INFO_NAMEENTRYSIZE, &stride);
    pcre2_pattern_info(compiled.code.get(), PCRE2_INFO_NAMETABLE, &table);
    std::vector<string> result(groups + 1);
    for (uint32_t i = 0; i < names; ++i) {
        const auto* entry = table + i * stride;
        result[entry[0] * 256 + entry[1]] = string{reinterpret_cast<const char*>(entry + 2)};
    }
    return result;
}

#include "capture_modes.hpp"

template<class Output, class Store>
result_or_false_int64 php_named_rows(const string& pattern, const string& subject, Output& matches,
                                     int64 offset, bool all, Store store) {
    capture_pattern compiled(pattern);
    Output output;
    int64 hits = 0;
    if (compiled.data) {
        const auto names = names_by_group(compiled);
        const auto text = bytes(subject);
        const bool ok = compiled.each(text, capture_offset(offset, subject.size()), [&](int count, size_t, size_t) {
            const auto* bounds = pcre2_get_ovector_pointer(compiled.data.get());
            hash_string_string row;
            for (int i = 0; i < count; ++i) {
                auto value = owned(compiled.capture(text, i));
                const auto& name = names[i];
                // An unmatched duplicate does not displace a participating group in a row.
                if (name.size() && (bounds[2 * i] != PCRE2_UNSET || !row.has(name))) row.set(name, value);
                row.set(capture_key(i), std::move(value));
            }
            store(output, std::move(row));
            ++hits;
            return all;
        });
        if (ok) {
            matches = std::move(output);
            return result_or_false_int64{hits};
        }
    }
    matches = Output{};
    return result_or_false_int64::from_false();
}
}

result_or_false_int64 preg_match_named(const string& pattern, const string& subject, hash_string_string& matches,
                                       int64 flags, int64 offset) {
    validate_match_flags(flags, false);
    return php_named_rows(pattern, subject, matches, offset, false,
        [](auto& output, auto row) { output = std::move(row); });
}

result_or_false_int64 preg_match_all_set_order_named(const string& pattern, const string& subject,
                                                     vector_hash_string_string& matches, int64 offset) {
    return php_named_rows(pattern, subject, matches, offset, true,
        [](auto& output, auto row) { output.append(std::move(row)); });
}

result_or_false_int64 preg_match_all_named_rows(const string& pattern, const string& subject, vector_hash_string_string& matches,
                                                int64 flags, int64 offset) {
    if (flags != 2) throw ValueError("preg_match_all named rows require PREG_SET_ORDER");
    return preg_match_all_set_order_named(pattern, subject, matches, offset);
}

result_or_false_int64 preg_match_all_pattern_order_named(const string& pattern, const string& subject,
                                                         hash_vector_string_string& matches, int64 offset) {
    capture_pattern compiled(pattern);
    if (compiled.data) {
        const auto names = names_by_group(compiled);
        std::vector<vector_string> columns(names.size());
        const auto text = bytes(subject);
        int64 hits = 0;
        const bool ok = compiled.each(text, capture_offset(offset, subject.size()), [&](int count, size_t, size_t) {
            for (size_t i = 0; i < columns.size(); ++i)
                columns[i].append(i < static_cast<size_t>(count) ? owned(compiled.capture(text, static_cast<int>(i))) : string{});
            ++hits;
            return true;
        });
        if (ok) {
            hash_vector_string_string output;
            for (size_t i = 0; i < columns.size(); ++i) {
                // Replacing a duplicate name selects the rightmost column at the first name's position.
                if (names[i].size()) output.set(names[i], columns[i]);
                output.set(capture_key(i), std::move(columns[i]));
            }
            matches = std::move(output);
            return result_or_false_int64{hits};
        }
    }
    matches = hash_vector_string_string{};
    return result_or_false_int64::from_false();
}

result_or_false_int64 preg_match_all_named_columns(const string& pattern, const string& subject, hash_vector_string_string& matches,
                                                   int64 flags, int64 offset) {
    if (flags != 0 && flags != 1) throw ValueError("preg_match_all named columns require PREG_PATTERN_ORDER");
    return preg_match_all_pattern_order_named(pattern, subject, matches, offset);
}

result_or_false_int64 preg_match_all_set_order_numbered(const string& pattern, const string& subject,
                                                        vector_vector_string& matches, int64 offset) {
    return collect_numbered_matches(pattern, subject, matches, false, offset);
}
result_or_false_int64 preg_match_all_set_order_numbered(const string& pattern, const string& subject, vector_vector_string& matches) {
    return preg_match_all_set_order_numbered(pattern, subject, matches, 0);
}

result_or_false_int64 preg_match_all_set_order_named(const string& pattern, const string& subject, vector_hash_string_string& matches) {
    return preg_match_all_set_order_named(pattern, subject, matches, 0);
}

result_or_false_int64 preg_match_all_pattern_order_numbered(const string& pattern, const string& subject,
                                                            vector_vector_string& matches, int64 offset) {
    return collect_numbered_matches(pattern, subject, matches, true, offset);
}
result_or_false_int64 preg_match_all_pattern_order_numbered(const string& pattern, const string& subject, vector_vector_string& matches) {
    return preg_match_all_pattern_order_numbered(pattern, subject, matches, 0);
}

result_or_false_int64 preg_match_all_pattern_order_named(const string& pattern, const string& subject, hash_vector_string_string& matches) {
    return preg_match_all_pattern_order_named(pattern, subject, matches, 0);
}

result_or_false_int64 preg_match_named(const string& pattern, const string& subject, hash_string_string& matches, int64 flags) {
    return preg_match_named(pattern, subject, matches, flags, 0);
}
result_or_false_int64 preg_match_named(const string& pattern, const string& subject, hash_string_string& matches) {
    return preg_match_named(pattern, subject, matches, 0, 0);
}

result_or_false_int64 preg_match_all_named_rows(const string& pattern, const string& subject, vector_hash_string_string& matches, int64 flags) {
    return preg_match_all_named_rows(pattern, subject, matches, flags, 0);
}
result_or_false_int64 preg_match_all_named_rows(const string& pattern, const string& subject, vector_hash_string_string& matches) {
    return preg_match_all_named_rows(pattern, subject, matches, 0, 0);
}

result_or_false_int64 preg_match_all_named_columns(const string& pattern, const string& subject, hash_vector_string_string& matches, int64 flags) {
    return preg_match_all_named_columns(pattern, subject, matches, flags, 0);
}
result_or_false_int64 preg_match_all_named_columns(const string& pattern, const string& subject, hash_vector_string_string& matches) {
    return preg_match_all_named_columns(pattern, subject, matches, 0, 0);
}

result_or_false_hash_string_string match_named(const string& pattern, const string& subject, int64 offset) {
    return collect_matches<result_or_false_hash_string_string, hash_string_string>(pattern, subject, offset,
        [](auto& row, const auto& compiled, auto text, int count) {
            row = named_row(compiled, text, count);
            return false;
        });
}
result_or_false_vector_hash_string_string match_all_named(const string& pattern, const string& subject, int64 offset) {
    return collect_matches<result_or_false_vector_hash_string_string, vector_hash_string_string>(pattern, subject, offset,
        [](auto& rows, const auto& compiled, auto text, int count) {
            rows.append(named_row(compiled, text, count));
            return true;
        });
}
result_or_false_vector_vector_string match_all(const string& pattern, const string& subject, int64 offset) {
    return collect_matches<result_or_false_vector_vector_string, vector_vector_string>(pattern, subject, offset,
        [](auto& rows, const auto& compiled, auto text, int count) {
            rows.append(numbered_row(compiled, text, count));
            return true;
        });
}
result_or_false_vector_vector_string match_all_pattern_order(const string& pattern, const string& subject, int64 offset) {
    auto matches = match_all(pattern, subject, offset);
    if (!matches.has_value()) return result_or_false_vector_vector_string::from_false();
    auto rows = matches.value();
    std::vector<vector_string> columns;
    for (int64 i = 0; i < rows.size(); ++i) {
        auto row = rows.at(i);
        while (columns.size() < static_cast<size_t>(row.size())) {
            auto& column = columns.emplace_back();
            for (int64 previous = 0; previous < i; ++previous) column.append(string{});
        }
        for (size_t j = 0; j < columns.size(); ++j)
            columns[j].append(j < static_cast<size_t>(row.size()) ? row.at(static_cast<int64>(j)) : string{});
    }
    vector_vector_string result;
    for (auto& column : columns) result.append(std::move(column));
    return result_or_false_vector_vector_string{std::move(result)};
}
result_or_false_hash_string_string match_named(const string& pattern, const string& subject) {
    return match_named(pattern, subject, 0);
}
result_or_false_vector_vector_string match_all(const string& pattern, const string& subject) {
    return match_all(pattern, subject, 0);
}
result_or_false_vector_hash_string_string match_all_named(const string& pattern, const string& subject) {
    return match_all_named(pattern, subject, 0);
}
result_or_false_vector_vector_string match_all_pattern_order(const string& pattern, const string& subject) {
    return match_all_pattern_order(pattern, subject, 0);
}
}

namespace scpp::regex {
namespace {
template<class Render>
result_or_false_string replace_with_callback(const string& pattern, const string& subject, int64 limit, int64& changed, Render render) {
    validate_limit(limit);
    capture_pattern compiled(pattern);
    if (!compiled.data) return result_or_false_string::from_false();
    std::string output;
    const auto text = bytes(subject);
    if (!rewrite(compiled, text, limit, output, changed, [&](std::string& out, int count) {
        auto rendered = render(compiled, text, count);
        out.append(bytes(rendered));
    })) return result_or_false_string::from_false();
    return result_or_false_string{owned(output)};
}
struct prepared_callback_pattern {
    capture_pattern compiled;
    std::vector<string> names;
    size_t groups;
    prepared_callback_pattern(const string& text, bool named)
        : compiled(text), names(named && compiled.data ? names_by_group(compiled) : std::vector<string>{}),
          groups(compiled.data ? pcre2_get_ovector_count(compiled.data.get()) : 0) {}
};

struct callback_pattern_entry {
    string text;
    std::optional<prepared_callback_pattern> prepared;

    bool prepare(bool named) {
        if (!prepared) prepared.emplace(text, named);
        return prepared->compiled.data != nullptr;
    }
};

template<class Input>
std::vector<callback_pattern_entry> snapshot_callback_patterns(const Input& input) {
    std::vector<callback_pattern_entry> patterns;
    patterns.reserve(input.size());
    int64 position = 0;
    const auto stop = input.iteration_limit();
    string text;
    while (input.iteration_next(position, stop, text)) patterns.push_back({std::move(text), std::nullopt});
    return patterns;
}

template<int Flags, bool Named, class Cell, class Row, class Invoke>
bool rewrite_callback_pass(const prepared_callback_pattern& pattern, const void* context, Invoke invoke,
                           string& value, int64 limit, int64& total, int64* count) {
    const auto text = bytes(value);
    int64 changed = 0;
    std::string output;
    const bool ok = rewrite(pattern.compiled, text, limit, output, changed, [&](std::string& out, int matched) {
        auto row = capture_container<Row>();
        const auto* pairs = pcre2_get_ovector_pointer(pattern.compiled.data.get());
        const size_t width = Flags & unmatched_as_null ? pattern.groups : matched;
        for (size_t i = 0; i < width; ++i) {
            const auto* bounds = i < static_cast<size_t>(matched) ? pairs + i * 2 : nullptr;
            write_capture<Named>(row, i, pattern.names, bounds && bounds[0] != PCRE2_UNSET, capture_value<Flags, Cell>(text, bounds));
        }
        ++total;
        try {
            out.append(bytes(invoke(context, std::move(row))));
        } catch (...) {
            if (count) *count = total;
            throw;
        }
    });
    if (ok && changed) value = owned(output);
    return ok;
}

template<class Result, class Subject, class Rewrite>
Result rewrite_callback_rows(const Subject& subject, int64& total, int64* count, Rewrite rewrite_one) {
    // A callback may mutate the caller's subject. Each pass owns its working value.
    Subject output = subject;
    bool ok = true;
    if constexpr (std::is_same_v<Subject, string>) {
        ok = rewrite_one(output);
    } else {
        int64 position = 0;
        const auto stop = output.iteration_limit();
        std::conditional_t<std::is_same_v<Subject, hash_string_string>, string, int64> key{};
        string value;
        while (output.iteration_next(position, stop, key, value)) {
            const int64 before = total;
            if (!rewrite_one(value)) {
                ok = false;
                break;
            }
            if (total != before) output.set(key, std::move(value));
        }
    }
    if (count) *count = total;
    if (!ok) return Result::from_false();
    return Result{std::move(output)};
}

template<int Flags, bool Named, class Cell, class Row, class Result, class Subject, class Invoke, class Patterns>
Result capture_callback(Patterns& patterns, const void* context, Invoke invoke,
                        const Subject& subject, int64 limit, int64* count) {
    validate_limit(limit);
    int64 total = 0;
    auto invalid = [&] {
        if (count) *count = total;
        return Result::from_false();
    };
    auto apply = [&](const prepared_callback_pattern& pattern, string& value) {
        return rewrite_callback_pass<Flags, Named, Cell, Row>(pattern, context, invoke, value, limit, total, count);
    };
    if constexpr (std::is_same_v<std::remove_cv_t<Patterns>, string>) {
        prepared_callback_pattern pattern(patterns, Named);
        if (!pattern.compiled.data) return invalid();
        return rewrite_callback_rows<Result>(subject, total, count, [&](string& value) { return apply(pattern, value); });
    } else {
        if constexpr (!std::is_same_v<Subject, string>) {
            if (subject.empty()) {
                for (auto& rule : patterns) if (!rule.prepare(Named)) return invalid();
            }
        }
        return rewrite_callback_rows<Result>(subject, total, count, [&](string& value) {
            for (auto& rule : patterns) {
                if (!rule.prepare(Named) || !apply(*rule.prepared, value)) return false;
            }
            return true;
        });
    }
}

template<class Result = result_or_false_string, class Subject, class Patterns>
Result mixed_callback(Patterns& pattern, const void* context, render_mixed invoke,
                      const Subject& subject, int64 limit, int64* count, int64 flags) {
    switch (flags) {
        case 0: return capture_callback<0, true, mixed, mixed, Result>(pattern, context, invoke, subject, limit, count);
        case capture_offsets: return capture_callback<capture_offsets, true, mixed, mixed, Result>(pattern, context, invoke, subject, limit, count);
        case unmatched_as_null: return capture_callback<unmatched_as_null, true, mixed, mixed, Result>(pattern, context, invoke, subject, limit, count);
        case capture_modifiers: return capture_callback<capture_modifiers, true, mixed, mixed, Result>(pattern, context, invoke, subject, limit, count);
        default: throw ValueError("unsupported regex callback capture flags");
    }
}

}
result_or_false_string replace_callback(const string& pattern, const void* context, render_vector_string invoke,
                                        const string& subject, int64 limit) {
    int64 changed = 0;
    return replace_with_callback(pattern, subject, limit, changed, [&](const auto& compiled, auto text, int count) {
        return invoke(context, numbered_row(compiled, text, count));
    });
}
result_or_false_string replace_callback(const string& pattern, const void* context, render_mixed invoke,
                                        const string& subject, int64 limit) {
    return mixed_callback(pattern, context, invoke, subject, limit, nullptr, 0);
}
result_or_false_string replace_callback(const string& pattern, const void* context, render_mixed invoke,
                                        const string& subject, int64 limit, int64& count) {
    return mixed_callback(pattern, context, invoke, subject, limit, &count, 0);
}
}

namespace scpp::regex {
namespace {
struct replacement_rule {
    string pattern;
    string replacement;
    std::optional<capture_pattern> compiled;

    bool prepare() {
        if (!compiled) compiled.emplace(pattern);
        return compiled->data != nullptr;
    }
};

template<class Patterns, class Replacements>
std::vector<replacement_rule> make_rules(const Patterns& patterns, const Replacements& replacements, int64 limit) {
    validate_limit(limit);
    std::vector<replacement_rule> rules;
    rules.reserve(patterns.size());
    int64 pattern_pos = 0, replacement_pos = 0;
    const auto stop = patterns.iteration_limit();
    string pattern;
    while (patterns.iteration_next(pattern_pos, stop, pattern)) {
        string replacement;
        if constexpr (std::is_same_v<Replacements, string>) replacement = replacements;
        else replacements.iteration_next(replacement_pos, replacements.iteration_limit(), replacement);
        rules.push_back({std::move(pattern), std::move(replacement), std::nullopt});
    }
    return rules;
}

// Compile on first use. An error in a later rule leaves the earlier counts observable.
outcome rewrite_rule_row(std::vector<replacement_rule>& rules, string& value, int64 limit, int64& total) {
    const int64 before = total;
    for (auto& rule : rules) {
        if (!rule.prepare()) return outcome::invalid_pattern;
        int64 changed = 0;
        std::string rewritten;
        const bool ok = rewrite_string(*rule.compiled, rule.replacement, value, limit, rewritten, changed);
        total += changed;
        if (!ok) return outcome::engine_error;
        if (changed) value = owned(rewritten);
    }
    return total == before ? outcome::miss : outcome::match;
}

template<bool Filter, class Result, class Subject>
Result apply_rules(std::vector<replacement_rule> rules, const Subject& subject, int64 limit, int64& total) {
    total = 0;
    if constexpr (std::is_same_v<Subject, string>) {
        string value = subject;
        const auto status = rewrite_rule_row(rules, value, limit, total);
        if constexpr (Filter) {
            if (status == outcome::invalid_pattern) throw ValueError("preg_filter: invalid pattern");
            if (status == outcome::engine_error) throw ValueError("preg_filter: matching failed");
            if (status == outcome::miss) return nullable_string{};
        } else if (status == outcome::invalid_pattern || status == outcome::engine_error) return Result::from_false();
        return Result{std::move(value)};
    } else {
        int64 position = 0;
        // Empty collections have no first row on which to validate the patterns.
        bool empty;
        if constexpr (std::is_same_v<Subject, mixed>) empty = subject.kind() != boxed_kind::container || !subject.container_size();
        else empty = subject.empty();
        if (empty && !std::all_of(rules.begin(), rules.end(), [](auto& rule) { return rule.prepare(); }))
            return Result::from_false();
        if constexpr (std::is_same_v<Subject, mixed>) {
            auto output = mixed::container();
            if (!rules.empty() && !empty) {
                int64 numeric_key;
                string text_key;
                mixed entry;
                const auto stop = subject.iteration_limit();
                while (subject.iteration_next(position, stop, numeric_key, text_key, entry)) {
                    auto value = boxed_mixed_explicit_cast_string(entry);
                    const auto status = rewrite_rule_row(rules, value, limit, total);
                    if (status == outcome::invalid_pattern || status == outcome::engine_error) return Result::from_false();
                    if (status == outcome::miss) continue;
                    entry = mixed::from_string(std::move(value));
                    if (numeric_key < 0) output.set(text_key, entry);
                    else output.set(numeric_key, entry);
                }
            }
            return Result{std::move(output)};
        } else {
            using Output = std::conditional_t<Filter && std::is_same_v<Subject, vector_string>, hash_string_int64, Subject>;
            Output output;
            std::conditional_t<std::is_same_v<Subject, hash_string_string>, string, int64> key{};
            string value;
            const auto stop = subject.iteration_limit();
            while (subject.iteration_next(position, stop, key, value)) {
                const auto status = rewrite_rule_row(rules, value, limit, total);
                if (status == outcome::invalid_pattern || status == outcome::engine_error) return Result::from_false();
                if (Filter && status == outcome::miss) continue;
                if constexpr (std::is_same_v<Output, vector_string>) output.append(std::move(value));
                else output.set(key, std::move(value));
            }
            return Result{std::move(output)};
        }
    }
}
}
}

#include "regex_capture_bindings.hpp"
