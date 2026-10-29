//! Validated native facts. The expected context must come from the current build driver.
use crate::json::{self, Value};
use crate::{Error, Result, ensure};
use std::collections::BTreeMap;

#[derive(Clone, Debug, Eq, PartialEq)]
pub struct Layout {
    pub size: u32,
    pub alignment: u32,
}
#[derive(Clone, Debug, Eq, PartialEq)]
pub struct IntegerLayout {
    pub signed: bool,
    pub digits: u32,
    pub layout: Layout,
}
#[derive(Clone, Debug)]
pub struct TargetContext {
    id: String,
    target: String,
}
#[derive(Clone, Debug)]
pub struct TargetFacts {
    context: TargetContext,
    native: BTreeMap<String, IntegerLayout>,
    fixed: BTreeMap<String, IntegerLayout>,
    ownership: BTreeMap<String, Layout>,
    standard_library: String,
    has_int128: bool,
}
fn id(value: &Value) -> Result<String> {
    let text = value.text()?;
    ensure(
        text.len() == 64
            && text
                .bytes()
                .all(|b| b.is_ascii_digit() || (b'a'..=b'f').contains(&b)),
        "Invalid context fingerprint",
    )?;
    Ok(text.into())
}
fn number(value: &Value) -> Result<u32> {
    u32::try_from(value.unsigned()?).map_err(|_| Error("Native fact is out of range".into()))
}
fn layout(value: &Value) -> Result<Layout> {
    let size = number(value.at("size")?)?;
    let alignment = number(value.at("alignment")?)?;
    ensure(
        size > 0
            && size <= 4096
            && alignment.is_power_of_two()
            && alignment <= size
            && size.is_multiple_of(alignment),
        "Invalid native size/alignment",
    )?;
    Ok(Layout { size, alignment })
}
fn integers(value: &Value, names: &[&str]) -> Result<BTreeMap<String, IntegerLayout>> {
    value.fields(names)?;
    names
        .iter()
        .map(|&name| {
            let item = value.at(name)?;
            item.fields(&["signed", "digits", "size", "alignment"])?;
            let layout = layout(item)?;
            let signed = item.at("signed")?.boolean()?;
            let digits = number(item.at("digits")?)?;
            ensure(
                digits > 0 && digits <= 64 && digits + u32::from(signed) <= layout.size * 8,
                "Invalid native integer range",
            )?;
            Ok((
                name.into(),
                IntegerLayout {
                    signed,
                    digits,
                    layout,
                },
            ))
        })
        .collect()
}
impl TargetContext {
    /// A context is a trusted build input, not a claim of freshness inferred from the facts file.
    pub fn parse(bytes: &[u8]) -> Result<Self> {
        let root = json::parse(bytes, "target context")?;
        root.fields(&["schema", "context_id", "configuration"])?;
        ensure(
            root.at("schema")?.unsigned()? == 1,
            "Unsupported target context schema",
        )?;
        let config = root.at("configuration")?;
        config.fields(&[
            "target",
            "compiler",
            "compiler_version",
            "compiler_sha256",
            "flags",
            "probe_sha256",
            "preprocessed_sha256",
            "driver_sha256",
        ])?;
        let target = config.at("target")?.text()?;
        ensure(
            target.starts_with("x86_64-") && target.contains("linux"),
            "Unsupported target: native x86_64 Linux required",
        )?;
        for field in ["compiler", "compiler_version"] {
            ensure(
                !config.at(field)?.text()?.is_empty(),
                "Missing compiler identity",
            )?;
        }
        for field in [
            "compiler_sha256",
            "probe_sha256",
            "preprocessed_sha256",
            "driver_sha256",
        ] {
            id(config.at(field)?)?;
        }
        let flags = config.at("flags")?.array()?;
        ensure(!flags.is_empty(), "Missing C++ build flags")?;
        for flag in flags {
            flag.text()?;
        }
        Ok(Self {
            id: id(root.at("context_id")?)?,
            target: target.into(),
        })
    }
    pub fn id(&self) -> &str {
        &self.id
    }
    pub fn target(&self) -> &str {
        &self.target
    }
}
impl TargetFacts {
    pub fn parse(bytes: &[u8], expected: &TargetContext) -> Result<Self> {
        let root = json::parse(bytes, "target facts")?;
        root.fields(&[
            "schema",
            "context_id",
            "char_bits",
            "pointer_bytes",
            "standard_library",
            "native_integers",
            "fixed_integers",
            "double",
            "has_int128",
            "ownership",
        ])?;
        ensure(
            root.at("schema")?.unsigned()? == 1,
            "Unsupported target facts schema",
        )?;
        ensure(
            id(root.at("context_id")?)? == expected.id,
            "Target context mismatch: stale or incompatible facts",
        )?;
        ensure(
            root.at("char_bits")?.unsigned()? == 8 && root.at("pointer_bytes")?.unsigned()? == 8,
            "Unsupported native byte/pointer representation",
        )?;
        let standard_library = root.at("standard_library")?.text()?;
        ensure(
            standard_library.starts_with("libc++:") || standard_library.starts_with("libstdc++:"),
            "Unsupported C++ standard library",
        )?;
        let native = integers(
            root.at("native_integers")?,
            &[
                "int",
                "unsigned int",
                "long",
                "unsigned long",
                "long long",
                "unsigned long long",
            ],
        )?;
        for (name, item) in &native {
            ensure(
                item.signed != name.starts_with("unsigned"),
                "Native integer signedness mismatch",
            )?;
        }
        let fixed = integers(
            root.at("fixed_integers")?,
            &[
                "int8", "uint8", "int16", "uint16", "int32", "uint32", "int64", "uint64",
            ],
        )?;
        for (name, item) in &fixed {
            let signed = name.starts_with('i');
            let bits: u32 = name
                .trim_start_matches("uint")
                .trim_start_matches("int")
                .parse()
                .map_err(|_| Error("Invalid fixed integer identity".into()))?;
            ensure(
                item.signed == signed
                    && item.digits == bits - u32::from(signed)
                    && item.layout.size * 8 == bits,
                "Fixed integer representation mismatch",
            )?;
        }
        let double = root.at("double")?;
        double.fields(&[
            "iec559",
            "radix",
            "digits",
            "max_exponent",
            "size",
            "alignment",
        ])?;
        ensure(
            double.at("iec559")?.boolean()?
                && double.at("radix")?.unsigned()? == 2
                && double.at("digits")?.unsigned()? == 53
                && double.at("max_exponent")?.unsigned()? == 1024
                && layout(double)?.size == 8,
            "float64 requires IEEE binary64 double",
        )?;
        let owners = root.at("ownership")?;
        owners.fields(&["shared", "weak", "unique"])?;
        let mut ownership = BTreeMap::new();
        for family in ["shared", "weak", "unique"] {
            let value = owners.at(family)?;
            value.fields(&["size", "alignment"])?;
            ownership.insert(family.into(), layout(value)?);
        }
        Ok(Self {
            context: expected.clone(),
            native,
            fixed,
            ownership,
            standard_library: standard_library.into(),
            has_int128: root.at("has_int128")?.boolean()?,
        })
    }
    pub fn context(&self) -> &TargetContext {
        &self.context
    }
    pub fn native_integers(&self) -> &BTreeMap<String, IntegerLayout> {
        &self.native
    }
    pub fn fixed_integers(&self) -> &BTreeMap<String, IntegerLayout> {
        &self.fixed
    }
    pub fn ownership(&self) -> &BTreeMap<String, Layout> {
        &self.ownership
    }
    pub fn standard_library(&self) -> &str {
        &self.standard_library
    }
    pub fn has_int128(&self) -> bool {
        self.has_int128
    }
}
