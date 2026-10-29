//! Concrete typed-hash preparation. No source admission or C++ emission.
use crate::{
    Result, ensure,
    json::Value,
    runtime::{CarrierFamily, RuntimePlan},
};
use serde_json::json;
use std::collections::{BTreeMap, BTreeSet};

#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum KeyKind {
    Integer,
    String,
}
#[derive(Clone, Debug)]
pub struct HashPlan {
    pub name: String,
    pub key: String,
    pub value: String,
    pub key_kind: KeyKind,
    pub nullable: String,
}
#[derive(Clone, Debug)]
pub struct HashPublication {
    pub header: String,
    pub count_type: String,
    pub specializations: Vec<HashPlan>,
}

pub fn prepare(
    metadata: &Value,
    publication: &Value,
    base: &RuntimePlan,
) -> Result<Option<HashPublication>> {
    let request = publication.object()?.get("hashes");
    let Some(policy) = metadata.object()?.get("hash") else {
        ensure(request.is_none(), "Hash publication requires metadata")?;
        return Ok(None);
    };
    policy.fields(&[
        "binding",
        "key_types",
        "value_types",
        "count_type",
        "key_policy",
        "read",
        "optional_lookup",
        "append",
    ])?;
    for (field, expected) in [
        ("binding", "ordered_typed_swiss"),
        ("count_type", "int64"),
        ("key_policy", "strict"),
        ("read", "value_copy"),
        ("optional_lookup", "nullable"),
        ("append", "max_live_nonnegative_plus_one"),
    ] {
        policy.literal(field, expected)?;
    }
    for field in ["key_types", "value_types"] {
        let mut types = BTreeSet::new();
        for value in policy.at(field)?.array()? {
            ensure(types.insert(value.text()?), "Duplicate hash type policy")?;
        }
        ensure(
            types == BTreeSet::from(["int64", "string"]),
            "Unsupported hash type policy",
        )?;
    }
    let Some(request) = request else {
        return Ok(None);
    };
    request.fields(&["header", "specializations"])?;
    let header = crate::public_header(request.at("header")?)?;
    base.check_header(&header)?;
    ensure(
        base.scalars.types.iter().any(|t| t.name == "int64"),
        "Hash requires published int64 count",
    )?;
    ensure(
        base.scalars.values.iter().any(|t| t.name == "bool_t"),
        "Hash requires published bool_t",
    )?;
    let mut hashes = BTreeMap::new();
    for item in request.at("specializations")?.array()? {
        item.fields(&["key", "value"])?;
        let key = item.at("key")?.text()?;
        let value = item.at("value")?.text()?;
        for ty in [key, value] {
            ensure(
                matches!(ty, "int64" | "string"),
                "Hash supports only int64/string keys and values",
            )?;
            ensure(
                ty != "string" || base.string.is_some(),
                "Hash requires published string",
            )?;
        }
        let nullable = base
            .carriers
            .iter()
            .find(|c| c.family == CarrierFamily::Nullable && c.payload == value)
            .ok_or_else(|| crate::Error("Hash find requires published nullable payload".into()))?;
        let name = format!("hash_{value}_{key}");
        ensure(
            !base.scalars.aliases.contains_key(&name),
            "Conflicting hash name",
        )?;
        ensure(!hashes.contains_key(&name), "Duplicate hash specialization")?;
        hashes.insert(
            name.clone(),
            HashPlan {
                name,
                key: key.into(),
                value: value.into(),
                nullable: nullable.name.clone(),
                key_kind: if key == "int64" {
                    KeyKind::Integer
                } else {
                    KeyKind::String
                },
            },
        );
    }
    ensure(!hashes.is_empty(), "Empty hash publication")?;
    Ok(Some(HashPublication {
        header,
        count_type: "int64".into(),
        specializations: hashes.into_values().collect(),
    }))
}
impl HashPublication {
    pub fn describe(&self) -> serde_json::Value {
        let specializations: Vec<_> = self.specializations.iter().map(|h| {
            let mut methods = vec![
                json!({"name":"size","arguments":[],"result":self.count_type,"mutates":false}),
                json!({"name":"empty","arguments":[],"result":"bool_t","mutates":false}),
                json!({"name":"has","arguments":[format!("const {}&",h.key)],"result":"bool_t","mutates":false}),
                json!({"name":"at","arguments":[format!("const {}&",h.key)],"result":h.value,"mutates":false,"missing":"throw_out_of_range"}),
                json!({"name":"find","arguments":[format!("const {}&",h.key)],"result":h.nullable,"mutates":false,"missing":"empty_nullable"}),
                json!({"name":"set","arguments":[h.key,h.value],"result":"void","mutates":true}),
                json!({"name":"remove","arguments":[format!("const {}&",h.key)],"result":"bool_t","mutates":true}),
                json!({"name":"clear","arguments":[],"result":"void","mutates":true}),
            ];
            if h.key_kind == KeyKind::Integer { methods.push(json!({"name":"append","arguments":[h.value],"result":h.key,"mutates":true,"policy":"max_live_nonnegative_plus_one","overflow":"throw_overflow_error"})); }
            json!({"name":h.name,"source_family":"hash","value":h.value,"key":h.key,"key_policy":"strict","read":"value_copy","copy":"independent","move":"transfer_source_empty","storage":"private_ordered_typed_swiss","mutation_failure":"logical_contents_unchanged","traversal":"deferred_RT-DEBT-01","methods":methods})
        }).collect();
        json!({"header":self.header,"count_type":self.count_type,"specializations":specializations})
    }
}
