#include "runtime.hpp"
#include <algorithm>
#include <memory>
#include <set>
#include <sstream>

namespace scpp::api_generation {
namespace {
void require(bool ok, const std::string &message) {
    if (!ok) throw std::runtime_error("Ownership: " + message);
}
const std::vector<value> &array(const value &v) {
    require(v.type == value::kind::array, "expected array"); return v.items;
}
void literal(const value &v, const char *field, const char *expected) {
    require(v.at(field).text() == expected, std::string("unsupported ") + field);
}
bool identifier(const std::string &s) {
    static const std::string keywords = " alignas alignof and and_eq asm auto bitand bitor bool break case catch char char8_t char16_t char32_t class compl concept const consteval constexpr constinit const_cast continue co_await co_return co_yield decltype default delete do double dynamic_cast else enum explicit export extern false float for friend goto if inline int long mutable namespace new noexcept not not_eq nullptr operator or or_eq private protected public register reinterpret_cast requires return short signed sizeof static static_assert static_cast struct switch template this thread_local throw true try typedef typeid typename union unsigned using virtual void volatile wchar_t while xor xor_eq ";
    return !s.empty() && s[0] >= 'a' && s[0] <= 'z' && s.find("__") == std::string::npos &&
        s != "std" && s != "detail" && keywords.find(" " + s + " ") == std::string::npos &&
        std::all_of(s.begin(), s.end(), [](char c) { return (c >= 'a' && c <= 'z') || (c >= '0' && c <= '9') || c == '_'; });
}
std::string path(const value &v) {
    auto s = v.text();
    require(s.starts_with("scpp/") && s.ends_with(".hpp") && s.find("..") == std::string::npos &&
        s.find("//") == std::string::npos && std::all_of(s.begin(), s.end(), [](char c) {
            return (c >= 'a' && c <= 'z') || (c >= '0' && c <= '9') || c == '/' || c == '_' || c == '.';
        }), "invalid header");
    return s;
}
value number(unsigned n) { value v; v.type = value::kind::number; v.scalar = std::to_string(n); return v; }
std::string join(const std::vector<std::string> &types, bool arguments) {
    std::string s;
    for (unsigned i = 0; i < types.size(); ++i) {
        if (i) s += ", ";
        s += arguments ? "arg" + std::to_string(i) : types[i] + " arg" + std::to_string(i);
    }
    return s;
}
}

void prepare_ownership(const value &metadata, const value &publication, runtime_plan &plan) {
    if (!metadata.has("ownership")) {
        require(!publication.has("ownership"), "missing metadata"); return;
    }
    const auto &m = metadata.at("ownership");
    fields_only(m, "binding families default construction access empty_access weak_access payload", "ownership metadata");
    literal(m, "binding", "std_smart_pointer_inline"); literal(m, "default", "empty");
    literal(m, "construction", "explicit_factory"); literal(m, "access", "checked_borrow");
    literal(m, "empty_access", "throw_runtime_error"); literal(m, "weak_access", "explicit_lock");
    literal(m, "payload", "registered_record");
    std::set<std::string> families;
    for (const auto &f : array(m.at("families"))) require(families.insert(f.text()).second, "duplicate family");
    require(families == std::set<std::string>{"shared", "weak", "unique"}, "unsupported families");
    if (!publication.has("ownership")) return;
    const auto &p = publication.at("ownership");
    fields_only(p, "header records", "ownership publication");
    auto &o = plan.ownership;
    o.header = path(p.at("header"));
    std::set<std::string> headers{plan.types_header, plan.values_header, plan.operators_header,
        plan.native_bridge_header, plan.short_names_header, plan.string.header, plan.vectors_header, plan.carriers_header};
    require(headers.insert(o.header).second, "conflicting header");
    require(!plan.boolean_type.empty(), "bool_t must be published");
    std::set<std::string> names, inputs;
    for (const auto &t : plan.types) { names.insert(t.name); inputs.insert(t.name); }
    for (const auto &t : plan.values) { names.insert(t.name); inputs.insert(t.name); }
    for (const auto &v : plan.vectors) names.insert(v.name);
    for (const auto &c : plan.carriers) names.insert(c.name);
    for (const auto &[name, target] : plan.aliases) names.insert(name);
    names.insert(plan.string.name); names.insert("error_t"); names.insert("null");
    for (const auto &r : array(p.at("records"))) {
        fields_only(r, "name header constructors families", "ownership record");
        ownership_record record; record.name = r.at("name").text(); record.header = path(r.at("header"));
        require(identifier(record.name) && names.insert(record.name).second, "invalid or conflicting record name");
        require(!headers.contains(record.header), "record header conflicts with generated header");
        std::set<std::string> requested;
        for (const auto &f : array(r.at("families"))) {
            require(families.contains(f.text()) && requested.insert(f.text()).second, "invalid or duplicate family");
            require(names.insert(f.text() + "_" + record.name).second, "conflicting specialization");
        }
        require(!requested.empty(), "empty family request");
        require(!requested.contains("weak") || requested.contains("shared"), "weak requires shared publication");
        record.families.assign(requested.begin(), requested.end());
        std::set<std::vector<std::string>> signatures;
        for (const auto &args : array(r.at("constructors"))) {
            std::vector<std::string> signature;
            for (const auto &arg : array(args)) {
                require(inputs.contains(arg.text()), "constructor argument must be a published scalar");
                signature.push_back(arg.text());
            }
            require(signatures.insert(signature).second, "duplicate constructor");
        }
        require(!signatures.empty(), "record needs constructor signatures");
        record.constructors.assign(signatures.begin(), signatures.end());
        o.records.push_back(record);
    }
    require(!o.records.empty(), "empty record publication");
    std::sort(o.records.begin(), o.records.end(), [](const auto &a, const auto &b) { return a.name < b.name; });
    // Native-toolchain binding, checked again against each concrete emitted type.
    o.shared_size = sizeof(std::shared_ptr<void>); o.shared_align = alignof(std::shared_ptr<void>);
    o.weak_size = sizeof(std::weak_ptr<void>); o.weak_align = alignof(std::weak_ptr<void>);
    o.unique_size = sizeof(std::unique_ptr<unsigned char>); o.unique_align = alignof(std::unique_ptr<unsigned char>);
}

value describe_ownership(const ownership_plan &o) {
    auto out = value::object(); out["header"] = o.header; out["binding"] = "std_smart_pointer_inline";
    out["empty_access"] = "std::runtime_error:ownership_empty_access";
    out["weak_lock_failure"] = "empty_shared"; out["copy_allocation"] = "none";
    out["weak_condition"] = "not_published"; out["payload_access"] = "checked_borrow_no_implicit_extraction";
    out["records"] = value::array();
    for (const auto &r : o.records) {
        auto row = value::object(); row["name"] = r.name; row["header"] = r.header;
        row["constructors"] = value::array(); row["families"] = value::array();
        for (const auto &args : r.constructors) { auto a = value::array(); for (const auto &t : args) a.items.emplace_back(t); row["constructors"].items.push_back(a); }
        for (const auto &f : r.families) {
            auto family = value::object(); family["name"] = f + "_" + r.name; family["family"] = f;
            family["default"] = "empty"; family["move"] = "transfer_source_empty";
            family["copy"] = f == "unique" ? "deleted" : "retain_identity";
            family["size"] = number(f == "shared" ? o.shared_size : f == "weak" ? o.weak_size : o.unique_size);
            family["alignment"] = number(f == "shared" ? o.shared_align : f == "weak" ? o.weak_align : o.unique_align);
            row["families"].items.push_back(family);
        }
        out["records"].items.push_back(row);
    }
    return out;
}

void emit_ownership(const runtime_plan &plan, std::map<std::string, std::string> &files) {
    const auto &o = plan.ownership;
    if (o.records.empty()) return;
    std::string h = "// Generated ownership API; edit metadata/publication.\n#pragma once\n#include \"" + plan.types_header + "\"\n#include \"" + plan.values_header + "\"\n";
    std::set<std::string> includes;
    for (const auto &r : o.records) includes.insert(r.header);
    for (const auto &header : includes) h += "#include \"" + header + "\"\n";
    h += "namespace scpp {\nnamespace detail { struct ownership_access; }\n";
    std::string cpp = "// Generated ownership definitions.\n#include \"" + o.header + "\"\n#include \"ownership_semantics.hpp\"\nnamespace scpp {\n";
    for (const auto &r : o.records) {
        cpp += "static_assert(std::is_class_v<" + r.name + "> && std::is_nothrow_destructible_v<" + r.name + ">);\n";
        for (const auto &f : r.families) h += "class " + f + "_" + r.name + ";\n";
        // Shared must be complete before the weak member declarations/definitions.
        for (const auto &f : r.families) {
            const auto n = f + "_" + r.name;
            const auto native = "std::" + f + "_ptr<" + r.name + ">";
            const auto shared = "shared_" + r.name;
            const auto size = f == "shared" ? o.shared_size : f == "weak" ? o.weak_size : o.unique_size;
            const auto alignment = f == "shared" ? o.shared_align : f == "weak" ? o.weak_align : o.unique_align;
            const auto get = [&](const std::string &obj) { return "detail::ownership_access::get<" + native + ">(" + obj + ")"; };
            const auto self = get("*this");
            h += "class " + n + " final {\npublic:\n    " + n + "() noexcept;\n    ~" + n + "() noexcept;\n";
            h += "    " + n + "(" + n + "&& other) noexcept;\n    " + n + "& operator=(" + n + "&& other) noexcept;\n";
            h += "    " + n + "(const " + n + "& other)" + (f == "unique" ? " = delete" : " noexcept") + ";\n";
            h += "    " + n + "& operator=(const " + n + "& other)" + (f == "unique" ? " = delete" : " noexcept") + ";\n";
            h += "    void reset() noexcept;\n    void swap(" + n + "& other) noexcept;\n";
            cpp += "static_assert(sizeof(" + native + ") == " + std::to_string(size) + " && alignof(" + native + ") == " + std::to_string(alignment) + ");\n";
            cpp += "static_assert(sizeof(" + n + ") == sizeof(" + native + ") && alignof(" + n + ") == alignof(" + native + "));\n";
            cpp += n + "::" + n + "() noexcept { detail::ownership_access::construct<" + native + ">(*this); }\n";
            cpp += n + "::~" + n + "() noexcept { detail::ownership_access::destroy<" + native + ">(*this); }\n";
            cpp += n + "::" + n + "(" + n + "&& other) noexcept { detail::ownership_access::construct<" + native + ">(*this, std::move(" + get("other") + ")); }\n";
            cpp += n + "& " + n + "::operator=(" + n + "&& other) noexcept { if (this != &other) " + self + " = std::move(" + get("other") + "); return *this; }\n";
            if (f != "unique") {
                cpp += n + "::" + n + "(const " + n + "& other) noexcept { detail::ownership_access::construct<" + native + ">(*this, " + get("other") + "); }\n";
                cpp += n + "& " + n + "::operator=(const " + n + "& other) noexcept { " + self + " = " + get("other") + "; return *this; }\n";
            }
            cpp += "void " + n + "::reset() noexcept { " + self + ".reset(); }\n";
            cpp += "void " + n + "::swap(" + n + "& other) noexcept { " + self + ".swap(" + get("other") + "); }\n";
            if (f == "weak") {
                h += "    explicit " + n + "(const " + shared + "& owner) noexcept;\n    " + shared + " lock() const noexcept;\n";
                const auto strong = "detail::ownership_access::get<std::shared_ptr<" + r.name + ">>";
                cpp += n + "::" + n + "(const " + shared + "& owner) noexcept { detail::ownership_access::construct<" + native + ">(*this, " + strong + "(owner)); }\n";
                cpp += shared + " " + n + "::lock() const noexcept { " + shared + " result; " + strong + "(result) = " + self + ".lock(); return result; }\n";
            } else {
                h += "    bool_t has_value() const noexcept;\n    explicit operator bool() const noexcept;\n    " + r.name + "& operator*() const;\n    " + r.name + "* operator->() const;\n";
                cpp += "bool_t " + n + "::has_value() const noexcept { return bool_t{static_cast<bool>(" + self + ")}; }\n";
                cpp += n + "::operator bool() const noexcept { return static_cast<bool>(" + self + "); }\n";
                cpp += r.name + "& " + n + "::operator*() const { return detail::ownership_access::checked(" + self + "); }\n";
                cpp += r.name + "* " + n + "::operator->() const { return std::addressof(detail::ownership_access::checked(" + self + ")); }\n";
                for (const auto &args : r.constructors) {
                    h += "    static " + n + " make(" + join(args, false) + ");\n";
                    cpp += n + " " + n + "::make(" + join(args, false) + ") { " + n + " result; " + get("result") + " = std::make_" + f + "<" + r.name + ">(" + join(args, true) + "); return result; }\n";
                }
            }
            h += "private:\n    friend struct detail::ownership_access;\n    alignas(" + std::to_string(alignment) + ") unsigned char storage_[" + std::to_string(size) + "];\n};\n";
        }
    }
    files["public/" + o.header] = h + "}\n";
    files["src/ownership.cpp"] = cpp + "}\n";
}
}
