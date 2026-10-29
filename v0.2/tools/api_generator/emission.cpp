#include "runtime.hpp"
#include <algorithm>
#include <set>
#include <tuple>

namespace scpp::api_generation {
namespace {
std::string signature(const scalar_operation &op) {
    const bool mutates = op.form == "compound" || op.form == "prefix" || op.form == "postfix";
    const bool reference = op.form == "compound" || op.form == "prefix";
    auto out = op.result + (reference ? "&" : "") + " operator" + op.symbol + "(" + op.left.cpp
        + (mutates ? "&" : "") + " left";
    if (!op.right.cpp.empty()) out += ", " + op.right.cpp + " right";
    if (op.form == "postfix") out += ", int";
    return out + ")";
}
std::string conversion_input(const runtime_plan &plan, const scalar_conversion &conversion) {
    return conversion.source == plan.string.name ? "const " + conversion.source + "&" : conversion.source;
}
std::pair<std::string, std::string> owned_lifecycle(const std::string &name) {
    const auto declarations = "    " + name + "() noexcept = default;\n    ~" + name + "();\n    " + name + "(const " + name + "&);\n    " + name + "(" + name + "&&) noexcept;\n    " + name + "& operator=(const " + name + "&);\n    " + name + "& operator=(" + name + "&&) noexcept;\n";
    std::string definitions = name + "::~" + name + "() { delete storage_; }\n";
    definitions += name + "::" + name + "(const " + name + "& other) : storage_(other.storage_ ? new storage(*other.storage_) : nullptr) {}\n";
    definitions += name + "::" + name + "(" + name + "&& other) noexcept : storage_(std::exchange(other.storage_, nullptr)) {}\n";
    definitions += name + "& " + name + "::operator=(const " + name + "& other) { if (this != &other) { " + name + " copy(other); std::swap(storage_, copy.storage_); } return *this; }\n";
    definitions += name + "& " + name + "::operator=(" + name + "&& other) noexcept { if (this != &other) { delete storage_; storage_ = std::exchange(other.storage_, nullptr); } return *this; }\n";
    return {declarations, definitions};
}
std::pair<std::string, std::string> emit_carriers(const runtime_plan &plan) {
    if (plan.carriers.empty()) return {};
    const bool errors = std::any_of(plan.carriers.begin(), plan.carriers.end(), [](const auto &c) { return c.family == "result"; });
    std::string header = "#pragma once\n#include \"" + plan.operators_header + "\"\n";
    if (!plan.string.name.empty()) header += "#include \"" + plan.string.header + "\"\n";
    if (!plan.vectors.empty()) header += "#include \"" + plan.vectors_header + "\"\n";
    header += "namespace scpp {\n";
    std::string definitions;
    if (errors) {
        header += "class error_t final {\npublic:\n    error_t() = default;\n    explicit error_t(string message, int64 line = int64{}, string file = string{});\n    static const error_t no_error;\n    bool_t has_error() const noexcept;\n    explicit operator bool() const noexcept;\n    string get_message() const;\n    int64 get_line() const noexcept;\n    string get_file() const;\nprivate:\n    bool present_ = false;\n    string message_;\n    int64 line_;\n    string file_;\n};\n";
        definitions += "const error_t error_t::no_error{};\nerror_t::error_t(string message, int64 line, string file) : present_(true), message_(std::move(message)), line_(line), file_(std::move(file)) {}\nbool_t error_t::has_error() const noexcept { return bool_t{present_}; }\nerror_t::operator bool() const noexcept { return present_; }\nstring error_t::get_message() const { return message_; }\nint64 error_t::get_line() const noexcept { return line_; }\nstring error_t::get_file() const { return file_; }\n";
    }
    for (const auto &c : plan.carriers) {
        const auto &name = c.name, &payload = c.payload;
        const bool result = c.family == "result", nullable = c.family == "nullable", bool_states = c.family == "result_or_bool";
        const auto [life_header, life_definitions] = owned_lifecycle(name);
        header += "class " + name + " final {\npublic:\n" + life_header;
        header += "    explicit " + name + "(" + payload + " value);\n    bool_t has_value() const noexcept;\n    " + payload + " value() const;\n    " + (c.implicit_extraction ? "" : "explicit ") + "operator " + payload + "() const;\n";
        definitions += "struct " + name + "::storage { using content_type = detail::carrier_storage<" + payload
            + (result ? ", error_t" : "") + ">; content_type content; };\n" + life_definitions;
        definitions += name + "::" + name + "(" + payload + " value) : storage_(new storage{storage::content_type{std::move(value)}}) {}\n";
        definitions += "bool_t " + name + "::has_value() const noexcept { return bool_t{storage_ ? storage_->content.has_value() : " + (result ? "true" : "false") + "}; }\n";
        definitions += payload + " " + name + "::value() const { ";
        if (result) definitions += "if (!storage_) return " + payload + "{}; ";
        definitions += "if (!storage_) throw std::runtime_error(\"carrier_no_value\"); return storage_->content.value(); }\n";
        definitions += name + "::operator " + payload + "() const { return value(); }\n";
        if (nullable) {
            header += "    explicit " + name + "(null_t) noexcept;\n";
            definitions += name + "::" + name + "(null_t) noexcept {}\n";
        }
        if (result) {
            header += "    explicit " + name + "(error_t error);\n    bool_t has_error() const noexcept;\n    error_t error() const;\n";
            definitions += name + "::" + name + "(error_t error) : storage_(new storage{storage::content_type::failure(std::move(error))}) {}\n";
            definitions += "bool_t " + name + "::has_error() const noexcept { return bool_t{storage_ && storage_->content.has_error()}; }\n";
            definitions += "error_t " + name + "::error() const { if (!storage_) throw std::runtime_error(\"result_no_error\"); return storage_->content.error(); }\n";
        } else {
            header += "    void reset() noexcept;\n";
            definitions += "void " + name + "::reset() noexcept { delete storage_; storage_ = nullptr; }\n";
            if (!nullable) {
                header += "    static " + name + " from_false() noexcept;\n    bool_t is_false() const noexcept;\n";
                definitions += name + " " + name + "::from_false() noexcept { return {}; }\n";
                definitions += "bool_t " + name + "::is_false() const noexcept { return bool_t{!storage_ || storage_->content.is_false()}; }\n";
            }
            if (bool_states) {
                header += "    static " + name + " from_true();\n    bool_t is_true() const noexcept;\n";
                definitions += name + " " + name + "::from_true() { " + name + " out; out.storage_ = new storage{storage::content_type::sentinel_true()}; return out; }\n";
                definitions += "bool_t " + name + "::is_true() const noexcept { return bool_t{storage_ && storage_->content.is_true()}; }\n";
            }
        }
        header += "private:\n    struct storage;\n    storage* storage_ = nullptr;\n};\n";
    }
    return {header + "}\n", definitions};
}
std::pair<std::string, std::string> emit_vectors(const runtime_plan &plan) {
    if (plan.vectors.empty()) return {};
    std::string header = "#pragma once\n#include \"" + plan.operators_header + "\"\n";
    if (!plan.string.name.empty()) header += "#include \"" + plan.string.header + "\"\n";
    header += "namespace scpp {\nnamespace detail { struct vector_access; }\n";
    for (const auto &v : plan.vectors) header += "class " + v.name + ";\n";
    std::string definitions;
    for (const auto &v : plan.vectors) {
        definitions += "static_assert(std::is_copy_constructible_v<" + v.element + "> && std::is_nothrow_move_constructible_v<" + v.element + "> && std::is_nothrow_move_assignable_v<" + v.element + ">);\n";
        definitions += "struct " + v.name + "::storage { std::vector<" + v.element + "> values; };\n";
    }
    definitions += "namespace detail { struct vector_access {\n";
    for (const auto &v : plan.vectors)
        definitions += "    static const std::vector<" + v.element + ">& read(const " + v.name + "& value) noexcept { static const std::vector<" + v.element + "> empty; return value.storage_ ? value.storage_->values : empty; }\n";
    definitions += "}; }\n";
    for (const auto &v : plan.vectors) {
        const auto &name = v.name, &element = v.element, &count = v.count_type;
        const auto [lifecycle_header, lifecycle_definitions] = owned_lifecycle(name);
        header += "class " + name + " final {\npublic:\n" + lifecycle_header;
        definitions += lifecycle_definitions;
        for (const auto &source : v.casts_from) {
            header += "    explicit " + name + "(const " + source + "&);\n";
            definitions += name + "::" + name + "(const " + source + "& source) { " + name + " converted; for (const auto& item : detail::vector_access::read(source)) converted.append(static_cast<" + element + ">(item)); std::swap(storage_, converted.storage_); }\n";
        }
        header += "    " + count + " size() const;\n    " + plan.boolean_type + " empty() const noexcept;\n    void clear() noexcept;\n";
        header += "    void append(" + element + " value);\n    " + element + " at(" + count + " index) const;\n    void set(" + count + " index, " + element + " value);\n";
        definitions += count + " " + name + "::size() const { return " + count + "{detail::checked_integer_cast<long long>(detail::vector_access::read(*this).size())}; }\n";
        definitions += plan.boolean_type + " " + name + "::empty() const noexcept { return " + plan.boolean_type + "{detail::vector_access::read(*this).empty()}; }\n";
        definitions += "void " + name + "::clear() noexcept { delete storage_; storage_ = nullptr; }\n";
        definitions += "void " + name + "::append(" + element + " value) { if (!storage_) storage_ = new storage; storage_->values.push_back(std::move(value)); }\n";
        definitions += element + " " + name + "::at(" + count + " index) const { const auto& values = detail::vector_access::read(*this); return values[detail::sequence_index(index.native_value(), values.size())]; }\n";
        definitions += "void " + name + "::set(" + count + " index, " + element + " value) { const auto offset = detail::sequence_index(index.native_value(), detail::vector_access::read(*this).size()); storage_->values[offset] = std::move(value); }\n";
        std::vector<std::string> indices{count}, values{element};
        if (v.native_index) {
            indices.push_back("int");
            header += "    " + element + " at(int index) const;\n";
            definitions += element + " " + name + "::at(int index) const { return at(" + count + "{index}); }\n";
        }
        if (!v.native_value.empty()) {
            values.push_back(v.native_value);
            header += "    void append(" + v.native_value + " value);\n";
            definitions += "void " + name + "::append(" + v.native_value + " value) { append(" + element + "{value}); }\n";
        }
        for (const auto &index : indices) for (const auto &value : values) {
            if (index == count && value == element) continue;
            header += "    void set(" + index + " index, " + value + " value);\n";
            definitions += "void " + name + "::set(" + index + " index, " + value + " value) { set("
                + (index == count ? "index" : count + "{index}") + ", "
                + (value == element ? "std::move(value)" : element + "{value}") + "); }\n";
        }
        const auto fences = [&](const std::vector<std::string> &natives) {
            std::set<std::string> deleted;
            for (const auto &native : natives) {
                if (v.native_index && native != "int") {
                    deleted.insert("    void at(" + native + ") const = delete;\n");
                    for (const auto &value : values) deleted.insert("    void set(" + native + ", " + value + ") = delete;\n");
                }
                if (!v.native_value.empty() && native != v.native_value) {
                    deleted.insert("    void append(" + native + ") = delete;\n");
                    for (const auto &index : indices) deleted.insert("    void set(" + index + ", " + native + ") = delete;\n");
                }
            }
            for (const auto &decl : deleted) header += decl;
        };
        fences(native_cpp_spellings());
        if (v.native_index || !v.native_value.empty()) {
            header += "#ifdef __SIZEOF_INT128__\n"; fences({"__int128", "unsigned __int128"}); header += "#endif\n";
        }
        header += "private:\n    struct storage;\n    storage* storage_ = nullptr;\n    friend struct detail::vector_access;\n};\n";
        for (const auto &op : v.operations) {
            const auto symbol = op == "equal" ? "==" : "!=";
            const auto signature = plan.boolean_type + " operator" + symbol + "(const " + name + "& left, const " + name + "& right)";
            header += signature + ";\n";
            definitions += signature + " { const auto& a = detail::vector_access::read(left); const auto& b = detail::vector_access::read(right); return " + plan.boolean_type + "{"
                + (op == "equal" ? "" : "!") + "std::equal(a.begin(), a.end(), b.begin(), b.end(), [](const " + element + "& x, const " + element + "& y) { return static_cast<bool>(x == y); })}; }\n";
        }
    }
    return {header + "}\n", definitions};
}
std::pair<std::string, std::string> emit_string(const runtime_plan &plan) {
    const auto &name = plan.string.name;
    if (name.empty()) return {};
    const auto &count = plan.string.count_type;
    const auto &boolean = plan.boolean_type;
    std::string header = "#pragma once\n#include \"" + plan.types_header + "\"\n#include \"" + plan.values_header
        + "\"\nnamespace scpp {\nnamespace detail { struct string_access; }\nclass " + name + " final {\npublic:\n";
    const auto [lifecycle_header, lifecycle_definitions] = owned_lifecycle(name);
    header += lifecycle_header;
    header += "    explicit " + name + "(const char*);\n    explicit " + name + "(const char*, " + count + ");\n";
    for (const auto &native : native_cpp_spellings()) header += "    explicit " + name + "(" + native + ") = delete;\n";
    header += "#ifdef __SIZEOF_INT128__\n    explicit " + name + "(__int128) = delete;\n    explicit " + name + "(unsigned __int128) = delete;\n#endif\n";
    for (const auto &c : plan.conversions) if (c.target == name)
        header += "    explicit " + name + "(" + conversion_input(plan, c) + " value);\n";
    header += "    " + count + " size() const;\n    " + boolean + " empty() const noexcept;\n    const char* data() const noexcept;\n    void append(const " + name + "&);\n    void clear() noexcept;\n";
    header += "private:\n    struct storage;\n    storage* storage_ = nullptr;\n    friend struct detail::string_access;\n};\n";
    std::string definitions = "struct " + name + "::storage { std::string bytes; };\n";
    definitions += "namespace detail { struct string_access {\n    static const std::string& read(const " + name + "& value) noexcept { static const std::string empty; return value.storage_ ? value.storage_->bytes : empty; }\n}; }\n";
    definitions += lifecycle_definitions;
    definitions += name + "::" + name + "(const char* bytes) : storage_(new storage{detail::string_from_c_string(bytes)}) {}\n";
    definitions += name + "::" + name + "(const char* bytes, " + count + " count) : storage_(new storage{detail::string_from_bytes(bytes, count.native_value())}) {}\n";
    definitions += count + " " + name + "::size() const { return " + count + "{detail::checked_integer_cast<long long>(detail::string_access::read(*this).size())}; }\n";
    definitions += boolean + " " + name + "::empty() const noexcept { return " + boolean + "{detail::string_access::read(*this).empty()}; }\n";
    definitions += "const char* " + name + "::data() const noexcept { return detail::string_access::read(*this).data(); }\n";
    definitions += "void " + name + "::append(const " + name + "& other) { if (!storage_) { " + name + " copy(other); std::swap(storage_, copy.storage_); } else { storage_->bytes.append(detail::string_access::read(other)); } }\n";
    definitions += "void " + name + "::clear() noexcept { delete storage_; storage_ = nullptr; }\n";
    for (const auto &op : plan.string.operations) {
        const auto &symbol = op.symbol;
        const auto signature = op.operation == "add" ? name + " operator+(" + name + " left, const " + name + "& right)"
            : op.form == "compound" ? name + "& operator+=(" + name + "& left, const " + name + "& right)"
            : boolean + " operator" + symbol + "(const " + name + "& left, const " + name + "& right) noexcept";
        header += signature + ";\n";
        definitions += signature + " { ";
        if (op.operation == "add" || op.form == "compound") definitions += "left.append(right); return left;";
        else definitions += "return " + boolean + "{detail::string_access::read(left) " + symbol + " detail::string_access::read(right)};";
        definitions += " }\n";
    }
    return {header + "}\n", definitions};
}
std::string emit_values(const runtime_plan &plan) {
    std::string out = "#pragma once\nnamespace scpp {\n";
    if (!plan.string.name.empty()) out += "class " + plan.string.name + ";\n";
    for (const auto &type : plan.types) out += "class " + type.name + ";\n";
    for (const auto &type : plan.values) out += "class " + type.name + ";\n";
    for (const auto &type : plan.values) {
        out += "class " + type.name + " final {\npublic:\n    constexpr " + type.name + "() noexcept = default;\n";
        for (const auto &c : plan.conversions) if (c.target == type.name)
            out += "    explicit " + type.name + "(" + conversion_input(plan, c) + " value)" + (c.can_fail ? "" : " noexcept") + ";\n";
        if (type.kind == "floating") {
            for (const auto &native : native_cpp_spellings())
                if (!std::any_of(plan.conversions.begin(), plan.conversions.end(), [&](const auto &c) {
                    return c.target == type.name && c.native_source && c.source == native;
                })) out += "    explicit " + type.name + "(" + native + ") = delete;\n";
            out += "#ifdef __SIZEOF_INT128__\n    explicit " + type.name + "(__int128) = delete;\n    explicit " + type.name + "(unsigned __int128) = delete;\n#endif\n";
            out += "    constexpr double native_value() const noexcept { return value_; }\nprivate:\n    double value_ = 0.0;\n";
        }
        if (type.kind == "boolean") {
            out += "    explicit constexpr " + type.name + "(bool value) noexcept : value_(value) {}\n";
            for (const auto &native : native_cpp_spellings()) if (native != "bool")
                out += "    explicit " + type.name + "(" + native + ") = delete;\n";
            out += "#ifdef __SIZEOF_INT128__\n    explicit " + type.name + "(__int128) = delete;\n    explicit " + type.name + "(unsigned __int128) = delete;\n#endif\n";
            // Fence pointer-to-bool as well as arithmetic-to-bool fallback.
            out += "    explicit " + type.name + "(const void*) = delete;\n";
            out += "    explicit constexpr operator bool() const noexcept { return value_; }\n";
            out += "    constexpr bool native_value() const noexcept { return value_; }\nprivate:\n    bool value_ = false;\n";
        }
        out += "};\n";
    }
    for (const auto &type : plan.values) {
        if (type.kind == "null") out += "inline constexpr " + type.name + " null{};\n";
        for (const auto &op : type.operations) {
            if (op == "not") out += "constexpr " + plan.boolean_type + " operator!(" + type.name
                + " value) noexcept { return " + plan.boolean_type + "{!value.native_value()}; }\n";
            else {
                const auto symbol = op == "equal" ? "==" : "!=";
                out += "constexpr " + plan.boolean_type + " operator" + symbol + "(" + type.name;
                if (type.kind == "null") out += ", " + type.name + ") noexcept { return " + plan.boolean_type
                    + "{" + (op == "equal" ? "true" : "false") + "}; }\n";
                else out += " left, " + type.name + " right) noexcept { return " + plan.boolean_type
                    + "{left.native_value() " + symbol + " right.native_value()}; }\n";
            }
        }
    }
    for (const auto &[alias, target] : plan.aliases)
        if (std::any_of(plan.values.begin(), plan.values.end(), [&](const auto &type) { return type.name == target; }))
            out += "using " + alias + " = " + target + ";\n";
    return out + "}\n";
}
}
std::map<std::string, std::string> emit_runtime(const runtime_plan &plan) {
    const std::string banner = "// Generated from reviewed runtime metadata. Do not edit.\n";
    std::string declarations = banner + "#pragma once\nnamespace scpp {\nnamespace detail { struct integer_access; }\n";
    std::string definitions = banner + "#include \"" + plan.operators_header + "\"\n#include \"scalar_semantics.hpp\"\n";
    if (!plan.string.name.empty()) definitions += "#include \"" + plan.string.header + "\"\n#include \"sequence_semantics.hpp\"\n";
    if (!plan.vectors.empty()) definitions += "#include \"" + plan.vectors_header + "\"\n#include \"sequence_semantics.hpp\"\n#include <vector>\n#include <algorithm>\n";
    if (!plan.carriers.empty()) definitions += "#include \"" + plan.carriers_header + "\"\n#include \"carrier_semantics.hpp\"\n";
    definitions += "namespace scpp {\n";
    const auto [string_header, string_definitions] = emit_string(plan);
    definitions += string_definitions;
    const auto [vectors_header, vectors_definitions] = emit_vectors(plan);
    definitions += vectors_definitions;
    const auto [carriers_header, carriers_definitions] = emit_carriers(plan);
    definitions += carriers_definitions;
    definitions += "namespace detail { struct integer_access { template<class T, class Rep> static T make(Rep value) { T out; out.value_ = value; return out; } }; }\n";
    if (!plan.string.name.empty()) declarations += "class " + plan.string.name + ";\n";
    for (const auto &t : plan.types) declarations += "class " + t.name + ";\n";
    for (const auto &t : plan.values) declarations += "class " + t.name + ";\n";
    const auto representation = [&](const std::string &name) {
        const auto integer = std::find_if(plan.types.begin(), plan.types.end(), [&](const auto &t) { return t.name == name; });
        if (integer != plan.types.end()) return integer->representation;
        if (!plan.boolean_type.empty() && name == plan.boolean_type) return std::string("bool");
        if (!plan.floating_type.empty() && name == plan.floating_type) return std::string("double");
        throw std::runtime_error("Missing prepared scalar representation: " + name);
    };
    if (!plan.floating_type.empty()) {
        definitions += "static_assert(std::numeric_limits<double>::is_iec559 && std::numeric_limits<double>::radix == 2 && std::numeric_limits<double>::digits == 53 && std::numeric_limits<double>::max_exponent == 1024);\n";
        definitions += "static_assert(sizeof(" + plan.floating_type + ") == sizeof(double));\n";
    }
    for (const auto &c : plan.conversions) {
        if (c.source == plan.string.name) {
            const auto call = "detail::" + c.implementation + (c.implementation == "parse_decimal" ? "<" + representation(c.target) + ">" : "")
                + "(detail::string_access::read(value))";
            definitions += c.target + "::" + c.target + "(" + conversion_input(plan, c) + " value) : value_(" + call + ") {}\n";
            continue;
        }
        if (c.target == plan.string.name) {
            definitions += c.target + "::" + c.target + "(" + conversion_input(plan, c) + " value) : storage_(new storage{detail::"
                + c.implementation + "(value.native_value())}) {}\n";
            continue;
        }
        const auto input = c.native_source ? "value" : "value.native_value()";
        definitions += c.target + "::" + c.target + "(" + conversion_input(plan, c) + " value)" + (c.can_fail ? "" : " noexcept")
            + " : value_(detail::" + c.implementation + "<" + representation(c.target) + ">(" + input + ")) {}\n";
    }
    std::set<std::string> natives;
    for (const auto &n : plan.native_inputs) {
        natives.insert(n.cpp);
        definitions += "static_assert(std::numeric_limits<" + n.cpp + ">::digits == " + std::to_string(n.digits) + ");\n";
        definitions += "static_assert(std::numeric_limits<" + n.cpp + ">::is_signed == " + (n.is_signed ? "true" : "false") + ");\n";
    }
    for (const auto &t : plan.types) {
        declarations += "class " + t.name + " final {\npublic:\n    constexpr " + t.name + "() noexcept = default;\n";
        for (const auto &c : plan.conversions) if (c.target == t.name)
            declarations += "    explicit " + t.name + "(" + conversion_input(plan, c) + " value)" + (c.can_fail ? "" : " noexcept") + ";\n";
        // Deleted exact matches prevent standard conversions from bypassing admission.
        for (const auto &input : native_cpp_spellings()) {
            if (!natives.contains(input)) declarations += "    explicit " + t.name + "(" + input + ") = delete;\n";
        }
        declarations += "#ifdef __SIZEOF_INT128__\n    explicit " + t.name + "(__int128) = delete;\n    explicit " + t.name + "(unsigned __int128) = delete;\n#endif\n";
        declarations += "    constexpr " + t.representation + " native_value() const noexcept { return value_; }\nprivate:\n    "
            + "friend struct detail::integer_access;\n    " + t.representation + " value_ = 0;\n};\n";
        definitions += "static_assert(sizeof(" + t.name + ") == sizeof(" + t.representation + "));\n";
        definitions += "static_assert(alignof(" + t.name + ") == alignof(" + t.representation + "));\n";
        definitions += "static_assert(std::numeric_limits<" + t.representation + ">::digits == " + std::to_string(t.bits - unsigned(t.is_signed)) + ");\n";
    }
    for (const auto &[alias, target] : plan.aliases)
        if (std::any_of(plan.types.begin(), plan.types.end(), [&](const auto &type) { return type.name == target; }))
            declarations += "using " + alias + " = " + target + ";\n";
    declarations += "}\n";
    std::string operators = banner + "#pragma once\n#include \"" + plan.types_header + "\"\n#include \"" + plan.values_header + "\"\nnamespace scpp {\n";
    std::string bridge = banner + "#pragma once\n#include \"" + plan.operators_header + "\"\nnamespace scpp {\n";
    std::set<std::tuple<std::string, std::string, std::string>> emitted;
    std::set<std::string> bridge_symbols;
    for (const auto &op : plan.operations) {
        emitted.emplace(op.symbol, op.left.cpp, op.right.cpp);
        const auto declaration = signature(op);
        if (op.left.native || op.right.native) {
            bridge_symbols.insert(op.symbol);
            const auto left = op.form == "compound" ? "left" : "static_cast<" + op.execution + ">(left)";
            bridge += "inline " + declaration + " {\n    return " + left + " " + op.symbol
                + " static_cast<" + op.execution + ">(right);\n}\n";
            continue;
        }
        operators += declaration + ";\n";
        if (op.promotion == "exact_integer_comparison") {
            definitions += declaration + " {\n    return " + op.result + "{detail::" + op.implementation
                + "(left.native_value(), right.native_value())};\n}\n";
            continue;
        }
        const auto execution_rep = representation(op.execution);
        std::string call = "detail::" + op.implementation + "<" + execution_rep + ">(";
        if (op.right.cpp.empty()) call += "static_cast<" + execution_rep + ">(left.native_value())";
        else if (op.promotion == "left_with_checked_count") call += "left.native_value(), right.native_value()";
        else call += "static_cast<" + execution_rep + ">(left.native_value()), static_cast<"
            + execution_rep + ">(right.native_value())";
        call += ")";
        if (op.result == plan.boolean_type) {
            definitions += declaration + " {\n    return " + op.result + "{" + call + "};\n}\n";
            continue;
        }
        const auto result = op.execution == plan.floating_type ? op.execution + "{" + call + "}"
            : "detail::integer_access::make<" + op.execution + ">(" + call + ")";
        definitions += declaration + " {\n";
        if (op.form == "compound") definitions += "    left = static_cast<" + op.left.cpp + ">(" + result + ");\n    return left;\n";
        else if (op.form == "prefix") definitions += "    left = " + result + ";\n    return left;\n";
        else if (op.form == "postfix") definitions += "    const auto old = left;\n    left = " + result + ";\n    return old;\n";
        else definitions += "    return " + result + ";\n";
        definitions += "}\n";
    }

    // An absent native signature must not fall through to a lossy standard conversion.
    // These declarations have no implementation bodies and are not admitted operations.
    std::vector<std::string> numeric_types;
    for (const auto &t : plan.types) numeric_types.push_back(t.name);
    if (!plan.floating_type.empty()) numeric_types.push_back(plan.floating_type);
    for (const auto &symbol : bridge_symbols) for (const auto &name : numeric_types) {
        const auto reject = [&](const std::string &native) {
            if (!emitted.contains({symbol, name, native})) bridge += "void operator" + symbol + "(" + name + ", " + native + ") = delete;\n";
            if (!emitted.contains({symbol, native, name})) bridge += "void operator" + symbol + "(" + native + ", " + name + ") = delete;\n";
        };
        for (const auto &native : native_cpp_spellings()) reject(native);
        bridge += "#ifdef __SIZEOF_INT128__\n";
        reject("__int128"); reject("unsigned __int128");
        bridge += "#endif\n";
    }
    bridge += "}\n"; operators += "}\n"; definitions += "}\n";
    std::string short_names = banner + "#pragma once\n#include \"" + plan.operators_header + "\"\n";
    if (!plan.string.name.empty()) short_names += "#include \"" + plan.string.header + "\"\nusing scpp::" + plan.string.name + ";\n";
    if (!plan.vectors.empty()) short_names += "#include \"" + plan.vectors_header + "\"\n";
    for (const auto &v : plan.vectors) short_names += "using scpp::" + v.name + ";\n";
    if (!plan.carriers.empty()) short_names += "#include \"" + plan.carriers_header + "\"\n";
    for (const auto &c : plan.carriers) short_names += "using scpp::" + c.name + ";\n";
    for (const auto &t : plan.types) short_names += "using scpp::" + t.name + ";\n";
    for (const auto &type : plan.values) {
        short_names += "using scpp::" + type.name + ";\n";
        if (type.kind == "null") short_names += "using scpp::null;\n";
    }
    for (const auto &alias : plan.short_aliases) short_names += "using scpp::" + alias + ";\n";
    std::map<std::string, std::string> files = {
        {"public/" + plan.values_header, banner + emit_values(plan)},
        {"public/" + plan.types_header, declarations}, {"public/" + plan.operators_header, operators},
        {"public/" + plan.short_names_header, short_names}, {"public/" + plan.native_bridge_header, bridge}, {"src/runtime.cpp", definitions},
        {"prepared.json", json_text(describe(plan)) + "\n"}};
    if (!plan.string.name.empty()) files["public/" + plan.string.header] = banner + string_header;
    if (!plan.vectors.empty()) files["public/" + plan.vectors_header] = banner + vectors_header;
    if (!plan.carriers.empty()) files["public/" + plan.carriers_header] = banner + carriers_header;
    emit_ownership(plan, files);
    value inventory = value::array();
    for (const auto &[path, contents] : files) inventory.items.emplace_back(path);
    files["inventory.json"] = json_text(inventory) + "\n";
    return files;
}
}
