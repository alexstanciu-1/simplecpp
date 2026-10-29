//! JSON grammar comes from serde_json; this adapter owns metadata-specific guarantees.
use crate::{Error, Result, ensure};
use serde::de::{MapAccess, Visitor};
use serde::{Deserialize, Deserializer};
use serde_json::value::RawValue;
use std::collections::{BTreeMap, BTreeSet};
use std::fmt;

#[derive(Clone, Debug, PartialEq)]
pub enum Value {
    Null,
    Boolean(bool),
    Number(String),
    Text(String),
    Array(Vec<Value>),
    Object(BTreeMap<String, Value>),
}

struct Members<'a>(Vec<(String, &'a RawValue)>);
impl<'de> Deserialize<'de> for Members<'de> {
    fn deserialize<D: Deserializer<'de>>(deserializer: D) -> std::result::Result<Self, D::Error> {
        struct ObjectVisitor;
        impl<'de> Visitor<'de> for ObjectVisitor {
            type Value = Members<'de>;
            fn expecting(&self, f: &mut fmt::Formatter) -> fmt::Result {
                f.write_str("a JSON object")
            }
            fn visit_map<A: MapAccess<'de>>(
                self,
                mut map: A,
            ) -> std::result::Result<Self::Value, A::Error> {
                let mut entries = Vec::new();
                let mut keys = BTreeSet::new();
                while let Some((key, value)) = map.next_entry::<String, &RawValue>()? {
                    if !keys.insert(key.clone()) {
                        return Err(serde::de::Error::custom(format!(
                            "duplicate object key {key:?}"
                        )));
                    }
                    entries.push((key, value));
                }
                Ok(Members(entries))
            }
        }
        deserializer.deserialize_map(ObjectVisitor)
    }
}

pub fn parse(bytes: &[u8], file: &str) -> Result<Value> {
    let input = std::str::from_utf8(bytes).map_err(|e| {
        Error(format!(
            "{file}: byte {} at $: invalid UTF-8",
            e.valid_up_to()
        ))
    })?;
    fn decode<T: for<'a> Deserialize<'a>>(text: &str) -> std::result::Result<T, serde_json::Error> {
        serde_json::from_str(text)
    }
    fn walk(raw: &RawValue, input: &str, file: &str, path: &str, depth: usize) -> Result<Value> {
        let text = raw.get();
        let base = text.as_ptr() as usize - input.as_ptr() as usize;
        let fail = |e: serde_json::Error| {
            let line_start: usize = text
                .split_inclusive('\n')
                .take(e.line().saturating_sub(1))
                .map(str::len)
                .sum();
            Error(format!(
                "{file}: byte {} at {path}: {e}",
                base + line_start + e.column().saturating_sub(1)
            ))
        };
        match text.as_bytes()[0] {
            b'{' | b'[' if depth >= 128 => Err(Error(format!(
                "{file}: byte {base} at {path}: nesting limit exceeded"
            ))),
            b'{' => {
                let members: Members = serde_json::from_str(text).map_err(fail)?;
                let mut result = BTreeMap::new();
                for (key, child) in members.0 {
                    let next_path =
                        format!("{path}[{}]", serde_json::to_string(&key).map_err(fail)?);
                    result.insert(key, walk(child, input, file, &next_path, depth + 1)?);
                }
                Ok(Value::Object(result))
            }
            b'[' => {
                let children: Vec<&RawValue> = serde_json::from_str(text).map_err(fail)?;
                children
                    .into_iter()
                    .enumerate()
                    .map(|(i, child)| walk(child, input, file, &format!("{path}[{i}]"), depth + 1))
                    .collect::<Result<Vec<_>>>()
                    .map(Value::Array)
            }
            b'"' => decode(text).map(Value::Text).map_err(fail),
            b't' | b'f' => decode(text).map(Value::Boolean).map_err(fail),
            b'n' => Ok(Value::Null),
            _ => Ok(Value::Number(text.to_owned())),
        }
    }
    let raw: &RawValue = serde_json::from_str(input).map_err(|e| {
        let start: usize = input
            .split_inclusive('\n')
            .take(e.line().saturating_sub(1))
            .map(str::len)
            .sum();
        Error(format!(
            "{file}: byte {} at $: {e}",
            start + e.column().saturating_sub(1)
        ))
    })?;
    walk(raw, input, file, "$", 0)
}

impl Value {
    pub fn object(&self) -> Result<&BTreeMap<String, Value>> {
        if let Self::Object(value) = self {
            Ok(value)
        } else {
            Err(Error("Expected metadata object".into()))
        }
    }
    pub fn fields(&self, allowed: &[&str]) -> Result<()> {
        for name in self.object()?.keys() {
            ensure(
                allowed.contains(&name.as_str()),
                &format!("Unknown field: {name}"),
            )?;
        }
        Ok(())
    }
    pub fn at(&self, name: &str) -> Result<&Value> {
        self.object()?
            .get(name)
            .ok_or_else(|| Error(format!("Missing metadata field: {name}")))
    }
    pub fn text(&self) -> Result<&str> {
        if let Self::Text(value) = self {
            Ok(value)
        } else {
            Err(Error("Expected metadata string".into()))
        }
    }
    pub fn array(&self) -> Result<&[Value]> {
        if let Self::Array(value) = self {
            Ok(value)
        } else {
            Err(Error("Expected metadata array".into()))
        }
    }
    pub fn boolean(&self) -> Result<bool> {
        if let Self::Boolean(value) = self {
            Ok(*value)
        } else {
            Err(Error("Expected metadata boolean".into()))
        }
    }
    pub fn unsigned(&self) -> Result<u64> {
        let Self::Number(token) = self else {
            return Err(Error("Expected unsigned integer token".into()));
        };
        ensure(
            !token.is_empty() && token.bytes().all(|b| b.is_ascii_digit()),
            "Expected unsigned integer token",
        )?;
        token
            .parse()
            .map_err(|_| Error("Unsigned integer is out of range".into()))
    }
    pub fn literal(&self, name: &str, expected: &str) -> Result<()> {
        ensure(
            self.at(name)?.text()? == expected,
            &format!("Unsupported {name}"),
        )
    }
}
