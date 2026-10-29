//! First preparation batch: integer identities and checked construction only.
//! Other runtime policies and operation families are not validated by this projection.
use crate::json::Value;
use crate::target::TargetFacts;
use crate::{Error, Result, ensure};
use std::collections::{BTreeMap, BTreeSet};

#[derive(Clone, Debug, Eq, PartialEq)]
pub struct IntegerType {
    pub name: String,
    pub representation: String,
    pub signed: bool,
    pub bits: u32,
}
#[derive(Clone, Debug, Eq, PartialEq)]
pub struct NativeInput {
    pub cpp: String,
    pub signed: bool,
    pub digits: u32,
}
#[derive(Clone, Debug, Eq, PartialEq)]
pub struct CheckedConversion {
    pub target: String,
    pub source: String,
    pub native_source: bool,
    pub lossless: bool,
}
#[derive(Clone, Debug)]
pub struct IntegerConstructionPlan {
    pub types: Vec<IntegerType>,
    pub native_inputs: Vec<NativeInput>,
    pub conversions: Vec<CheckedConversion>,
}

pub fn prepare(
    metadata: &Value,
    publication: &Value,
    facts: &TargetFacts,
) -> Result<IntegerConstructionPlan> {
    metadata.fields(&[
        "version",
        "types",
        "representation",
        "construction",
        "promotion_policies",
        "operations",
        "values",
        "source_types",
        "aliases",
        "floating",
        "scalar_casts",
        "string",
        "vector",
        "carriers",
        "ownership",
        "hash",
    ])?;
    publication.fields(&[
        "version",
        "namespace",
        "types",
        "operators",
        "headers",
        "values",
        "short_aliases",
    ])?;
    ensure(
        metadata.at("version")?.unsigned()? == 1 && publication.at("version")?.unsigned()? == 1,
        "Unsupported metadata version",
    )?;
    publication.literal("namespace", "scpp")?;
    for field in ["operators", "values", "short_aliases"] {
        ensure(
            publication.at(field)?.array()?.is_empty(),
            "Integer construction batch requires empty operators, values and short_aliases",
        )?;
    }
    // These paths are not part of the construction plan, but must never be admitted unchecked.
    let headers = publication.at("headers")?;
    headers.fields(&[
        "types",
        "values",
        "operators",
        "short_names",
        "native_bridge",
    ])?;
    let mut paths = BTreeSet::new();
    for field in [
        "types",
        "values",
        "operators",
        "short_names",
        "native_bridge",
    ] {
        let path = crate::public_header(headers.at(field)?)?;
        ensure(paths.insert(path), "Conflicting public headers")?;
    }
    let registry = registry(metadata, facts)?;
    let mut types = BTreeMap::new();
    for name in publication.at("types")?.array()? {
        let name = name.text()?;
        let item = registry
            .get(name)
            .ok_or_else(|| Error("Unknown published integer type".into()))?;
        ensure(
            types.insert(name.to_owned(), item.clone()).is_none(),
            "Duplicate published type",
        )?;
    }
    ensure(!types.is_empty(), "Empty integer construction publication")?;
    let native_inputs = native_inputs(metadata, facts)?;
    let conversions = conversions(&types, &native_inputs);
    Ok(IntegerConstructionPlan {
        types: types.into_values().collect(),
        native_inputs,
        conversions,
    })
}
impl IntegerConstructionPlan {
    /// A scoped projection for C++ parity, not a complete prepared_runtime_api artifact.
    pub fn describe(&self) -> serde_json::Value {
        serde_json::json!({
            "kind": "prepared_integer_construction", "schema": 1,
            "types": self.types.iter().map(|t| serde_json::json!({"name":t.name,"representation":t.representation,"signed":t.signed,"bits":t.bits})).collect::<Vec<_>>(),
            "native_inputs": self.native_inputs.iter().map(|n| serde_json::json!({"cpp":n.cpp,"signed":n.signed,"digits":n.digits})).collect::<Vec<_>>(),
            "conversions": self.conversions.iter().map(|c| serde_json::json!({"target":c.target,"source":c.source,"native_source":c.native_source,"lossless":c.lossless,"implementation":"checked_integer_cast","can_fail":!c.lossless,"source_category":"value"})).collect::<Vec<_>>()
        })
    }
}

impl IntegerType {
    pub fn digits(&self) -> u32 {
        self.bits - u32::from(self.signed)
    }
    pub fn fits(&self, signed: bool, digits: u32) -> bool {
        (!signed || self.signed) && self.digits() >= digits
    }
}

pub(crate) fn registry(
    metadata: &Value,
    facts: &TargetFacts,
) -> Result<BTreeMap<String, IntegerType>> {
    let representation = metadata.at("representation")?;
    representation.fields(&["binding", "default_initialization"])?;
    representation.literal("binding", "fixed_width_integer")?;
    representation.literal("default_initialization", "zero")?;
    let construction = metadata.at("construction")?;
    construction.fields(&[
        "public_integer_inputs",
        "native_integer_inputs",
        "native_conversion",
        "explicit",
    ])?;
    construction.literal("public_integer_inputs", "checked")?;
    construction.literal("native_conversion", "checked")?;
    ensure(
        construction.at("explicit")?.boolean()?,
        "Integer constructors must be explicit",
    )?;
    let mut registry = BTreeMap::new();
    for (name, row) in metadata.at("types")?.object()? {
        row.fields(&["signed", "bits"])?;
        let signed = row.at("signed")?.boolean()?;
        let bits = row.at("bits")?.unsigned()?;
        ensure([8, 16, 32, 64].contains(&bits), "Unsupported integer width")?;
        ensure(
            name == &format!("{}{bits}", if signed { "int" } else { "uint" }),
            "Integer identity disagrees with width/signedness",
        )?;
        let native = facts
            .fixed_integers()
            .get(name)
            .ok_or_else(|| Error(format!("Missing native representation for {name}")))?;
        ensure(
            native.signed == signed && native.digits == bits as u32 - u32::from(signed),
            "Native fixed-width mismatch",
        )?;
        registry.insert(
            name.clone(),
            IntegerType {
                name: name.clone(),
                representation: format!("__{}{bits}_TYPE__", if signed { "INT" } else { "UINT" }),
                signed,
                bits: bits as u32,
            },
        );
    }
    ensure(!registry.is_empty(), "Expected integer type definitions")?;
    Ok(registry)
}
pub(crate) fn native_inputs(metadata: &Value, facts: &TargetFacts) -> Result<Vec<NativeInput>> {
    let construction = metadata.at("construction")?;
    let mut native_inputs = BTreeMap::new();
    for name in construction.at("native_integer_inputs")?.array()? {
        let name = name.text()?;
        let input = facts
            .native_integers()
            .get(name)
            .ok_or_else(|| Error("Unsupported native integer input".into()))?;
        ensure(
            native_inputs
                .insert(
                    name.to_owned(),
                    NativeInput {
                        cpp: name.into(),
                        signed: input.signed,
                        digits: input.digits,
                    },
                )
                .is_none(),
            "Duplicate native integer input",
        )?;
    }
    ensure(!native_inputs.is_empty(), "Empty native integer input set")?;
    Ok(native_inputs.into_values().collect())
}
pub(crate) fn conversions(
    types: &BTreeMap<String, IntegerType>,
    native_inputs: &[NativeInput],
) -> Vec<CheckedConversion> {
    let mut conversions = Vec::new();
    for target in types.values() {
        for source in native_inputs.iter() {
            conversions.push(CheckedConversion {
                target: target.name.clone(),
                source: source.cpp.clone(),
                native_source: true,
                lossless: target.fits(source.signed, source.digits),
            });
        }
        for source in types.values().filter(|s| s.name != target.name) {
            conversions.push(CheckedConversion {
                target: target.name.clone(),
                source: source.name.clone(),
                native_source: false,
                lossless: target.fits(source.signed, source.digits()),
            });
        }
    }
    conversions.sort_by(|a, b| {
        (&a.target, &a.source, a.native_source).cmp(&(&b.target, &b.source, b.native_source))
    });
    conversions
}
