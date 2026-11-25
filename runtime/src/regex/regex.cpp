#include "regex_provider.hpp"
#include "scpp/value_error.hpp"
#include <algorithm>

#define PCRE2_CODE_UNIT_WIDTH 8
#include <pcre2.h>
#include <memory>
#include <string>
#include <string_view>
#include <vector>

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

result_or_false_vector_string grep(const string& pattern, const vector_string& input) {
    capture_pattern compiled(pattern);
    if (!compiled.data) return result_or_false_vector_string::from_false();
    vector_string output;
    for (int64 i = 0; i < input.size(); ++i) {
        auto value = input.at(i);
        const auto count = compiled.find(bytes(value), 0, 0);
        if (count == PCRE2_ERROR_NOMATCH) continue;
        if (count < 0) return result_or_false_vector_string::from_false();
        output.append(std::move(value));
    }
    return result_or_false_vector_string{std::move(output)};
}

result_or_false_string replace(const string& pattern, const string& replacement, const string& subject, int64 limit) {
    validate_limit(limit);
    capture_pattern compiled(pattern);
    if (!compiled.data) return result_or_false_string::from_false();
    std::string output;
    int64 changed = 0;
    if (!rewrite(compiled, bytes(subject), limit, output, changed, [&](std::string& out, int count) {
        expand(out, bytes(replacement), compiled, bytes(subject), count);
    })) return result_or_false_string::from_false();
    return result_or_false_string{owned(output)};
}

result_or_false_string replace(const string& pattern, const string& replacement, const string& subject) {
    return replace(pattern, replacement, subject, -1);
}

result_or_false_vector_string filter(const string& pattern, const string& replacement, const vector_string& input, int64 limit) {
    validate_limit(limit);
    capture_pattern compiled(pattern);
    if (!compiled.data) return result_or_false_vector_string::from_false();
    vector_string output;
    for (int64 i = 0; i < input.size(); ++i) {
        auto value = input.at(i);
        int64 changed = 0;
        std::string row;
        if (!rewrite(compiled, bytes(value), limit, row, changed, [&](std::string& out, int count) {
            expand(out, bytes(replacement), compiled, bytes(value), count);
        })) return result_or_false_vector_string::from_false();
        if (changed) output.append(owned(row));
    }
    return result_or_false_vector_string{std::move(output)};
}

result_or_false_vector_string filter(const string& pattern, const string& replacement, const vector_string& input) {
    return filter(pattern, replacement, input, -1);
}

result_or_false_vector_string split(const string& pattern, const string& subject, int64 limit, int64 flags) {
    validate_limit(limit);
    if (flags & 4) throw ValueError("regex_split offset captures are unsupported");
    capture_pattern compiled(pattern);
    if (!compiled.data) return result_or_false_vector_string::from_false();
    vector_string fields;
    const auto text = bytes(subject);
    size_t copied = 0;
    auto append = [&](std::string_view field) {
        if (!(flags & 1) || !field.empty()) fields.append(owned(field));
    };
    if (limit != 1 && !compiled.each(text, 0, [&](int count, size_t start, size_t end) {
        append(text.substr(copied, start - copied));
        if (flags & 2)
            for (int i = 1; i < count; ++i) append(compiled.capture(text, i));
        copied = end;
        return limit <= 0 || fields.size() < limit - 1;
    })) return result_or_false_vector_string::from_false();
    append(text.substr(copied));
    return result_or_false_vector_string{std::move(fields)};
}

result_or_false_vector_string split(const string& pattern, const string& subject, int64 limit) {
    return split(pattern, subject, limit, 0);
}
result_or_false_vector_string split(const string& pattern, const string& subject) {
    return split(pattern, subject, -1, 0);
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

result_or_false_int64 php_matches(const string& pattern, const string& subject, mixed* matches,
                                  int64 flags, int64 offset, bool all) {
    if ((!all && flags != 0) || (all && flags != 0 && flags != 1 && flags != 2))
        throw ValueError("unsupported match flags");
    auto output = mixed::container();
    capture_pattern compiled(pattern);
    int64 hits = 0;
    auto text = bytes(subject);
    bool ok = compiled.data && compiled.each(text, capture_offset(offset, subject.size()), [&](int count, size_t, size_t) {
        ++hits;
        if (matches) {
            auto row = mixed::container();
            capture_fields(compiled, text, count, [&](const auto& key, const string& capture) {
                auto value = mixed::from_string(capture);
                if (all && flags != 2) {
                    auto column = output.has(key) ? output.read(key) : mixed::container();
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
result_or_false_mixed preg_split(const string& pattern, const string& subject, int64 limit, int64 flags) {
    auto fields = split(pattern, subject, limit, flags);
    if (!fields.has_value()) return result_or_false_mixed::from_false();
    auto vec = fields.value();
    auto output = mixed::container();
    for (int64 i = 0; i < vec.size(); ++i) output.append(mixed::from_string(vec.at(i)));
    return result_or_false_mixed{std::move(output)};
}
result_or_false_mixed preg_split(const string& pattern, const string& subject, int64 limit) {
    return preg_split(pattern, subject, limit, 0);
}
result_or_false_mixed preg_split(const string& pattern, const string& subject) {
    return preg_split(pattern, subject, -1, 0);
}

namespace {
result_or_false_mixed select_rows(const string& pattern, const mixed& input, const string* replacement, int64 limit) {
    validate_limit(limit);
    capture_pattern compiled(pattern);
    if (!compiled.data) return result_or_false_mixed::from_false();
    auto output = mixed::container();
    if (input.kind() != boxed_kind::container) return result_or_false_mixed{std::move(output)};
    int64 position = 0, numeric_key;
    string text_key;
    mixed entry;
    const auto stop = input.iteration_limit();
    while (input.iteration_next(position, stop, numeric_key, text_key, entry)) {
        auto subject = entry.get_string();
        if (replacement) {
            int64 changed = 0;
            std::string rewritten;
            if (!rewrite(compiled, bytes(subject), limit, rewritten, changed, [&](std::string& out, int count) {
                expand(out, bytes(*replacement), compiled, bytes(subject), count);
            })) return result_or_false_mixed::from_false();
            if (!changed) continue;
            entry = mixed::from_string(owned(rewritten));
        } else {
            int count = compiled.find(bytes(subject), 0, 0);
            if (count == PCRE2_ERROR_NOMATCH) continue;
            if (count < 0) return result_or_false_mixed::from_false();
        }
        if (numeric_key < 0) output.set(text_key, entry);
        else output.set(numeric_key, entry);
    }
    return result_or_false_mixed{std::move(output)};
}
}

result_or_false_mixed preg_grep(const string& pattern, const mixed& input) {
    return select_rows(pattern, input, nullptr, -1);
}
result_or_false_mixed preg_filter(const string& pattern, const string& replacement, const mixed& input, int64 limit) {
    return select_rows(pattern, input, &replacement, limit);
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
        while (columns.size() < static_cast<size_t>(row.size()))
            columns.push_back(vector_string::filled(i, string{}));
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
