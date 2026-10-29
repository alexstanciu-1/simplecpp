//! Registered-record ownership preparation. Payload C++ traits remain compile-time obligations.
use crate::{
    Error, Result, ensure,
    json::Value,
    runtime::RuntimePlan,
    scalar,
    target::{Layout, TargetFacts},
};
use serde_json::json;
use std::collections::BTreeSet;

#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum Family {
    Shared,
    Weak,
    Unique,
    Value,
}
impl Family {
    fn parse(name: &str) -> Result<Self> {
        match name {
            "shared" => Ok(Self::Shared),
            "weak" => Ok(Self::Weak),
            "unique" => Ok(Self::Unique),
            "value" => Ok(Self::Value),
            _ => Err(Error("Ownership: invalid family".into())),
        }
    }
    pub fn name(self) -> &'static str {
        match self {
            Self::Shared => "shared",
            Self::Weak => "weak",
            Self::Unique => "unique",
            Self::Value => "value",
        }
    }
}
#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum QualificationEffect {
    RetainIdentity,
    TransferSourceEmpty,
}
#[derive(Clone, Debug)]
pub struct Qualification {
    pub source: String,
    pub effect: QualificationEffect,
}
#[derive(Clone, Debug)]
pub struct Specialization {
    pub name: String,
    pub family: Family,
    pub const_pointee: bool,
    pub layout: Option<Layout>,
    pub qualifications: Vec<Qualification>,
}
#[derive(Clone, Debug)]
pub struct Record {
    pub name: String,
    pub header: String,
    pub constructors: Vec<Vec<String>>,
    pub families: Vec<Specialization>,
}
#[derive(Clone, Debug)]
pub struct OwnershipPlan {
    pub header: String,
    pub records: Vec<Record>,
}

fn identifier(name: &str) -> bool {
    const KEYWORDS: &str = "alignas alignof and and_eq asm auto bitand bitor bool break case catch char char8_t char16_t char32_t class compl concept const consteval constexpr constinit const_cast continue co_await co_return co_yield decltype default delete do double dynamic_cast else enum explicit export extern false float for friend goto if inline int long mutable namespace new noexcept not not_eq nullptr operator or or_eq private protected public register reinterpret_cast requires return short signed sizeof static static_assert static_cast struct switch template this thread_local throw true try typedef typeid typename union unsigned using virtual void volatile wchar_t while xor xor_eq";
    name.as_bytes().first().is_some_and(u8::is_ascii_lowercase)
        && !name.contains("__")
        && !matches!(name, "std" | "detail")
        && !KEYWORDS.split_whitespace().any(|word| word == name)
        && name
            .bytes()
            .all(|c| c.is_ascii_lowercase() || c.is_ascii_digit() || c == b'_')
}
fn header(value: &Value) -> Result<String> {
    let name = value.text()?;
    // Preserve the production ownership path contract, which (unlike scalar
    // headers) admits /./ components. Tightening it is a separate policy change.
    ensure(
        name.starts_with("scpp/")
            && name.ends_with(".hpp")
            && !name.contains("..")
            && !name.contains("//")
            && name
                .bytes()
                .all(|c| c.is_ascii_lowercase() || c.is_ascii_digit() || b"_/.".contains(&c)),
        "Ownership: invalid header",
    )?;
    Ok(name.into())
}
fn specialization_name(family: Family, is_const: bool, record: &str) -> String {
    format!(
        "{}_{}{record}",
        family.name(),
        if is_const { "const_" } else { "" }
    )
}

pub(crate) fn prepare(
    metadata: &Value,
    publication: &Value,
    base: &RuntimePlan,
    facts: &TargetFacts,
) -> Result<Option<OwnershipPlan>> {
    let request = publication.object()?.get("ownership");
    let Some(m) = metadata.object()?.get("ownership") else {
        ensure(request.is_none(), "Ownership: missing metadata")?;
        return Ok(None);
    };
    m.fields(&[
        "binding",
        "families",
        "default",
        "construction",
        "access",
        "empty_access",
        "weak_access",
        "payload",
        "value",
        "const_pointee",
    ])?;
    for (field, expected) in [
        ("binding", "std_smart_pointer_inline"),
        ("default", "empty"),
        ("construction", "explicit_factory"),
        ("access", "checked_borrow"),
        ("empty_access", "throw_runtime_error"),
        ("weak_access", "explicit_lock"),
        ("payload", "registered_record"),
    ] {
        m.literal(field, expected)?;
    }
    let families = scalar::names(m.at("families")?, "Ownership: duplicate family")?;
    ensure(
        families == BTreeSet::from(["shared".into(), "weak".into(), "unique".into()]),
        "Ownership: unsupported families",
    )?;
    if let Some(v) = m.object()?.get("value") {
        v.fields(&["binding", "construction", "special_members", "access"])?;
        for (field, expected) in [
            ("binding", "inline_record"),
            ("construction", "explicit"),
            ("special_members", "payload"),
            ("access", "borrow"),
        ] {
            v.literal(field, expected)?;
        }
    }
    if m.object()?.contains_key("const_pointee") {
        m.literal("const_pointee", "explicit_add_const")?;
    }
    let Some(p) = request else {
        return Ok(None);
    };
    p.fields(&["header", "records"])?;
    let output_header = header(p.at("header")?)?;
    base.check_header(&output_header)?;
    ensure(
        base.scalars.values.iter().any(|v| v.name == "bool_t"),
        "Ownership: bool_t must be published",
    )?;
    let inputs: BTreeSet<_> = base
        .scalars
        .types
        .iter()
        .map(|v| v.name.clone())
        .chain(base.scalars.values.iter().map(|v| v.name.clone()))
        .collect();
    let mut names = inputs.clone();
    names.extend(base.vectors.iter().map(|v| v.name.clone()));
    names.extend(base.carriers.iter().map(|v| v.name.clone()));
    names.extend(base.scalars.aliases.keys().cloned());
    if let Some(s) = &base.string {
        names.insert(s.name.clone());
    }
    names.extend(["error_t".into(), "null".into()]);
    let mut records = vec![];
    for r in p.at("records")?.array()? {
        r.fields(&[
            "name",
            "header",
            "constructors",
            "families",
            "const_families",
        ])?;
        let name = r.at("name")?.text()?;
        ensure(
            identifier(name) && names.insert(name.into()),
            "Ownership: invalid or conflicting record name",
        )?;
        let record_header = header(r.at("header")?)?;
        base.check_header(&record_header)?;
        ensure(
            record_header != output_header,
            "Ownership: record header conflicts with generated header",
        )?;
        let mut specializations = vec![];
        for (field, is_const) in [("families", false), ("const_families", true)] {
            let Some(request) = r.object()?.get(field) else {
                continue;
            };
            let requested = scalar::names(request, "Ownership: invalid or duplicate family")?;
            for f in &requested {
                ensure(
                    families.contains(f)
                        || (!is_const && f == "value" && m.object()?.contains_key("value")),
                    "Ownership: invalid or duplicate family",
                )?;
                ensure(
                    !is_const || m.object()?.contains_key("const_pointee"),
                    "Ownership: missing const-pointee policy",
                )?;
                let family = Family::parse(f)?;
                let identity = specialization_name(family, is_const, name);
                ensure(
                    names.insert(identity.clone()),
                    "Ownership: conflicting specialization",
                )?;
                let layout = if family == Family::Value {
                    None
                } else {
                    Some(
                        facts
                            .ownership()
                            .get(f)
                            .ok_or_else(|| Error("Missing native ownership layout".into()))?
                            .clone(),
                    )
                };
                specializations.push(Specialization {
                    name: identity,
                    family,
                    const_pointee: is_const,
                    layout,
                    qualifications: vec![],
                });
            }
            ensure(
                !requested.contains("weak") || requested.contains("shared"),
                "Ownership: weak requires matching shared publication",
            )?;
        }
        ensure(
            !specializations.is_empty(),
            "Ownership: empty family request",
        )?;
        let sources: Vec<_> = specializations
            .iter()
            .filter(|s| !s.const_pointee && s.family != Family::Value)
            .map(|s| s.family)
            .collect();
        for target in &mut specializations {
            if !target.const_pointee {
                continue;
            }
            for &source in &sources {
                let identity = specialization_name(source, false, name);
                if source == target.family {
                    if target.family != Family::Unique {
                        target.qualifications.push(Qualification {
                            source: identity.clone(),
                            effect: QualificationEffect::RetainIdentity,
                        });
                    }
                    target.qualifications.push(Qualification {
                        source: identity,
                        effect: QualificationEffect::TransferSourceEmpty,
                    });
                } else if target.family == Family::Weak && source == Family::Shared {
                    target.qualifications.push(Qualification {
                        source: identity,
                        effect: QualificationEffect::RetainIdentity,
                    });
                }
            }
        }
        let mut signatures = BTreeSet::new();
        for args in r.at("constructors")?.array()? {
            let mut signature = vec![];
            for arg in args.array()? {
                let arg = arg.text()?;
                ensure(
                    inputs.contains(arg),
                    "Ownership: constructor argument must be a published scalar",
                )?;
                signature.push(arg.to_owned());
            }
            ensure(
                signatures.insert(signature),
                "Ownership: duplicate constructor",
            )?;
        }
        ensure(
            !signatures.is_empty(),
            "Ownership: record needs constructor signatures",
        )?;
        records.push(Record {
            name: name.into(),
            header: record_header,
            constructors: signatures.into_iter().collect(),
            families: specializations,
        });
    }
    ensure(!records.is_empty(), "Ownership: empty record publication")?;
    records.sort_by(|a, b| a.name.cmp(&b.name));
    Ok(Some(OwnershipPlan {
        header: output_header,
        records,
    }))
}
impl OwnershipPlan {
    pub fn describe(&self) -> serde_json::Value {
        let records:Vec<_>=self.records.iter().map(|r|{
            let families:Vec<_>=r.families.iter().map(|s|{
                let mut row=json!({"name":s.name,"family":s.family.name()});
                if s.family==Family::Value {
                    row["binding"]=json!("inline_record"); row["special_members"]=json!("native_payload_dependent");
                    row["default"]=json!(if r.constructors.iter().any(Vec::is_empty){"payload_value_initialization"}else{"not_published"});
                    row["wrapper_allocation"]=json!("none");
                } else {
                    row["pointee"]=json!(if s.const_pointee{"const"}else{"mutable"});
                    row["qualification_constructors"]=json!(s.qualifications.iter().map(|c|json!({"source":c.source,
                        "parameter":if c.effect==QualificationEffect::TransferSourceEmpty{"rvalue_reference"}else{"const_reference"},"construction":"explicit",
                        "effect":if c.effect==QualificationEffect::TransferSourceEmpty{"transfer_source_empty"}else{"retain_identity"}})).collect::<Vec<_>>());
                    row["default"]=json!("empty"); row["move"]=json!("transfer_source_empty");
                    row["copy"]=json!(if s.family==Family::Unique{"deleted"}else{"retain_identity"});
                    if let Some(layout)=&s.layout {row["size"]=json!(layout.size);row["alignment"]=json!(layout.alignment);}
                }
                row
            }).collect();
            json!({"name":r.name,"header":r.header,"constructors":r.constructors,"families":families})
        }).collect();
        json!({"header":self.header,"handle_binding":"std_smart_pointer_inline","handle_empty_access":"std::runtime_error:ownership_empty_access",
            "weak_lock_failure":"empty_shared","handle_copy_allocation":"none","weak_condition":"not_published","handle_payload_access":"checked_borrow_no_implicit_extraction","records":records})
    }
}
