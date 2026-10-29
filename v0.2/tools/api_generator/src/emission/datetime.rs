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
        "{BANNER}#include \"{}\"\n#include \"datetime_native.hpp\"\n#include \"scalar_semantics.hpp\"\nnamespace scpp::dt {{\n",
        module.header
    );
    for op in &module.operations {
        let argument = op
            .arguments
            .iter()
            .enumerate()
            .map(|(i, t)| format!("const {t}& input{i}"))
            .collect::<Vec<_>>()
            .join(", ");
        let signature = format!("{} {}({argument})", op.result, op.name);
        add!(header, "{signature};\n");
        let body = match op.binding {
            Binding::SignedClock => format!("return int64{{static_cast<long long>(detail::datetime::{0}())}};", op.name),
            Binding::UnsignedClock => format!("return uint64{{static_cast<unsigned long long>(detail::datetime::{0}())}};", op.name),
            Binding::Sleep => "detail::datetime::sleep_millis(input0.native_value());".into(),
            Binding::FormatUtc => "const auto bytes = detail::datetime::format_iso_utc(input0.native_value()); return string{bytes.data(), int64{20LL}};".into(),
            Binding::Parse => format!("const auto parsed = detail::datetime::{}({{input0.data(), static_cast<std::size_t>(input0.size().native_value())}}); if (parsed.error) return result_int64{{error_t{{string{{parsed.error}}}}}}; return result_int64{{int64{{static_cast<long long>(parsed.value)}}}};",op.name),
            Binding::FormatLocal | Binding::FormatLocalNow => {
                let seconds=if op.binding==Binding::FormatLocal { ", input1.native_value()" } else { "" };
                format!("const auto bytes = detail::datetime::{}({{input0.data(), static_cast<std::size_t>(input0.size().native_value())}}{seconds}); return string{{bytes.data(), int64{{detail::checked_integer_cast<long long>(bytes.size())}}}};",op.name)
            },
        };
        add!(source, "{signature} {{ {body} }}\n");
    }
    header.push_str("}\n");
    source.push_str("}\n");
    files.insert(format!("public/{}", module.header), header);
    files.insert("src/datetime.cpp".into(), source);
}
