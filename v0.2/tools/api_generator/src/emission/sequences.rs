//! Owned sequence/carrier declarations and definitions from the composed plan.
use super::{BANNER, NATIVES, input, int128_fences};
use crate::runtime::{CarrierFamily, Extraction, RuntimePlan};
use std::collections::{BTreeMap, BTreeSet};

fn lifecycle(n: &str) -> (String, String) {
    (
        format!(
            "    {n}() noexcept = default;\n    ~{n}();\n    {n}(const {n}&);\n    {n}({n}&&) noexcept;\n    {n}& operator=(const {n}&);\n    {n}& operator=({n}&&) noexcept;\n"
        ),
        format!(
            "{n}::~{n}() {{ delete storage_; }}\n{n}::{n}(const {n}& other) : storage_(other.storage_ ? new storage(*other.storage_) : nullptr) {{}}\n{n}::{n}({n}&& other) noexcept : storage_(std::exchange(other.storage_, nullptr)) {{}}\n{n}& {n}::operator=(const {n}& other) {{ if (this != &other) {{ {n} copy(other); std::swap(storage_, copy.storage_); }} return *this; }}\n{n}& {n}::operator=({n}&& other) noexcept {{ if (this != &other) {{ delete storage_; storage_ = std::exchange(other.storage_, nullptr); }} return *this; }}\n"
        ),
    )
}
fn string(plan: &RuntimePlan) -> Option<(String, String)> {
    let s = plan.string.as_ref()?;
    let n = &s.name;
    let count = &s.count_type;
    let types = &plan.scalars.headers["types"];
    let vals = &plan.scalars.headers["values"];
    let (lh, ld) = lifecycle(n);
    let mut h = format!(
        "#pragma once\n#include \"{types}\"\n#include \"{vals}\"\nnamespace scpp {{\nnamespace detail {{ struct string_access; }}\nclass {n} final {{\npublic:\n{lh}    explicit {n}(const char*);\n    explicit {n}(const char*, {count});\n"
    );
    for native in NATIVES {
        add!(h, "    explicit {n}({native}) = delete;\n");
    }
    int128_fences(&mut h, n);
    for c in plan.scalars.conversions.iter().filter(|c| c.target == *n) {
        add!(
            h,
            "    explicit {n}({} value);\n",
            input(&c.source, Some(n))
        );
    }
    add!(
        h,
        "    {count} size() const;\n    bool_t empty() const noexcept;\n    const char* data() const noexcept;\n    void append(const {n}&);\n    void clear() noexcept;\nprivate:\n    struct storage;\n    storage* storage_ = nullptr;\n    friend struct detail::string_access;\n}};\n"
    );
    let mut d = format!(
        "struct {n}::storage {{ std::string bytes; }};\nnamespace detail {{ struct string_access {{\n    static const std::string& read(const {n}& value) noexcept {{ static const std::string empty; return value.storage_ ? value.storage_->bytes : empty; }}\n}}; }}\n{ld}"
    );
    add!(
        d,
        "{n}::{n}(const char* bytes) : storage_(new storage{{detail::string_from_c_string(bytes)}}) {{}}\n{n}::{n}(const char* bytes, {count} count) : storage_(new storage{{detail::string_from_bytes(bytes, count.native_value())}}) {{}}\n{count} {n}::size() const {{ return {count}{{detail::checked_integer_cast<long long>(detail::string_access::read(*this).size())}}; }}\nbool_t {n}::empty() const noexcept {{ return bool_t{{detail::string_access::read(*this).empty()}}; }}\nconst char* {n}::data() const noexcept {{ return detail::string_access::read(*this).data(); }}\nvoid {n}::append(const {n}& other) {{ if (!storage_) {{ {n} copy(other); std::swap(storage_, copy.storage_); }} else {{ storage_->bytes.append(detail::string_access::read(other)); }} }}\nvoid {n}::clear() noexcept {{ delete storage_; storage_ = nullptr; }}\n"
    );
    for op in &s.operations {
        let signature = if op.mutates {
            format!("{n}& operator+=({n}& left, const {n}& right)")
        } else if op.append {
            format!("{n} operator+({n} left, const {n}& right)")
        } else {
            format!(
                "bool_t operator{}(const {n}& left, const {n}& right) noexcept",
                op.symbol
            )
        };
        add!(h, "{signature};\n");
        add!(d, "{signature} {{ ");
        if op.append {
            d.push_str("left.append(right); return left;");
        } else {
            add!(
                d,
                "return bool_t{{detail::string_access::read(left) {} detail::string_access::read(right)}};",
                op.symbol
            );
        }
        d.push_str(" }\n");
    }
    h.push_str("}\n");
    Some((h, d))
}
fn vectors(plan: &RuntimePlan) -> Option<(String, String)> {
    plan.vectors_header.as_ref()?;
    let mut h = format!(
        "#pragma once\n#include \"{}\"\n",
        plan.scalars.headers["operators"]
    );
    if let Some(s) = &plan.string {
        add!(h, "#include \"{}\"\n", s.header);
    }
    h.push_str("namespace scpp {\nnamespace detail { struct vector_access; }\n");
    let mut d = String::new();
    for v in &plan.vectors {
        add!(h, "class {};\n", v.name);
        add!(
            d,
            "static_assert(std::is_copy_constructible_v<{0}> && std::is_nothrow_move_constructible_v<{0}> && std::is_nothrow_move_assignable_v<{0}>);\nstruct {1}::storage {{ std::vector<{0}> values; }};\n",
            v.element,
            v.name
        );
    }
    d.push_str("namespace detail { struct vector_access {\n");
    for v in &plan.vectors {
        add!(
            d,
            "    static const std::vector<{0}>& read(const {1}& value) noexcept {{ static const std::vector<{0}> empty; return value.storage_ ? value.storage_->values : empty; }}\n",
            v.element,
            v.name
        );
    }
    d.push_str("}; }\n");
    for v in &plan.vectors {
        let n = &v.name;
        let e = &v.element;
        let count = &v.count_type;
        let (lh, ld) = lifecycle(n);
        add!(h, "class {n} final {{\npublic:\n{lh}");
        d.push_str(&ld);
        for c in &v.casts {
            let src = &c.source;
            add!(h, "    explicit {n}(const {src}&);\n");
            add!(
                d,
                "{n}::{n}(const {src}& source) {{ {n} converted; for (const auto& item : detail::vector_access::read(source)) converted.append(static_cast<{e}>(item)); std::swap(storage_, converted.storage_); }}\n"
            );
        }
        add!(
            h,
            "    {count} size() const;\n    bool_t empty() const noexcept;\n    void clear() noexcept;\n    void append({e} value);\n    {e} at({count} index) const;\n    void set({count} index, {e} value);\n"
        );
        add!(
            d,
            "{count} {n}::size() const {{ return {count}{{detail::checked_integer_cast<long long>(detail::vector_access::read(*this).size())}}; }}\nbool_t {n}::empty() const noexcept {{ return bool_t{{detail::vector_access::read(*this).empty()}}; }}\nvoid {n}::clear() noexcept {{ delete storage_; storage_ = nullptr; }}\nvoid {n}::append({e} value) {{ if (!storage_) storage_ = new storage; storage_->values.push_back(std::move(value)); }}\n{e} {n}::at({count} index) const {{ const auto& values = detail::vector_access::read(*this); return values[detail::sequence_index(index.native_value(), values.size())]; }}\nvoid {n}::set({count} index, {e} value) {{ const auto offset = detail::sequence_index(index.native_value(), detail::vector_access::read(*this).size()); storage_->values[offset] = std::move(value); }}\n"
        );
        let mut indices = vec![count.as_str()];
        let mut values = vec![e.as_str()];
        if v.native_index {
            indices.push("int");
            add!(h, "    {e} at(int index) const;\n");
            add!(
                d,
                "{e} {n}::at(int index) const {{ return at({count}{{index}}); }}\n"
            );
        }
        if let Some(native) = &v.native_value {
            values.push(native);
            add!(h, "    void append({native} value);\n");
            add!(
                d,
                "void {n}::append({native} value) {{ append({e}{{value}}); }}\n"
            );
        }
        for index in &indices {
            for value in &values {
                if *index == count && *value == e {
                    continue;
                }
                add!(h, "    void set({index} index, {value} value);\n");
                add!(
                    d,
                    "void {n}::set({index} index, {value} value) {{ set({}, {}); }}\n",
                    if *index == count {
                        "index".into()
                    } else {
                        format!("{count}{{index}}")
                    },
                    if *value == e {
                        "std::move(value)".into()
                    } else {
                        format!("{e}{{value}}")
                    }
                );
            }
        }
        let fences = |h: &mut String, natives: &[&str]| {
            let mut deleted = BTreeSet::new();
            for native in natives {
                if v.native_index && *native != "int" {
                    deleted.insert(format!("    void at({native}) const = delete;\n"));
                    for value in &values {
                        deleted.insert(format!("    void set({native}, {value}) = delete;\n"));
                    }
                }
                if v.native_value.as_ref().is_some_and(|n| n != native) {
                    deleted.insert(format!("    void append({native}) = delete;\n"));
                    for index in &indices {
                        deleted.insert(format!("    void set({index}, {native}) = delete;\n"));
                    }
                }
            }
            for decl in deleted {
                h.push_str(&decl);
            }
        };
        fences(&mut h, NATIVES);
        if v.native_index || v.native_value.is_some() {
            h.push_str("#ifdef __SIZEOF_INT128__\n");
            fences(&mut h, &["__int128", "unsigned __int128"]);
            h.push_str("#endif\n");
        }
        h.push_str("private:\n    struct storage;\n    storage* storage_ = nullptr;\n    friend struct detail::vector_access;\n};\n");
        for op in &v.operations {
            let symbol = if op == "equal" { "==" } else { "!=" };
            let negate = if op == "equal" { "" } else { "!" };
            let sig = format!("bool_t operator{symbol}(const {n}& left, const {n}& right)");
            add!(h, "{sig};\n");
            add!(
                d,
                "{sig} {{ const auto& a = detail::vector_access::read(left); const auto& b = detail::vector_access::read(right); return bool_t{{{negate}std::equal(a.begin(), a.end(), b.begin(), b.end(), [](const {e}& x, const {e}& y) {{ return static_cast<bool>(x == y); }})}}; }}\n"
            );
        }
    }
    h.push_str("}\n");
    Some((h, d))
}
fn carriers(plan: &RuntimePlan) -> Option<(String, String)> {
    plan.carriers_header.as_ref()?;
    let mut h = format!(
        "#pragma once\n#include \"{}\"\n",
        plan.scalars.headers["operators"]
    );
    if let Some(s) = &plan.string {
        add!(h, "#include \"{}\"\n", s.header);
    }
    if let Some(v) = &plan.vectors_header {
        add!(h, "#include \"{v}\"\n");
    }
    h.push_str("namespace scpp {\n");
    let mut d = String::new();
    if plan.requires_error() {
        h.push_str("class error_t final {\npublic:\n    error_t() = default;\n    explicit error_t(string message, int64 line = int64{}, string file = string{});\n    static const error_t no_error;\n    bool_t has_error() const noexcept;\n    explicit operator bool() const noexcept;\n    string get_message() const;\n    int64 get_line() const noexcept;\n    string get_file() const;\nprivate:\n    bool present_ = false;\n    string message_;\n    int64 line_;\n    string file_;\n};\n");
        d.push_str("const error_t error_t::no_error{};\nerror_t::error_t(string message, int64 line, string file) : present_(true), message_(std::move(message)), line_(line), file_(std::move(file)) {}\nbool_t error_t::has_error() const noexcept { return bool_t{present_}; }\nerror_t::operator bool() const noexcept { return present_; }\nstring error_t::get_message() const { return message_; }\nint64 error_t::get_line() const noexcept { return line_; }\nstring error_t::get_file() const { return file_; }\n");
    }
    for c in &plan.carriers {
        let n = &c.name;
        let p = &c.payload;
        let result = c.family == CarrierFamily::Result;
        let nullable = c.family == CarrierFamily::Nullable;
        let (lh, ld) = lifecycle(n);
        add!(
            h,
            "class {n} final {{\npublic:\n{lh}    explicit {n}({p} value);\n    bool_t has_value() const noexcept;\n    {p} value() const;\n    {}operator {p}() const;\n",
            if c.extraction == Extraction::ImplicitCheckedCopy {
                ""
            } else {
                "explicit "
            }
        );
        add!(
            d,
            "struct {n}::storage {{ using content_type = detail::carrier_storage<{p}{}>; content_type content; }};\n{ld}",
            if result { ", error_t" } else { "" }
        );
        add!(
            d,
            "{n}::{n}({p} value) : storage_(new storage{{storage::content_type{{std::move(value)}}}}) {{}}\nbool_t {n}::has_value() const noexcept {{ return bool_t{{storage_ ? storage_->content.has_value() : {result}}}; }}\n{p} {n}::value() const {{ "
        );
        if result {
            add!(d, "if (!storage_) return {p}{{}}; ");
        }
        d.push_str("if (!storage_) throw std::runtime_error(\"carrier_no_value\"); return storage_->content.value(); }\n");
        add!(d, "{n}::operator {p}() const {{ return value(); }}\n");
        if nullable {
            add!(h, "    explicit {n}(null_t) noexcept;\n");
            add!(d, "{n}::{n}(null_t) noexcept {{}}\n");
        }
        if result {
            add!(
                h,
                "    explicit {n}(error_t error);\n    bool_t has_error() const noexcept;\n    error_t error() const;\n"
            );
            add!(
                d,
                "{n}::{n}(error_t error) : storage_(new storage{{storage::content_type::failure(std::move(error))}}) {{}}\nbool_t {n}::has_error() const noexcept {{ return bool_t{{storage_ && storage_->content.has_error()}}; }}\nerror_t {n}::error() const {{ if (!storage_) throw std::runtime_error(\"result_no_error\"); return storage_->content.error(); }}\n"
            );
        } else {
            h.push_str("    void reset() noexcept;\n");
            add!(
                d,
                "void {n}::reset() noexcept {{ delete storage_; storage_ = nullptr; }}\n"
            );
            if !nullable {
                add!(
                    h,
                    "    static {n} from_false() noexcept;\n    bool_t is_false() const noexcept;\n"
                );
                add!(
                    d,
                    "{n} {n}::from_false() noexcept {{ return {{}}; }}\nbool_t {n}::is_false() const noexcept {{ return bool_t{{!storage_ || storage_->content.is_false()}}; }}\n"
                );
            }
            if c.family == CarrierFamily::ResultOrBool {
                add!(
                    h,
                    "    static {n} from_true();\n    bool_t is_true() const noexcept;\n"
                );
                add!(
                    d,
                    "{n} {n}::from_true() {{ {n} out; out.storage_ = new storage{{storage::content_type::sentinel_true()}}; return out; }}\nbool_t {n}::is_true() const noexcept {{ return bool_t{{storage_ && storage_->content.is_true()}}; }}\n"
                );
            }
        }
        h.push_str("private:\n    struct storage;\n    storage* storage_ = nullptr;\n};\n");
    }
    h.push_str("}\n");
    Some((h, d))
}
pub(super) fn emit(
    plan: &RuntimePlan,
    files: &mut BTreeMap<String, String>,
    definitions: &mut String,
) {
    for (path, output) in [
        (
            plan.string.as_ref().map(|s| s.header.as_str()),
            string(plan),
        ),
        (plan.vectors_header.as_deref(), vectors(plan)),
        (plan.carriers_header.as_deref(), carriers(plan)),
    ] {
        if let (Some(path), Some((h, d))) = (path, output) {
            files.insert(format!("public/{path}"), format!("{BANNER}{h}"));
            definitions.push_str(&d);
        }
    }
}
