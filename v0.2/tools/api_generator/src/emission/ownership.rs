//! Concrete ownership emission; native payload traits remain C++ assertions.
use crate::{
    ownership::{Family, QualificationEffect},
    runtime::RuntimePlan,
};
use std::collections::{BTreeMap, BTreeSet};
fn arguments(types: &[String], names: bool) -> String {
    types
        .iter()
        .enumerate()
        .map(|(i, t)| {
            if names {
                format!("arg{i}")
            } else {
                format!("{t} arg{i}")
            }
        })
        .collect::<Vec<_>>()
        .join(", ")
}
pub(super) fn emit(plan: &RuntimePlan, files: &mut BTreeMap<String, String>) {
    let Some(o) = &plan.ownership else {
        return;
    };
    let mut h = format!(
        "// Generated ownership API; edit metadata/publication.\n#pragma once\n#include \"{}\"\n#include \"{}\"\n",
        plan.scalars.headers["types"], plan.scalars.headers["values"]
    );
    for header in o.records.iter().map(|r| &r.header).collect::<BTreeSet<_>>() {
        add!(h, "#include \"{header}\"\n");
    }
    h.push_str("namespace scpp {\nnamespace detail { struct ownership_access; }\n");
    let mut d = format!(
        "// Generated ownership definitions.\n#include \"{}\"\n#include \"ownership_semantics.hpp\"\nnamespace scpp {{\n",
        o.header
    );
    for r in &o.records {
        let record = &r.name;
        add!(
            d,
            "static_assert(std::is_class_v<{record}> && std::is_nothrow_destructible_v<{record}>);\n"
        );
        for s in &r.families {
            add!(h, "class {};\n", s.name);
        }
        for s in &r.families {
            let n = &s.name;
            let family = s.family.name();
            let t = format!("{}{record}", if s.const_pointee { "const " } else { "" });
            if s.family == Family::Value {
                add!(h, "class {n} final {{\npublic:\n");
                for args in &r.constructors {
                    let params = arguments(args, false);
                    let values = arguments(args, true);
                    let spec = format!(" noexcept(noexcept({record}({values})))");
                    add!(h, "    explicit {n}({params}){spec};\n");
                    add!(d, "{n}::{n}({params}){spec} : payload_({values}) {{}}\n");
                }
                for read_only in [false, true] {
                    let ty = format!("{}{record}", if read_only { "const " } else { "" });
                    let q = if read_only { " const" } else { "" };
                    add!(
                        h,
                        "    {ty}& operator*(){q} noexcept;\n    {ty}* operator->(){q} noexcept;\n"
                    );
                    add!(
                        d,
                        "{ty}& {n}::operator*(){q} noexcept {{ return payload_; }}\n{ty}* {n}::operator->(){q} noexcept {{ return std::addressof(payload_); }}\n"
                    );
                }
                add!(h, "private:\n    {record} payload_;\n}};\n");
                add!(
                    d,
                    "static_assert(sizeof({n}) == sizeof({record}) && alignof({n}) == alignof({record}));\n"
                );
                continue;
            }
            let native = format!("std::{family}_ptr<{t}>");
            let shared = format!(
                "shared_{}{record}",
                if s.const_pointee { "const_" } else { "" }
            );
            let layout = s.layout.as_ref().expect("prepared handle layout");
            let size = layout.size;
            let align = layout.alignment;
            let get = |obj: &str| format!("detail::ownership_access::get<{native}>({obj})");
            let this = get("*this");
            let other = get("other");
            let copy_spec = if s.family == Family::Unique {
                " = delete"
            } else {
                " noexcept"
            };
            add!(
                h,
                "class {n} final {{\npublic:\n    {n}() noexcept;\n    ~{n}() noexcept;\n    {n}({n}&& other) noexcept;\n    {n}& operator=({n}&& other) noexcept;\n    {n}(const {n}& other){copy_spec};\n    {n}& operator=(const {n}& other){copy_spec};\n    void reset() noexcept;\n    void swap({n}& other) noexcept;\n"
            );
            add!(
                d,
                "static_assert(sizeof({native}) == {size} && alignof({native}) == {align});\nstatic_assert(sizeof({n}) == sizeof({native}) && alignof({n}) == alignof({native}));\n{n}::{n}() noexcept {{ detail::ownership_access::construct<{native}>(*this); }}\n{n}::~{n}() noexcept {{ detail::ownership_access::destroy<{native}>(*this); }}\n{n}::{n}({n}&& other) noexcept {{ detail::ownership_access::construct<{native}>(*this, std::move({other})); }}\n{n}& {n}::operator=({n}&& other) noexcept {{ if (this != &other) {this} = std::move({other}); return *this; }}\n"
            );
            if s.family != Family::Unique {
                add!(
                    d,
                    "{n}::{n}(const {n}& other) noexcept {{ detail::ownership_access::construct<{native}>(*this, {other}); }}\n{n}& {n}::operator=(const {n}& other) noexcept {{ {this} = {other}; return *this; }}\n"
                );
            }
            add!(
                d,
                "void {n}::reset() noexcept {{ {this}.reset(); }}\nvoid {n}::swap({n}& other) noexcept {{ {this}.swap({other}); }}\n"
            );
            if s.family == Family::Weak {
                let strong = format!("detail::ownership_access::get<std::shared_ptr<{t}>>");
                add!(
                    h,
                    "    explicit {n}(const {shared}& owner) noexcept;\n    {shared} lock() const noexcept;\n"
                );
                add!(
                    d,
                    "{n}::{n}(const {shared}& owner) noexcept {{ detail::ownership_access::construct<{native}>(*this, {strong}(owner)); }}\n{shared} {n}::lock() const noexcept {{ {shared} result; {strong}(result) = {this}.lock(); return result; }}\n"
                );
            } else {
                add!(
                    h,
                    "    bool_t has_value() const noexcept;\n    explicit operator bool() const noexcept;\n    {t}& operator*() const;\n    {t}* operator->() const;\n"
                );
                add!(
                    d,
                    "bool_t {n}::has_value() const noexcept {{ return bool_t{{static_cast<bool>({this})}}; }}\n{n}::operator bool() const noexcept {{ return static_cast<bool>({this}); }}\n{t}& {n}::operator*() const {{ return detail::ownership_access::checked({this}); }}\n{t}* {n}::operator->() const {{ return std::addressof(detail::ownership_access::checked({this})); }}\n"
                );
                for args in &r.constructors {
                    let params = arguments(args, false);
                    let values = arguments(args, true);
                    let result = get("result");
                    add!(h, "    static {n} make({params});\n");
                    add!(
                        d,
                        "{n} {n}::make({params}) {{ {n} result; {result} = std::make_{family}<{t}>({values}); return result; }}\n"
                    );
                }
            }
            for c in &s.qualifications {
                let source = r
                    .families
                    .iter()
                    .find(|f| f.name == c.source)
                    .expect("prepared qualification source");
                let transfer = c.effect == QualificationEffect::TransferSourceEmpty;
                let param = if transfer {
                    format!("{}&& other", c.source)
                } else {
                    format!("const {}& other", c.source)
                };
                let mut expression = format!(
                    "detail::ownership_access::get<std::{}_ptr<{record}>>(other)",
                    source.family.name()
                );
                if transfer {
                    expression = format!("std::move({expression})");
                }
                add!(h, "    explicit {n}({param}) noexcept;\n");
                add!(
                    d,
                    "{n}::{n}({param}) noexcept {{ detail::ownership_access::construct<{native}>(*this, {expression}); }}\n"
                );
            }
            add!(
                h,
                "private:\n    friend struct detail::ownership_access;\n    alignas({align}) unsigned char storage_[{size}];\n}};\n"
            );
        }
    }
    h.push_str("}\n");
    d.push_str("}\n");
    files.insert(format!("public/{}", o.header), h);
    files.insert("src/ownership.cpp".into(), d);
}
