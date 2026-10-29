//! Composition of prepared runtime families. No source-language admission or C++ emission.
use crate::{
    Error, Result, ensure, integer,
    json::Value,
    scalar::{self, Cast, Conversion, ScalarPlan},
    target::TargetFacts,
};
use serde_json::json;
use std::collections::BTreeMap;

#[derive(Clone, Debug)]
pub struct StringOperation {
    pub name: String,
    pub symbol: &'static str,
    pub append: bool,
    pub mutates: bool,
    pub result: String,
}
#[derive(Clone, Debug)]
pub struct StringPlan {
    pub name: String,
    pub count_type: String,
    pub header: String,
    pub operations: Vec<StringOperation>,
}
#[derive(Clone, Debug)]
pub struct RuntimePlan {
    pub scalars: ScalarPlan,
    pub string: Option<StringPlan>,
}

pub fn prepare(metadata: &Value, publication: &Value, facts: &TargetFacts) -> Result<RuntimePlan> {
    publication.fields(&[
        "version",
        "namespace",
        "types",
        "operators",
        "headers",
        "values",
        "short_aliases",
        "string",
    ])?;
    // Validate the outer demand first, then delegate the scalar projection to its sole owner.
    let scalar_request = Value::Object(
        publication
            .object()?
            .iter()
            .filter(|(key, _)| key.as_str() != "string")
            .map(|(k, v)| (k.clone(), v.clone()))
            .collect(),
    );
    let scalars = scalar::prepare(metadata, &scalar_request, facts)?;
    let registry = integer::registry(metadata, facts)?;
    let mut plan = RuntimePlan {
        scalars,
        string: None,
    };
    prepare_string(metadata, publication, &registry, &mut plan)?;
    plan.scalars
        .conversions
        .sort_by(|a, b| (&a.target, &a.source).cmp(&(&b.target, &b.source)));
    Ok(plan)
}

fn header(value: &Value) -> Result<String> {
    let path = value.text()?;
    ensure(
        path.starts_with("scpp/")
            && path.ends_with(".hpp")
            && !path.contains("..")
            && !path.contains("//")
            && !path.contains("/.")
            && path
                .bytes()
                .all(|b| b.is_ascii_lowercase() || b.is_ascii_digit() || b"_/.".contains(&b)),
        "Invalid public header path",
    )?;
    Ok(path.into())
}
impl RuntimePlan {
    fn check_header(&self, path: &str) -> Result<()> {
        ensure(
            !self.scalars.headers.values().any(|p| p == path)
                && !self.string.as_ref().is_some_and(|s| s.header == path),
            "Conflicting public headers",
        )
    }
    fn has_integer(&self, name: &str) -> bool {
        self.scalars.types.iter().any(|t| t.name == name)
    }
    fn has_value(&self, name: &str) -> bool {
        self.scalars.values.iter().any(|t| t.name == name)
    }
    fn has_native(&self, name: &str) -> bool {
        self.scalars.native_inputs.iter().any(|t| t.cpp == name)
    }
    pub fn describe(&self) -> serde_json::Value {
        let mut out = self.scalars.describe();
        if let Some(string) = &self.string {
            out["string"] = string.describe();
        }
        out
    }
}
fn string_binding(name: &str) -> Result<(&'static str, bool, bool)> {
    Ok(match name {
        "add" => ("+", true, false),
        "add_assign" => ("+=", true, true),
        "equal" => ("==", false, false),
        "not_equal" => ("!=", false, false),
        "less" => ("<", false, false),
        "less_equal" => ("<=", false, false),
        "greater" => (">", false, false),
        "greater_equal" => (">=", false, false),
        _ => return Err(Error("Unsupported string operation".into())),
    })
}
fn prepare_string(
    metadata: &Value,
    publication: &Value,
    registry: &BTreeMap<String, integer::IntegerType>,
    plan: &mut RuntimePlan,
) -> Result<()> {
    let request = publication.object()?.get("string");
    let Some(record) = metadata.object()?.get("string") else {
        return ensure(request.is_none(), "String publication requires metadata");
    };
    record.fields(&[
        "type",
        "source_type",
        "storage",
        "count_type",
        "construction",
        "null_pointer",
        "text_casts",
        "operations",
    ])?;
    for (field, expected) in [
        ("type", "string"),
        ("source_type", "string"),
        ("storage", "private_std_string"),
        ("construction", "explicit"),
        ("null_pointer", "reject"),
    ] {
        record.literal(field, expected)?;
    }
    let count = record.at("count_type")?.text()?;
    ensure(
        registry.get(count).is_some_and(|t| t.signed),
        "String count requires a signed integer type",
    )?;
    let capabilities = scalar::names(record.at("operations")?, "Duplicate string operation")?;
    for op in &capabilities {
        string_binding(op)?;
    }
    let casts = scalar::names(record.at("text_casts")?, "Duplicate text cast policy")?;
    for cast in &casts {
        ensure(
            matches!(
                cast.as_str(),
                "decimal_integer" | "decimal_float" | "boolean_words"
            ),
            "Unsupported text cast policy",
        )?;
    }
    let Some(request) = request else {
        return Ok(());
    };
    request.fields(&["header", "operations", "casts"])?;
    let header = header(request.at("header")?)?;
    plan.check_header(&header)?;
    ensure(
        plan.has_integer(count),
        "String requires published count type",
    )?;
    ensure(
        plan.has_value("bool_t"),
        "String requires published boolean type",
    )?;
    ensure(
        plan.has_native("long long"),
        "String count requires explicit native long long construction",
    )?;
    let mut operations = vec![];
    for name in scalar::names(
        request.at("operations")?,
        "Duplicate published string operation",
    )? {
        ensure(
            capabilities.contains(&name),
            "String operation is not enabled by metadata",
        )?;
        let (symbol, append, mutates) = string_binding(&name)?;
        operations.push(StringOperation {
            name,
            symbol,
            append,
            mutates,
            result: if append { "string" } else { "bool_t" }.into(),
        });
    }
    for target in scalar::names(request.at("casts")?, "Duplicate published text cast")? {
        let policy = if plan.has_integer(&target) {
            "decimal_integer"
        } else if target == "float64" && plan.has_value(&target) {
            "decimal_float"
        } else if target == "bool_t" && plan.has_value(&target) {
            "boolean_words"
        } else {
            return Err(Error(
                "Text cast requires a published numeric or boolean type".into(),
            ));
        };
        ensure(
            casts.contains(policy),
            "Text cast is not enabled by metadata",
        )?;
        let words = target == "bool_t";
        plan.scalars.conversions.push(Conversion {
            target: target.clone(),
            source: "string".into(),
            native_source: false,
            lossless: false,
            implementation: if words {
                Cast::ParseBooleanText
            } else {
                Cast::ParseDecimal
            },
            can_fail: true,
        });
        plan.scalars.conversions.push(Conversion {
            target: "string".into(),
            source: target.clone(),
            native_source: false,
            lossless: target != "float64",
            implementation: if words {
                Cast::FormatBooleanText
            } else {
                Cast::FormatDecimal
            },
            can_fail: true,
        });
    }
    plan.scalars
        .source_types
        .insert("string".into(), "string".into());
    plan.string = Some(StringPlan {
        name: "string".into(),
        count_type: count.into(),
        header,
        operations,
    });
    Ok(())
}
impl StringPlan {
    fn describe(&self) -> serde_json::Value {
        let operations:Vec<_>=self.operations.iter().map(|op|json!({"operation":op.name,"symbol":op.symbol,
            "form":if op.mutates{"compound"}else{"binary"},"implementation":if op.append{"string_append"}else{"string_compare"},
            "execution":self.name,"result":op.result,"left":self.name,"right":self.name,
            "result_category":if op.mutates{"left_reference"}else{"value"}})).collect();
        let methods:Vec<_>=[("size",self.count_type.as_str()),("empty","bool_t"),("data","const char*"),("append","void"),("clear","void")].into_iter().map(|(name,result)|{
            json!({"name":name,"result":result,"receiver":if matches!(name,"append"|"clear"){"mutable_reference"}else{"const_reference"},
                "arguments":if name=="append"{vec![format!("const {}&",self.name)]}else{vec![]},
                "result_category":if name=="data"{"borrowed_bytes_until_mutation_or_destruction"}else{"value"},"can_fail":matches!(name,"size"|"append")})
        }).collect();
        json!({"name":self.name,"count_type":self.count_type,"header":self.header,"storage":"private_std_string","lifecycle":"owned_copy_noexcept_move","empty_state":"null_storage",
            "operations":operations,"methods":methods,"construction":[
                {"arguments":["const char*"],"explicit":true,"null_pointer":"reject","can_fail":true},
                {"arguments":["const char*",self.count_type],"explicit":true,"null_pointer":"reject","can_fail":true}]})
    }
}
