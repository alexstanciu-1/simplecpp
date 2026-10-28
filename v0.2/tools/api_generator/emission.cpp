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
std::pair<std::string, std::string> emit_string(const runtime_plan &plan) {
    const auto &name = plan.string.name;
    if (name.empty()) return {};
    const auto &count = plan.string.count_type;
    const auto &boolean = plan.boolean_type;
    std::string header = "#pragma once\n#include \"" + plan.types_header + "\"\n#include \"" + plan.values_header
        + "\"\nnamespace scpp {\nnamespace detail { struct string_access; }\nclass " + name + " final {\npublic:\n";
    header += "    " + name + "() noexcept = default;\n    ~" + name + "();\n    " + name + "(const " + name + "&);\n    " + name + "(" + name + "&&) noexcept;\n";
    header += "    " + name + "& operator=(const " + name + "&);\n    " + name + "& operator=(" + name + "&&) noexcept;\n";
    header += "    explicit " + name + "(const char*);\n    explicit " + name + "(const char*, " + count + ");\n";
    for (const auto &native : native_cpp_spellings()) header += "    explicit " + name + "(" + native + ") = delete;\n";
    header += "#ifdef __SIZEOF_INT128__\n    explicit " + name + "(__int128) = delete;\n    explicit " + name + "(unsigned __int128) = delete;\n#endif\n";
    for (const auto &c : plan.conversions) if (c.target == name)
        header += "    explicit " + name + "(" + conversion_input(plan, c) + " value);\n";
    header += "    " + count + " size() const;\n    " + boolean + " empty() const noexcept;\n    const char* data() const noexcept;\n    void append(const " + name + "&);\n    void clear() noexcept;\n";
    header += "private:\n    struct storage;\n    storage* storage_ = nullptr;\n    friend struct detail::string_access;\n};\n";
    std::string definitions = "struct " + name + "::storage { std::string bytes; };\n";
    definitions += "namespace detail { struct string_access {\n    static const std::string& read(const " + name + "& value) noexcept { static const std::string empty; return value.storage_ ? value.storage_->bytes : empty; }\n}; }\n";
    definitions += name + "::~" + name + "() { delete storage_; }\n";
    definitions += name + "::" + name + "(const " + name + "& other) : storage_(other.storage_ ? new storage{other.storage_->bytes} : nullptr) {}\n";
    definitions += name + "::" + name + "(" + name + "&& other) noexcept : storage_(std::exchange(other.storage_, nullptr)) {}\n";
    definitions += name + "& " + name + "::operator=(const " + name + "& other) { if (this != &other) { " + name + " copy(other); std::swap(storage_, copy.storage_); } return *this; }\n";
    definitions += name + "& " + name + "::operator=(" + name + "&& other) noexcept { if (this != &other) { delete storage_; storage_ = std::exchange(other.storage_, nullptr); } return *this; }\n";
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
    definitions += "namespace scpp {\n";
    const auto [string_header, string_definitions] = emit_string(plan);
    definitions += string_definitions;
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
    value inventory = value::array();
    for (const auto &[path, contents] : files) inventory.items.emplace_back(path);
    files["inventory.json"] = json_text(inventory) + "\n";
    return files;
}
}
