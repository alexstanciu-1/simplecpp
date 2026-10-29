//! C++ emission from resolved plans. No policy admission or promotion resolution.
use crate::{
    Error, Result, ensure,
    scalar::{Cast, Form, Operation, Promotion, ScalarPlan, ValueKind},
};
use std::collections::{BTreeMap, BTreeSet};
const BANNER: &str = "// Generated from reviewed runtime metadata. Do not edit.\n";
const NATIVES: &[&str] = &[
    "int",
    "unsigned int",
    "long",
    "unsigned long",
    "long long",
    "unsigned long long",
    "short",
    "unsigned short",
    "bool",
    "char",
    "signed char",
    "unsigned char",
    "wchar_t",
    "char8_t",
    "char16_t",
    "char32_t",
    "float",
    "double",
    "long double",
];
macro_rules! add { ($out:expr,$($args:tt)*) => {$out.push_str(&format!($($args)*))}; }
mod datetime;
mod hash;
mod ownership;
mod sequences;
fn signature(op: &Operation) -> String {
    let mut s = format!(
        "{}{} operator{}({}{} left",
        op.result,
        if matches!(op.form, Form::Compound | Form::Prefix) {
            "&"
        } else {
            ""
        },
        op.symbol,
        op.left.cpp,
        if matches!(op.form, Form::Compound | Form::Prefix | Form::Postfix) {
            "&"
        } else {
            ""
        }
    );
    if let Some(r) = &op.right {
        add!(s, ", {} right", r.cpp);
    }
    if op.form == Form::Postfix {
        s.push_str(", int");
    }
    s.push(')');
    s
}
fn int128_fences(out: &mut String, name: &str) {
    add!(
        out,
        "#ifdef __SIZEOF_INT128__\n    explicit {name}(__int128) = delete;\n    explicit {name}(unsigned __int128) = delete;\n#endif\n"
    );
}
fn input(source: &str, string: Option<&str>) -> String {
    if string == Some(source) {
        format!("const {source}&")
    } else {
        source.into()
    }
}
fn values(plan: &ScalarPlan, string: Option<&str>) -> String {
    let mut out = String::from("#pragma once\nnamespace scpp {\n");
    if let Some(name) = string {
        add!(out, "class {name};\n");
    }
    for name in plan
        .types
        .iter()
        .map(|t| &t.name)
        .chain(plan.values.iter().map(|t| &t.name))
    {
        add!(out, "class {name};\n");
    }
    for t in &plan.values {
        let n = &t.name;
        add!(
            out,
            "class {n} final {{\npublic:\n    constexpr {n}() noexcept = default;\n"
        );
        for c in plan.conversions.iter().filter(|c| c.target == *n) {
            add!(
                out,
                "    explicit {n}({} value){};\n",
                input(&c.source, string),
                if c.can_fail { "" } else { " noexcept" }
            );
        }
        match t.kind {
            ValueKind::Floating => {
                for native in NATIVES {
                    if !plan
                        .conversions
                        .iter()
                        .any(|c| c.target == *n && c.native_source && c.source == *native)
                    {
                        add!(out, "    explicit {n}({native}) = delete;\n");
                    }
                }
                int128_fences(&mut out, n);
                out.push_str("    constexpr double native_value() const noexcept { return value_; }\nprivate:\n    double value_ = 0.0;\n");
            }
            ValueKind::Boolean => {
                add!(
                    out,
                    "    explicit constexpr {n}(bool value) noexcept : value_(value) {{}}\n"
                );
                for native in NATIVES {
                    if *native != "bool" {
                        add!(out, "    explicit {n}({native}) = delete;\n");
                    }
                }
                int128_fences(&mut out, n);
                add!(out, "    explicit {n}(const void*) = delete;\n");
                out.push_str("    explicit constexpr operator bool() const noexcept { return value_; }\n    constexpr bool native_value() const noexcept { return value_; }\nprivate:\n    bool value_ = false;\n");
            }
            ValueKind::Null => {}
        }
        out.push_str("};\n");
    }
    for t in &plan.values {
        let n = &t.name;
        if t.kind == ValueKind::Null {
            add!(out, "inline constexpr {n} null{{}};\n");
        }
        for op in &t.operations {
            if op == "not" {
                add!(
                    out,
                    "constexpr bool_t operator!({n} value) noexcept {{ return bool_t{{!value.native_value()}}; }}\n"
                );
            } else {
                let symbol = if op == "equal" { "==" } else { "!=" };
                if t.kind == ValueKind::Null {
                    add!(
                        out,
                        "constexpr bool_t operator{symbol}({n}, {n}) noexcept {{ return bool_t{{{}}}; }}\n",
                        if op == "equal" { "true" } else { "false" }
                    );
                } else {
                    add!(
                        out,
                        "constexpr bool_t operator{symbol}({n} left, {n} right) noexcept {{ return bool_t{{left.native_value() {symbol} right.native_value()}}; }}\n"
                    );
                }
            }
        }
    }
    for (alias, target) in &plan.aliases {
        if plan.values.iter().any(|v| v.name == *target) {
            add!(out, "using {alias} = {target};\n");
        }
    }
    out.push_str("}\n");
    out
}

pub fn emit_scalars(plan: &ScalarPlan) -> Result<BTreeMap<String, String>> {
    ensure(
        plan.conversions.iter().all(|c| {
            !matches!(
                c.implementation,
                Cast::ParseDecimal
                    | Cast::FormatDecimal
                    | Cast::ParseBooleanText
                    | Cast::FormatBooleanText
            )
        }),
        "Scalar emission does not support text conversions",
    )?;
    emit_scalar_core(plan, None)
}

fn emit_scalar_core(
    plan: &ScalarPlan,
    runtime: Option<&crate::runtime::RuntimePlan>,
) -> Result<BTreeMap<String, String>> {
    let string = runtime
        .and_then(|r| r.string.as_ref())
        .map(|s| s.name.as_str());
    let types = &plan.headers["types"];
    let vals = &plan.headers["values"];
    let ops = &plan.headers["operators"];
    let mut declarations = format!(
        "{BANNER}#pragma once\nnamespace scpp {{\nnamespace detail {{ struct integer_access; }}\n"
    );
    let mut definitions =
        format!("{BANNER}#include \"{ops}\"\n#include \"scalar_semantics.hpp\"\n");
    if let Some(r) = runtime {
        if let Some(s) = &r.string {
            add!(
                definitions,
                "#include \"{}\"\n#include \"sequence_semantics.hpp\"\n",
                s.header
            );
        }
        if let Some(h) = &r.vectors_header {
            add!(
                definitions,
                "#include \"{h}\"\n#include \"sequence_semantics.hpp\"\n#include <vector>\n#include <algorithm>\n"
            );
        }
        if let Some(h) = &r.carriers_header {
            add!(
                definitions,
                "#include \"{h}\"\n#include \"carrier_semantics.hpp\"\n"
            );
        }
    }
    definitions.push_str("namespace scpp {\n");
    let mut family_files = BTreeMap::new();
    if let Some(r) = runtime {
        sequences::emit(r, &mut family_files, &mut definitions);
    }
    definitions.push_str("namespace detail { struct integer_access { template<class T, class Rep> static T make(Rep value) { T out; out.value_ = value; return out; } }; }\n");
    if let Some(name) = string {
        add!(declarations, "class {name};\n");
    }
    for name in plan
        .types
        .iter()
        .map(|t| &t.name)
        .chain(plan.values.iter().map(|v| &v.name))
    {
        add!(declarations, "class {name};\n");
    }
    let floating = plan.values.iter().any(|v| v.kind == ValueKind::Floating);
    let representation = |name: &str| -> Result<String> {
        if let Some(t) = plan.types.iter().find(|t| t.name == name) {
            return Ok(t.representation.clone());
        }
        match name {
            "bool_t" => Ok("bool".into()),
            "float64" => Ok("double".into()),
            _ => Err(Error(format!(
                "Missing prepared scalar representation: {name}"
            ))),
        }
    };
    if floating {
        definitions.push_str("static_assert(std::numeric_limits<double>::is_iec559 && std::numeric_limits<double>::radix == 2 && std::numeric_limits<double>::digits == 53 && std::numeric_limits<double>::max_exponent == 1024);\nstatic_assert(sizeof(float64) == sizeof(double));\n");
    }
    for c in &plan.conversions {
        if string == Some(c.source.as_str()) {
            let suffix = if c.implementation == Cast::ParseDecimal {
                format!("<{}>", representation(&c.target)?)
            } else {
                String::new()
            };
            add!(
                definitions,
                "{0}::{0}({1} value) : value_(detail::{2}{suffix}(detail::string_access::read(value))) {{}}\n",
                c.target,
                input(&c.source, string),
                c.implementation.name()
            );
            continue;
        }
        if string == Some(c.target.as_str()) {
            add!(
                definitions,
                "{0}::{0}({1} value) : storage_(new storage{{detail::{2}(value.native_value())}}) {{}}\n",
                c.target,
                input(&c.source, string),
                c.implementation.name()
            );
            continue;
        }
        add!(
            definitions,
            "{}::{}({} value){} : value_(detail::{}<{}>({})) {{}}\n",
            c.target,
            c.target,
            c.source,
            if c.can_fail { "" } else { " noexcept" },
            c.implementation.name(),
            representation(&c.target)?,
            if c.native_source {
                "value"
            } else {
                "value.native_value()"
            }
        );
    }
    let mut natives = BTreeSet::new();
    for n in &plan.native_inputs {
        natives.insert(n.cpp.as_str());
        add!(
            definitions,
            "static_assert(std::numeric_limits<{}>::digits == {});\nstatic_assert(std::numeric_limits<{}>::is_signed == {});\n",
            n.cpp,
            n.digits,
            n.cpp,
            n.signed
        );
    }
    for t in &plan.types {
        let n = &t.name;
        let rep = &t.representation;
        add!(
            declarations,
            "class {n} final {{\npublic:\n    constexpr {n}() noexcept = default;\n"
        );
        for c in plan.conversions.iter().filter(|c| c.target == *n) {
            add!(
                declarations,
                "    explicit {n}({} value){};\n",
                input(&c.source, string),
                if c.can_fail { "" } else { " noexcept" }
            );
        }
        for native in NATIVES {
            if !natives.contains(native) {
                add!(declarations, "    explicit {n}({native}) = delete;\n");
            }
        }
        int128_fences(&mut declarations, n);
        add!(
            declarations,
            "    constexpr {rep} native_value() const noexcept {{ return value_; }}\nprivate:\n    friend struct detail::integer_access;\n    {rep} value_ = 0;\n}};\n"
        );
        add!(
            definitions,
            "static_assert(sizeof({n}) == sizeof({rep}));\nstatic_assert(alignof({n}) == alignof({rep}));\nstatic_assert(std::numeric_limits<{rep}>::digits == {});\n",
            t.digits()
        );
    }
    for (alias, target) in &plan.aliases {
        if plan.types.iter().any(|t| t.name == *target) {
            add!(declarations, "using {alias} = {target};\n");
        }
    }
    declarations.push_str("}\n");
    let mut operators = format!(
        "{BANNER}#pragma once\n#include \"{types}\"\n#include \"{vals}\"\nnamespace scpp {{\n"
    );
    let mut bridge = format!("{BANNER}#pragma once\n#include \"{ops}\"\nnamespace scpp {{\n");
    let mut emitted = BTreeSet::new();
    let mut symbols = BTreeSet::new();
    for op in &plan.operations {
        emitted.insert((
            op.symbol,
            op.left.cpp.as_str(),
            op.right.as_ref().map_or("", |r| r.cpp.as_str()),
        ));
        let declaration = signature(op);
        if op.left.native || op.right.as_ref().is_some_and(|r| r.native) {
            symbols.insert(op.symbol);
            let left = if op.form == Form::Compound {
                "left".into()
            } else {
                format!("static_cast<{}>(left)", op.execution)
            };
            add!(
                bridge,
                "inline {declaration} {{\n    return {left} {} static_cast<{}>(right);\n}}\n",
                op.symbol,
                op.execution
            );
            continue;
        }
        add!(operators, "{declaration};\n");
        if op.promotion == Promotion::ExactComparison {
            add!(
                definitions,
                "{declaration} {{\n    return {}{{detail::{}(left.native_value(), right.native_value())}};\n}}\n",
                op.result,
                op.implementation
            );
            continue;
        }
        let rep = representation(&op.execution)?;
        let args = if op.right.is_none() {
            format!("static_cast<{rep}>(left.native_value())")
        } else if op.promotion == Promotion::CheckedShift {
            "left.native_value(), right.native_value()".into()
        } else {
            format!(
                "static_cast<{rep}>(left.native_value()), static_cast<{rep}>(right.native_value())"
            )
        };
        let call = format!("detail::{}<{rep}>({args})", op.implementation);
        if op.result == "bool_t" {
            add!(
                definitions,
                "{declaration} {{\n    return bool_t{{{call}}};\n}}\n"
            );
            continue;
        }
        let result = if op.execution == "float64" {
            format!("float64{{{call}}}")
        } else {
            format!("detail::integer_access::make<{}>({call})", op.execution)
        };
        add!(definitions, "{declaration} {{\n");
        match op.form {
            Form::Compound => add!(
                definitions,
                "    left = static_cast<{}>({result});\n    return left;\n",
                op.left.cpp
            ),
            Form::Prefix => add!(definitions, "    left = {result};\n    return left;\n"),
            Form::Postfix => add!(
                definitions,
                "    const auto old = left;\n    left = {result};\n    return old;\n"
            ),
            _ => add!(definitions, "    return {result};\n"),
        }
        definitions.push_str("}\n");
    }
    let numeric: Vec<_> = plan
        .types
        .iter()
        .map(|t| t.name.as_str())
        .chain(floating.then_some("float64"))
        .collect();
    for symbol in symbols {
        for name in &numeric {
            let mut reject = |native: &str| {
                if !emitted.contains(&(symbol, *name, native)) {
                    add!(
                        bridge,
                        "void operator{symbol}({name}, {native}) = delete;\n"
                    );
                }
                if !emitted.contains(&(symbol, native, *name)) {
                    add!(
                        bridge,
                        "void operator{symbol}({native}, {name}) = delete;\n"
                    );
                }
            };
            for native in NATIVES {
                reject(native);
            }
            bridge.push_str("#ifdef __SIZEOF_INT128__\n");
            for native in ["__int128", "unsigned __int128"] {
                if !emitted.contains(&(symbol, *name, native)) {
                    add!(
                        bridge,
                        "void operator{symbol}({name}, {native}) = delete;\n"
                    );
                }
                if !emitted.contains(&(symbol, native, *name)) {
                    add!(
                        bridge,
                        "void operator{symbol}({native}, {name}) = delete;\n"
                    );
                }
            }
            bridge.push_str("#endif\n");
        }
    }
    bridge.push_str("}\n");
    operators.push_str("}\n");
    definitions.push_str("}\n");
    let mut short = format!("{BANNER}#pragma once\n#include \"{ops}\"\n");
    if let Some(r) = runtime {
        if let Some(s) = &r.string {
            add!(
                short,
                "#include \"{}\"\nusing scpp::{};\n",
                s.header,
                s.name
            );
        }
        if let Some(h) = &r.vectors_header {
            add!(short, "#include \"{h}\"\n");
        }
        for v in &r.vectors {
            add!(short, "using scpp::{};\n", v.name);
        }
        if let Some(h) = &r.carriers_header {
            add!(short, "#include \"{h}\"\n");
        }
        for c in &r.carriers {
            add!(short, "using scpp::{};\n", c.name);
        }
        if let Some(hashes) = &r.hashes {
            add!(short, "#include \"{}\"\n", hashes.header);
            for h in &hashes.specializations {
                add!(short, "using scpp::{};\n", h.name);
            }
        }
    }
    for t in &plan.types {
        add!(short, "using scpp::{};\n", t.name);
    }
    for v in &plan.values {
        add!(short, "using scpp::{};\n", v.name);
        if v.kind == ValueKind::Null {
            short.push_str("using scpp::null;\n");
        }
    }
    for alias in &plan.short_aliases {
        add!(short, "using scpp::{alias};\n");
    }
    let mut files = BTreeMap::from([
        (format!("public/{types}"), declarations),
        (
            format!("public/{vals}"),
            format!("{BANNER}{}", values(plan, string)),
        ),
        (format!("public/{ops}"), operators),
        (format!("public/{}", plan.headers["native_bridge"]), bridge),
        (format!("public/{}", plan.headers["short_names"]), short),
        ("src/runtime.cpp".into(), definitions),
        (
            "prepared.json".into(),
            serde_json::to_string(&runtime.map_or_else(|| plan.describe(), |r| r.describe()))
                .map_err(|e| Error(e.to_string()))?
                + "\n",
        ),
    ]);
    files.extend(family_files);
    if let Some(r) = runtime {
        ownership::emit(r, &mut files);
        hash::emit(r, &mut files);
        datetime::emit(r, &mut files);
    }
    let inventory = serde_json::to_string(&files.keys().collect::<Vec<_>>())
        .map_err(|e| Error(e.to_string()))?
        + "\n";
    files.insert("inventory.json".into(), inventory);
    Ok(files)
}

pub fn emit_runtime(plan: &crate::runtime::RuntimePlan) -> Result<BTreeMap<String, String>> {
    emit_scalar_core(&plan.scalars, Some(plan))
}

/// Validate old inventory before mutation. Like the production writer, publication
/// is per-file and not transactional; --check performs no writes.
pub fn publish(
    files: &BTreeMap<String, String>,
    output: &std::path::Path,
    check: bool,
) -> Result<()> {
    let io = |e: std::io::Error| Error(e.to_string());
    let inventory = output.join("inventory.json");
    if inventory.exists() {
        let old = crate::json::parse(
            &std::fs::read(&inventory).map_err(io)?,
            "old generated inventory",
        )?;
        ensure(
            matches!(old, crate::json::Value::Array(_)),
            "Invalid old generated inventory",
        )?;
        for path in old.array()? {
            ensure(
                files.contains_key(path.text()?),
                "Obsolete generated outputs; regenerate in empty staging",
            )?;
        }
    }
    for (path, contents) in files {
        let target = output.join(path);
        if check {
            ensure(
                std::fs::read(&target)
                    .map_err(|e| Error(format!("Cannot read {}: {e}", target.display())))?
                    == contents.as_bytes(),
                &format!("Stale generated file: {}", target.display()),
            )?;
        } else {
            std::fs::create_dir_all(
                target
                    .parent()
                    .ok_or_else(|| Error("Missing artifact parent".into()))?,
            )
            .map_err(io)?;
            std::fs::write(target, contents).map_err(io)?;
        }
    }
    Ok(())
}
