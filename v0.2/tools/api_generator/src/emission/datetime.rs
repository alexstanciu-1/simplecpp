//! Render the prepared native module interface and value adapters.
use super::BANNER;
use crate::{datetime::Binding, runtime::RuntimePlan};
use std::collections::BTreeMap;
pub(super) fn emit(plan: &RuntimePlan, files: &mut BTreeMap<String, String>) {
    let Some(module) = &plan.datetime else {
        return;
    };
    let mut header = format!("{BANNER}#pragma once\n");
    for include in &module.includes {
        add!(header, "#include \"{include}\"\n");
    }
    header.push_str("namespace scpp::dt {\n");
    let mut source = format!(
        "{BANNER}#include \"{}\"\n#include \"datetime_native.hpp\"\nnamespace scpp::dt {{\n",
        module.header
    );
    for op in &module.operations {
        let argument = op
            .argument
            .map(|t| format!("const {t}& input"))
            .unwrap_or_default();
        let signature = format!("{} {}({argument})", op.result, op.name);
        add!(header, "{signature};\n");
        let body = match op.binding {
            Binding::SignedClock => format!("return int64{{static_cast<long long>(detail::datetime::{0}())}};", op.name),
            Binding::UnsignedClock => format!("return uint64{{static_cast<unsigned long long>(detail::datetime::{0}())}};", op.name),
            Binding::Sleep => "detail::datetime::sleep_millis(input.native_value());".into(),
            Binding::FormatUtc => "const auto bytes = detail::datetime::format_iso_utc(input.native_value()); return string{bytes.data(), int64{20LL}};".into(),
            Binding::ParseUtc => "const auto parsed = detail::datetime::parse_iso_utc({input.data(), static_cast<std::size_t>(input.size().native_value())}); if (parsed.error) return result_int64{error_t{string{parsed.error}}}; return result_int64{int64{static_cast<long long>(parsed.value)}};".into(),
        };
        add!(source, "{signature} {{ {body} }}\n");
    }
    header.push_str("}\n");
    source.push_str("}\n");
    files.insert(format!("public/{}", module.header), header);
    files.insert("src/datetime.cpp".into(), source);
}
