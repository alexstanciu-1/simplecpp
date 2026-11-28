#include "scpp/php/parse_str.hpp"
#include "scpp/exception.hpp"
#include "scpp/string.hpp"

#include <cstdint>
#include <charconv>
#include <cstdio>
#include <limits>
#include <stdexcept>
#include <string>
#include <string_view>
#include <vector>

namespace scpp::php {
namespace {
constexpr unsigned max_input_vars = 1000;
constexpr unsigned max_input_nesting_level = 64;

string bytes(std::string_view value) {
    return string{value.data(), static_cast<int64>(value.size())};
}

[[noreturn]] void key_out_of_range() {
    throw_exception(exception{string{"parse_str(): integer key outside mixed range"}});
}

boxed_text_key classify(std::string_view key) {
    if (!key.empty() && (key == "0" || (key.front() != '0' && !key.starts_with("-0")))) {
        int64 number{};
        auto [end, error] = std::from_chars(key.data(), key.data() + key.size(), number);
        if (error == std::errc{} && end == key.data() + key.size()) {
            if (number < 0 || number > std::numeric_limits<std::int32_t>::max()) key_out_of_range();
            return {number, {}};
        }
    }
    return {-1, bytes(key)};
}

// An empty child component is an append. An empty root never reaches this path.
void store(mixed& table, const std::vector<std::string_view>& path, std::size_t level,
           const string& value, std::string_view original) {
    const auto component = path[level];
    for (auto prefix : {"__Host-", "__Secure-"}) {
        if (component.starts_with(prefix) && !original.starts_with(prefix)) return;
    }
    const bool append = component.empty();
    const auto key = append ? boxed_text_key{} : classify(component);
    mixed child;
    if (level + 1 == path.size()) {
        child = mixed::from_string(value);
    } else {
        if (!append) child = table.source_read(key);
        if (child.kind() != boxed_kind::container) child = mixed::container();
        store(child, path, level + 1, value, original);
    }
    if (append) {
        try { table.append(std::move(child)); }
        catch (const std::overflow_error&) { key_out_of_range(); }
    } else {
        table.source_set(key, child);
    }
}

void register_field(mixed& result, std::string_view name, const string& value) {
    name = name.substr(0, name.find('\0'));
    const auto first = name.find_first_not_of(' ');
    if (first == name.npos) return;
    name.remove_prefix(first);
    const auto bracket = name.find('[');
    std::string root{name.substr(0, bracket)};
    if (root.empty()) return;
    auto normalize = [](std::string& key) {
        for (char& ch : key) if (ch == ' ' || ch == '.' || ch == '[') ch = '_';
    };
    normalize(root);
    std::vector<std::string_view> path{root};
    for (auto pos = bracket; pos < name.size() && name[pos] == '[';) {
        if (path.size() > max_input_nesting_level) {
            const auto key = classify(root);
            if (key.index < 0) result.remove(key.text);
            else result.remove(key.index);
            return;
        }
        const auto end = name.find(']', pos + 1);
        if (end == name.npos) {
            if (path.size() == 1) {
                root.assign(name);
                normalize(root);
                path.front() = root;
            }
            break;
        }
        auto key = name.substr(pos + 1, end - pos - 1);
        if (key.size() == 1 && std::string_view{" \t\n\r\f\v"}.find(key.front()) != key.npos) key = {};
        path.push_back(key);
        pos = end + 1;
    }
    store(result, path, 0, value, name);
}
}

void parse_str(const string& input, mixed& output) {
    std::string_view query{input.data(), static_cast<std::size_t>(input.size())};
    query = query.substr(0, query.find('\0'));
    mixed result = mixed::container();
    unsigned count = 0;
    while (!query.empty()) {
        const auto end = query.find('&');
        const auto field = query.substr(0, end);
        query.remove_prefix(end == query.npos ? query.size() : end + 1);
        if (field.empty()) continue;
        if (++count > max_input_vars) {
            std::fprintf(stderr, "Warning: parse_str(): Input variables exceeded %u.\n", max_input_vars);
            break;
        }
        const auto equal = field.find('=');
        const auto name = str::urldecode(bytes(field.substr(0, equal)));
        const auto value = equal == field.npos ? string{} : str::urldecode(bytes(field.substr(equal + 1)));
        register_field(result, {name.data(), static_cast<std::size_t>(name.size())}, value);
    }
    output = std::move(result);
}
}
