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
    pub vectors: Vec<VectorPlan>,
    pub vectors_header: Option<String>,
    pub carriers: Vec<CarrierPlan>,
    pub carriers_header: Option<String>,
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
        "vectors",
        "carriers",
    ])?;
    // Validate the outer demand first, then delegate the scalar projection to its sole owner.
    let scalar_request = Value::Object(
        publication
            .object()?
            .iter()
            .filter(|(key, _)| !matches!(key.as_str(), "string" | "vectors" | "carriers"))
            .map(|(k, v)| (k.clone(), v.clone()))
            .collect(),
    );
    let scalars = scalar::prepare(metadata, &scalar_request, facts)?;
    let registry = integer::registry(metadata, facts)?;
    let mut plan = RuntimePlan {
        scalars,
        string: None,
        vectors: vec![],
        vectors_header: None,
        carriers: vec![],
        carriers_header: None,
    };
    prepare_string(metadata, publication, &registry, &mut plan)?;
    prepare_vectors(metadata, publication, &registry, &mut plan)?;
    prepare_carriers(metadata, publication, &mut plan)?;
    plan.scalars
        .conversions
        .sort_by(|a, b| (&a.target, &a.source).cmp(&(&b.target, &b.source)));
    Ok(plan)
}

impl RuntimePlan {
    fn check_header(&self, path: &str) -> Result<()> {
        ensure(
            !self.scalars.headers.values().any(|p| p == path)
                && !self.string.as_ref().is_some_and(|s| s.header == path)
                && self.vectors_header.as_deref() != Some(path)
                && self.carriers_header.as_deref() != Some(path),
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
        out["vectors"] = json!(
            self.vectors
                .iter()
                .map(VectorPlan::describe)
                .collect::<Vec<_>>()
        );
        if let Some(header) = &self.vectors_header {
            out["vectors_header"] = json!(header);
        }
        out["carriers"] = json!(
            self.carriers
                .iter()
                .map(CarrierPlan::describe)
                .collect::<Vec<_>>()
        );
        if let Some(header) = &self.carriers_header {
            out["carriers_header"] = json!(header);
        }
        if self.requires_error() {
            out["error"] = json!({"type":"error_t","state":"explicit_present","message":"string","line":"int64","file":"string","inspection":"value_copy","global_short_name":false});
        }
        out
    }
    pub fn requires_error(&self) -> bool {
        self.carriers
            .iter()
            .any(|c| c.family == CarrierFamily::Result)
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
    let header = crate::public_header(request.at("header")?)?;
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

#[derive(Clone, Debug)]
pub struct VectorMethod {
    pub name: &'static str,
    pub result: String,
    pub arguments: Vec<String>,
    pub mutates: bool,
    pub adaptation: &'static str,
}
#[derive(Clone, Debug)]
pub struct VectorCast {
    pub source: String,
    pub element_source: String,
}
#[derive(Clone, Debug)]
pub struct VectorPlan {
    pub name: String,
    pub element: String,
    pub count_type: String,
    pub native_index: bool,
    pub native_value: Option<String>,
    pub operations: Vec<String>,
    pub casts: Vec<VectorCast>,
    pub methods: Vec<VectorMethod>,
}
fn prepare_vectors(
    metadata: &Value,
    publication: &Value,
    registry: &BTreeMap<String, integer::IntegerType>,
    plan: &mut RuntimePlan,
) -> Result<()> {
    let request = publication.object()?.get("vectors");
    let Some(record) = metadata.object()?.get("vector") else {
        return ensure(request.is_none(), "Vector publication requires metadata");
    };
    record.fields(&[
        "source_type",
        "storage",
        "count_type",
        "read",
        "mutation",
        "element_families",
        "native_index",
        "native_value",
        "operations",
    ])?;
    for (field, expected) in [
        ("source_type", "vector"),
        ("storage", "private_std_vector"),
        ("read", "value_copy"),
        ("mutation", "explicit_set"),
        ("native_index", "int"),
    ] {
        record.literal(field, expected)?;
    }
    let count = record.at("count_type")?.text()?;
    ensure(
        registry.get(count).is_some_and(|t| t.signed),
        "Vector count requires signed integer type",
    )?;
    let families = scalar::names(
        record.at("element_families")?,
        "Duplicate vector element family",
    )?;
    for family in &families {
        ensure(
            matches!(
                family.as_str(),
                "integer" | "floating" | "boolean" | "null" | "string"
            ),
            "Unsupported vector element family",
        )?;
    }
    let operations = scalar::names(record.at("operations")?, "Duplicate vector operation")?;
    for op in &operations {
        ensure(
            matches!(op.as_str(), "equal" | "not_equal"),
            "Unsupported vector operation",
        )?;
    }
    let native_values = scalar::names(record.at("native_value")?, "Duplicate vector native value")?;
    for native in &native_values {
        ensure(
            matches!(native.as_str(), "int" | "const char*"),
            "Unsupported vector native value",
        )?;
    }
    let Some(request) = request else {
        return Ok(());
    };
    request.fields(&["header", "specializations"])?;
    let header = crate::public_header(request.at("header")?)?;
    plan.check_header(&header)?;
    ensure(
        plan.has_integer(count) && plan.has_native("long long"),
        "Vector requires published count and native long long construction",
    )?;
    ensure(
        plan.has_value("bool_t"),
        "Vector requires published boolean type",
    )?;
    let mut vectors = BTreeMap::new();
    let mut demands = BTreeMap::new();
    for item in request.at("specializations")?.array()? {
        item.fields(&[
            "element",
            "native_index",
            "native_value",
            "operations",
            "casts_from",
        ])?;
        let element = item.at("element")?.text()?;
        let family = if plan.has_integer(element) {
            "integer"
        } else if element == "string" && plan.string.is_some() {
            "string"
        } else {
            match plan
                .scalars
                .values
                .iter()
                .find(|v| v.name == element)
                .map(|v| v.kind)
            {
                Some(scalar::ValueKind::Boolean) => "boolean",
                Some(scalar::ValueKind::Null) => "null",
                Some(scalar::ValueKind::Floating) => "floating",
                None => "",
            }
        };
        ensure(
            !family.is_empty() && families.contains(family),
            "Vector requires supported published element type",
        )?;
        let name = format!("vector_{element}");
        ensure(
            !vectors.contains_key(&name),
            "Duplicate vector specialization",
        )?;
        let native_index = item.at("native_index")?.boolean()?;
        ensure(
            !native_index || plan.has_native("int"),
            "Vector native index requires int construction",
        )?;
        let native = item.at("native_value")?.text()?;
        let native_value = if native == "none" {
            None
        } else {
            ensure(
                native_values.contains(native),
                "Vector native value is not enabled",
            )?;
            if native == "const char*" {
                ensure(
                    family == "string",
                    "C-string adapter requires string element",
                )?;
            } else {
                ensure(
                    plan.scalars
                        .conversions
                        .iter()
                        .any(|c| c.target == element && c.source == "int" && c.native_source),
                    "Vector int adapter requires checked element construction",
                )?;
            }
            Some(native.to_owned())
        };
        let selected_operations = scalar::names(
            item.at("operations")?,
            "Duplicate published vector operation",
        )?;
        for op in &selected_operations {
            ensure(operations.contains(op), "Vector operation is not enabled")?;
        }
        if !selected_operations.is_empty() {
            let equality = match family {
                "string" => plan
                    .string
                    .as_ref()
                    .is_some_and(|s| s.operations.iter().any(|op| op.name == "equal")),
                "integer" | "floating" => plan.scalars.operations.iter().any(|op| {
                    op.name == "equal"
                        && op.left.identity == element
                        && !op.left.native
                        && op
                            .right
                            .as_ref()
                            .is_some_and(|r| r.identity == element && !r.native)
                }),
                _ => plan
                    .scalars
                    .values
                    .iter()
                    .any(|v| v.name == element && v.operations.iter().any(|op| op == "equal")),
            };
            ensure(
                equality,
                "Vector equality requires published element equality",
            )?;
        }
        let sources = scalar::names(item.at("casts_from")?, "Duplicate vector cast")?;
        ensure(
            !sources.contains(&name),
            "Vector identity uses copy construction",
        )?;
        demands.insert(name.clone(), sources);
        let mut vector = VectorPlan {
            name: name.clone(),
            element: element.into(),
            count_type: count.into(),
            native_index,
            native_value,
            operations: selected_operations.into_iter().collect(),
            casts: vec![],
            methods: vec![],
        };
        vector.resolve_methods();
        vectors.insert(name, vector);
    }
    ensure(!vectors.is_empty(), "Empty vector publication")?;
    // Resolve cross-specialization edges after all identities are registered. Demand order is irrelevant.
    let elements: BTreeMap<_, _> = vectors
        .iter()
        .map(|(name, v)| (name.clone(), v.element.clone()))
        .collect();
    for vector in vectors.values_mut() {
        for source in &demands[&vector.name] {
            let element_source = elements.get(source).ok_or_else(|| {
                Error("Vector cast requires published source specialization".into())
            })?;
            ensure(
                plan.scalars.conversions.iter().any(|c| {
                    c.target == vector.element && c.source == *element_source && !c.native_source
                }),
                "Vector cast requires published explicit element conversion",
            )?;
            vector.casts.push(VectorCast {
                source: source.clone(),
                element_source: element_source.clone(),
            });
        }
        plan.scalars
            .source_types
            .insert(format!("vector<{}>", vector.element), vector.name.clone());
    }
    plan.vectors = vectors.into_values().collect();
    plan.vectors_header = Some(header);
    Ok(())
}
impl VectorPlan {
    fn resolve_methods(&mut self) {
        let mut methods = vec![];
        let mut add = |name, result: &str, arguments: Vec<String>, mutates, adaptation| {
            methods.push(VectorMethod {
                name,
                result: result.into(),
                arguments,
                mutates,
                adaptation,
            })
        };
        add("size", &self.count_type, vec![], false, "checked_count");
        add("empty", "bool_t", vec![], false, "none");
        add("clear", "void", vec![], true, "none");
        let mut indices = vec![self.count_type.as_str()];
        if self.native_index {
            indices.push("int");
        }
        let mut values = vec![self.element.as_str()];
        if let Some(native) = &self.native_value {
            values.push(native);
        }
        for index in indices {
            add(
                "at",
                &self.element,
                vec![index.into()],
                false,
                if index == self.count_type {
                    "checked_index_copy"
                } else {
                    "checked_native_index_copy"
                },
            );
            for value in &values {
                add(
                    "set",
                    "void",
                    vec![index.into(), (*value).into()],
                    true,
                    if index == self.count_type && *value == self.element {
                        "checked_index_move_value"
                    } else {
                        "explicit_arguments_then_checked_set"
                    },
                );
            }
        }
        for value in values {
            add(
                "append",
                "void",
                vec![value.into()],
                true,
                if value == self.element {
                    "move_value"
                } else {
                    "explicit_element_then_append"
                },
            );
        }
        self.methods = methods;
    }
    fn describe(&self) -> serde_json::Value {
        json!({"name":self.name,"element":self.element,"source_type":format!("vector<{}>",self.element),"count_type":self.count_type,
            "storage":"private_std_vector","lifecycle":"owned_copy_noexcept_move","read":"checked_index_value_copy","set":"checked_index_move_value",
            "native_index":self.native_index,"native_value":self.native_value.as_deref().unwrap_or(""),
            "operations":self.operations.iter().map(|op|json!({"operation":op,"symbol":if op=="equal"{"=="}else{"!="},"implementation":"elementwise_equality","element_dependency":"operator==","result":"bool_t"})).collect::<Vec<_>>(),
            "methods":self.methods.iter().map(|m|json!({"name":m.name,"result":m.result,"receiver":if m.mutates{"mutable_reference"}else{"const_reference"},"result_category":"value","adaptation":m.adaptation,"arguments":m.arguments})).collect::<Vec<_>>(),
            "casts_from":self.casts.iter().map(|c|json!({"source":c.source,"explicit":true,"element_source":c.element_source,"element_target":self.element,"implementation":"elementwise_explicit_conversion","failure":"discard_partial_target_keep_source"})).collect::<Vec<_>>()})
    }
}

#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum CarrierFamily {
    Nullable,
    Result,
    ResultOrFalse,
    ResultOrBool,
}
impl CarrierFamily {
    fn parse(name: &str) -> Result<Self> {
        match name {
            "nullable" => Ok(Self::Nullable),
            "result" => Ok(Self::Result),
            "result_or_false" => Ok(Self::ResultOrFalse),
            "result_or_bool" => Ok(Self::ResultOrBool),
            _ => Err(Error("Unknown carrier family".into())),
        }
    }
    fn name(self) -> &'static str {
        match self {
            Self::Nullable => "nullable",
            Self::Result => "result",
            Self::ResultOrFalse => "result_or_false",
            Self::ResultOrBool => "result_or_bool",
        }
    }
    pub fn default_state(self) -> &'static str {
        match self {
            Self::Nullable => "empty",
            Self::Result => "value",
            Self::ResultOrFalse | Self::ResultOrBool => "false",
        }
    }
}
#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum Extraction {
    ImplicitCheckedCopy,
    ExplicitCheckedCopy,
}
impl Extraction {
    fn parse(name: &str) -> Result<Self> {
        match name {
            "implicit_checked_one_layer_value_copy" => Ok(Self::ImplicitCheckedCopy),
            "explicit_checked_one_layer_value_copy" => Ok(Self::ExplicitCheckedCopy),
            _ => Err(Error("Unsupported carrier extraction policy".into())),
        }
    }
    fn name(self) -> &'static str {
        match self {
            Self::ImplicitCheckedCopy => "implicit_checked_one_layer_value_copy",
            Self::ExplicitCheckedCopy => "explicit_checked_one_layer_value_copy",
        }
    }
}
#[derive(Clone, Debug)]
pub struct CarrierPlan {
    pub name: String,
    pub family: CarrierFamily,
    pub payload: String,
    pub extraction: Extraction,
}
fn prepare_carriers(metadata: &Value, publication: &Value, plan: &mut RuntimePlan) -> Result<()> {
    let request = publication.object()?.get("carriers");
    let Some(record) = metadata.object()?.get("carriers") else {
        return ensure(request.is_none(), "Carrier publication requires metadata");
    };
    record.fields(&["extraction", "construction", "families", "error"])?;
    let extraction = Extraction::parse(record.at("extraction")?.text()?)?;
    record.literal("construction", "explicit")?;
    let error = record.at("error")?;
    error.fields(&["type", "message", "line", "file", "state"])?;
    for (field, expected) in [
        ("type", "error_t"),
        ("message", "string"),
        ("line", "int64"),
        ("file", "string"),
        ("state", "explicit_present"),
    ] {
        error.literal(field, expected)?;
    }
    let families = record.at("families")?;
    families.fields(&["nullable", "result", "result_or_false", "result_or_bool"])?;
    for (name, state) in families.object()? {
        ensure(
            state.text()? == CarrierFamily::parse(name)?.default_state(),
            "Unsupported carrier default state",
        )?;
    }
    let Some(request) = request else {
        return Ok(());
    };
    request.fields(&["header", "specializations"])?;
    let header = crate::public_header(request.at("header")?)?;
    plan.check_header(&header)?;
    ensure(
        plan.has_value("bool_t"),
        "Carriers require published boolean type",
    )?;
    let mut carriers = BTreeMap::new();
    for item in request.at("specializations")?.array()? {
        item.fields(&["family", "payload"])?;
        let name = item.at("family")?.text()?;
        families.at(name)?;
        let family = CarrierFamily::parse(name)?;
        let payload = item.at("payload")?.text()?;
        ensure(
            family != CarrierFamily::Nullable || plan.has_value("null_t"),
            "Nullable requires published null_t",
        )?;
        let supported = plan.has_integer(payload)
            || (payload == "string" && plan.string.is_some())
            || plan.vectors.iter().any(|v| v.name == payload)
            || plan
                .scalars
                .values
                .iter()
                .any(|v| v.name == payload && v.kind != scalar::ValueKind::Null);
        ensure(
            supported,
            "Carrier payload must be a published scalar, string or vector; null/nested carriers are not admitted",
        )?;
        ensure(
            family != CarrierFamily::Result || (plan.string.is_some() && plan.has_integer("int64")),
            "Result error payload requires string and int64",
        )?;
        let identity = format!("{name}_{payload}");
        ensure(
            !carriers.contains_key(&identity),
            "Duplicate carrier specialization",
        )?;
        carriers.insert(
            identity.clone(),
            CarrierPlan {
                name: identity.clone(),
                family,
                payload: payload.into(),
                extraction,
            },
        );
        plan.scalars
            .source_types
            .insert(format!("{name}<{payload}>"), identity);
    }
    ensure(!carriers.is_empty(), "Empty carrier publication")?;
    plan.carriers = carriers.into_values().collect();
    plan.carriers_header = Some(header);
    Ok(())
}
impl CarrierPlan {
    fn describe(&self) -> serde_json::Value {
        json!({"name":self.name,"family":self.family.name(),"payload":self.payload,"default_state":self.family.default_state(),"boxing":"explicit",
            "extraction":self.extraction.name(),"extraction_can_fail":true,"non_payload_extraction":"throw_runtime_error","condition":"query_state_explicitly",
            "lifecycle":"owned_copy_move_resets_source_to_default","lifted_operators":false,"result_category":"value","explicit_lowering":"value_then_payload_operation"})
    }
}
