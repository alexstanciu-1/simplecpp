//! Bounded native datetime module publication, independent of source callable catalogs.
use crate::{
    Result, ensure,
    json::Value,
    runtime::{CarrierFamily, RuntimePlan},
};
use serde_json::json;
use std::collections::BTreeSet;

#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum Binding {
    SignedClock,
    UnsignedClock,
    Sleep,
    FormatUtc,
    ParseUtc,
}
#[derive(Clone, Debug)]
pub struct Operation {
    pub name: String,
    pub result: String,
    pub argument: Option<&'static str>,
    pub binding: Binding,
}
#[derive(Clone, Debug)]
pub struct DatetimePlan {
    pub header: String,
    pub includes: Vec<String>,
    pub operations: Vec<Operation>,
}
fn operation(name: &str) -> Result<Operation> {
    let (result, argument, binding) = match name {
        "now_unix_seconds" | "now_unix_millis" | "monotonic_millis" => {
            ("int64", None, Binding::SignedClock)
        }
        "monotonic_micros" | "monotonic_nanos" => ("uint64", None, Binding::UnsignedClock),
        "sleep_millis" => ("void", Some("int64"), Binding::Sleep),
        "format_iso_utc" => ("string", Some("int64"), Binding::FormatUtc),
        "parse_iso_utc" => ("result_int64", Some("string"), Binding::ParseUtc),
        _ => {
            return Err(crate::Error(format!(
                "Unsupported datetime operation: {name}"
            )));
        }
    };
    Ok(Operation {
        name: name.into(),
        result: result.into(),
        argument,
        binding,
    })
}
pub fn prepare(
    metadata: &Value,
    publication: &Value,
    base: &RuntimePlan,
) -> Result<Option<DatetimePlan>> {
    let request = publication.object()?.get("datetime");
    let Some(policy) = metadata.object()?.get("datetime") else {
        ensure(request.is_none(), "Datetime publication requires metadata")?;
        return Ok(None);
    };
    policy.fields(&[
        "binding",
        "utc_years",
        "parse_failure",
        "format_failure",
        "operations",
    ])?;
    for (key, value) in [
        ("binding", "native_clock_utc"),
        ("utc_years", "0000_9999"),
        ("parse_failure", "result_error"),
        ("format_failure", "throw_runtime_error"),
    ] {
        policy.literal(key, value)?;
    }
    let mut registered = BTreeSet::new();
    for value in policy.at("operations")?.array()? {
        let name = value.text()?;
        operation(name)?;
        ensure(
            registered.insert(name),
            "Duplicate datetime operation policy",
        )?;
    }
    ensure(!registered.is_empty(), "Empty datetime policy")?;
    let Some(request) = request else {
        return Ok(None);
    };
    request.fields(&["header", "operations"])?;
    let header = crate::public_header(request.at("header")?)?;
    base.check_header(&header)?;
    ensure(
        !base.scalars.aliases.contains_key("dt"),
        "Conflicting datetime namespace",
    )?;
    let mut selected = BTreeSet::new();
    let mut operations = vec![];
    let mut includes = BTreeSet::new();
    for value in request.at("operations")?.array()? {
        let name = value.text()?;
        ensure(registered.contains(name), "Unregistered datetime operation")?;
        ensure(selected.insert(name), "Duplicate datetime operation")?;
        let op = operation(name)?;
        // Native transport constructors are explicit dependencies, not inferred in emission.
        let unsigned = op.binding == Binding::UnsignedClock;
        let integer = if unsigned { "uint64" } else { "int64" };
        let native = if unsigned {
            "unsigned long long"
        } else {
            "long long"
        };
        ensure(
            base.scalars.types.iter().any(|t| t.name == integer),
            "Datetime requires published integer type",
        )?;
        ensure(
            base.scalars
                .conversions
                .iter()
                .any(|c| c.native_source && c.source == native && c.target == integer),
            "Datetime requires explicit native integer transport",
        )?;
        includes.insert(base.scalars.headers["types"].clone());
        if matches!(op.binding, Binding::FormatUtc | Binding::ParseUtc) {
            let string = base
                .string
                .as_ref()
                .ok_or_else(|| crate::Error("Datetime requires published string".into()))?;
            includes.insert(string.header.clone());
        }
        if op.binding == Binding::ParseUtc {
            ensure(
                base.carriers
                    .iter()
                    .any(|c| c.family == CarrierFamily::Result && c.payload == "int64"),
                "Datetime parse requires published result_int64",
            )?;
            includes.insert(
                base.carriers_header
                    .clone()
                    .expect("prepared result header"),
            );
        }
        operations.push(op);
    }
    ensure(!operations.is_empty(), "Empty datetime publication")?;
    operations.sort_by(|a, b| a.name.cmp(&b.name));
    Ok(Some(DatetimePlan {
        header,
        includes: includes.into_iter().collect(),
        operations,
    }))
}
impl DatetimePlan {
    pub fn describe(&self) -> serde_json::Value {
        json!({"module":"datetime", "namespace":"scpp::dt", "header":self.header,
            "utc_years":[0,9999], "parse_failure":"result_error", "format_failure":"throw_runtime_error",
            "operations":self.operations.iter().map(|o| json!({"name":o.name,"result":o.result,
                "arguments":o.argument.map(|a| vec![format!("const {a}&")]).unwrap_or_default()})).collect::<Vec<_>>(),
            "build":{"target":"scpp_datetime", "requires":"scpp_runtime", "generated_sources":["src/datetime.cpp"],
                "private_sources":["runtime/src/datetime/datetime.cpp"],"private_includes":["runtime/src/datetime"]}})
    }
}
