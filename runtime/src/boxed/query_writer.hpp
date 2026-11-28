#pragma once
#include "scpp/exception.hpp"
#include <charconv>
#include <cmath>
#include <cstdio>
#include <string>
#include <string_view>
#include <vector>

namespace scpp::php {
namespace {
std::string_view query_bytes(const string& value) {
    return {value.data(), static_cast<std::size_t>(value.size())};
}

// PHP precision=14 uses general notation with an uppercase exponent and an
// explicit decimal fraction for a single-digit scientific mantissa.
std::string query_float(double number) {
    if (std::isnan(number)) {
        std::fputs("Warning: unexpected NAN value was coerced to string\n", stderr);
        return "NAN";
    }
    if (std::isinf(number)) return number < 0 ? "-INF" : "INF";
    char buffer[64];
    auto end = std::to_chars(buffer, buffer + sizeof(buffer), number, std::chars_format::general, 14).ptr;
    std::string text(buffer, end);
    const auto exponent = text.find('e');
    if (exponent == text.npos) return text;
    std::string result = text.substr(0, exponent);
    if (result.find('.') == result.npos) result += ".0";
    result += 'E';
    result += text[exponent + 1];
    auto digits = text.find_first_not_of('0', exponent + 2);
    result += digits == text.npos ? "0" : text.substr(digits);
    return result;
}

class query_writer {
    std::string output_;
    std::string path_;
    std::string_view prefix_;
    std::string_view separator_;
    bool raw_;

    void encoded(std::string& dest, const string& text) const {
        auto encoded = raw_ ? str::rawurlencode(text) : str::urlencode(text);
        dest.append(query_bytes(encoded));
    }
    void key(int64 index, bool root) {
        if (root) path_.append(prefix_);
        path_ += std::to_string(index);
    }
    void key(const string& text, bool) { encoded(path_, text); }
    void pair() {
        if (!output_.empty()) output_.append(separator_);
        output_ += path_;
        output_ += '=';
    }
    void leaf(const string& value) { pair(); encoded(output_, value); }
    void leaf(bool value) { pair(); output_ += value ? '1' : '0'; }
    void leaf(int64 value) { pair(); output_ += std::to_string(value); }
    void leaf(uint8 value) { leaf(static_cast<int64>(value)); }
    void leaf(double value) {
        const auto text = query_float(value);
        leaf(string{text.data(), static_cast<int64>(text.size())});
    }
    [[noreturn]] static void unexpected_kind() {
        throw_exception(exception{string{"http_build_query(): expected a mixed table with scalar or table entries"}});
    }

public:
    query_writer(const string& prefix, const nullable_string& separator, int64 mode)
        : prefix_(query_bytes(prefix)),
          separator_(separator.has_value() ? query_bytes(separator.checked_ref()) : "&"),
          raw_(mode == 2) {}

    template<class Key, class Value, class Collection>
    void typed(const Collection& data) {
        Key index{};
        Value value{};
        int64 position = 0;
        const auto limit = data.iteration_limit();
        while (data.iteration_next(position, limit, index, value)) {
            path_.clear();
            key(index, true);
            leaf(value);
        }
    }

    void table(const mixed& data) {
        if (data.kind() != boxed_kind::container) unexpected_kind();
        struct frame { mixed table; int64 position, limit; std::size_t path_length; };
        std::vector<frame> frames{{data, 0, data.iteration_limit(), 0}};
        while (!frames.empty()) {
            auto& current = frames.back();
            mixed value;
            string text;
            int64 index;
            if (!current.table.iteration_next(current.position, current.limit, index, text, value)) {
                frames.pop_back();
                continue;
            }
            if (value.kind() == boxed_kind::null) continue;
            path_.resize(current.path_length);
            const bool root = frames.size() == 1;
            if (!root) path_ += "%5B";
            if (index < 0) key(text, root);
            else key(index, root);
            if (!root) path_ += "%5D";
            switch (value.kind()) {
            case boxed_kind::container:
                frames.push_back({value, 0, value.iteration_limit(), path_.size()});
                break;
            case boxed_kind::string: leaf(value.get_string()); break;
            case boxed_kind::boolean: leaf(value.get_boolean()); break;
            case boxed_kind::integer: leaf(value.get_integer()); break;
            case boxed_kind::floating: leaf(value.get_floating()); break;
            default: unexpected_kind();
            }
        }
    }

    string finish() const { return string{output_.data(), static_cast<int64>(output_.size())}; }
};
}
}
