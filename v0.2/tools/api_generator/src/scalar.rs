//! Scalar-only preparation. The runtime planner composes this with other families;
//! this narrower entrypoint rejects non-scalar demands and does not validate their metadata.
use crate::integer::{IntegerType, NativeInput};
use crate::{Error, Result, ensure, integer, json::Value, target::TargetFacts};
use std::collections::{BTreeMap, BTreeSet};

#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum Form {
    Binary,
    Unary,
    Compound,
    Prefix,
    Postfix,
}
impl Form {
    fn name(self) -> &'static str {
        match self {
            Self::Binary => "binary",
            Self::Unary => "unary",
            Self::Compound => "compound",
            Self::Prefix => "prefix",
            Self::Postfix => "postfix",
        }
    }
    fn binary(self) -> bool {
        matches!(self, Self::Binary | Self::Compound)
    }
    fn result_category(self) -> &'static str {
        match self {
            Self::Compound | Self::Prefix => "left_reference",
            Self::Postfix => "old_value",
            _ => "value",
        }
    }
}
#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum Promotion {
    DomainMaximum,
    ExactComparison,
    Preserve,
    CheckedShift,
    Floating,
}
impl Promotion {
    fn name(self) -> &'static str {
        match self {
            Self::DomainMaximum => "integer_domain_maximum",
            Self::ExactComparison => "exact_integer_comparison",
            Self::Preserve => "preserve_operand",
            Self::CheckedShift => "left_with_checked_count",
            Self::Floating => "floating_exact_integer",
        }
    }
}
#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum Cast {
    CheckedInteger,
    Numeric,
    FloatingInteger,
    Truth,
    ParseDecimal,
    FormatDecimal,
    ParseBooleanText,
    FormatBooleanText,
}
impl Cast {
    fn name(self) -> &'static str {
        match self {
            Self::CheckedInteger => "checked_integer_cast",
            Self::Numeric => "numeric_cast",
            Self::FloatingInteger => "floating_integer_cast",
            Self::Truth => "truth_cast",
            Self::ParseDecimal => "parse_decimal",
            Self::FormatDecimal => "format_decimal",
            Self::ParseBooleanText => "parse_boolean_text",
            Self::FormatBooleanText => "format_boolean_text",
        }
    }
}
#[derive(Clone, Debug, Eq, PartialEq)]
pub struct Conversion {
    pub target: String,
    pub source: String,
    pub native_source: bool,
    pub lossless: bool,
    pub implementation: Cast,
    pub can_fail: bool,
}
#[derive(Clone, Debug, Eq, PartialEq)]
pub struct Operand {
    pub cpp: String,
    pub identity: String,
    pub native: bool,
}
#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum Adaptation {
    Identity,
    LosslessInteger,
    LosslessNativeInteger,
    ExactIntegerToFloating,
}
impl Adaptation {
    fn name(self) -> &'static str {
        match self {
            Self::Identity => "identity",
            Self::LosslessInteger => "lossless_integer",
            Self::LosslessNativeInteger => "lossless_native_integer",
            Self::ExactIntegerToFloating => "exact_integer_to_floating",
        }
    }
}
#[derive(Clone, Debug)]
pub struct Operation {
    pub name: String,
    pub symbol: &'static str,
    pub implementation: String,
    pub promotion: Promotion,
    pub form: Form,
    pub left: Operand,
    pub right: Option<Operand>,
    pub left_conversion: Adaptation,
    pub right_conversion: Option<Adaptation>,
    pub execution: String,
    pub result: String,
}
#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum ValueKind {
    Boolean,
    Null,
    Floating,
}
impl ValueKind {
    fn name(self) -> &'static str {
        match self {
            Self::Boolean => "boolean",
            Self::Null => "null",
            Self::Floating => "floating",
        }
    }
    fn representation(self) -> &'static str {
        match self {
            Self::Boolean => "native_bool",
            Self::Null => "empty",
            Self::Floating => "double",
        }
    }
}
#[derive(Clone, Debug)]
pub struct SimpleValue {
    pub name: String,
    pub kind: ValueKind,
    pub operations: Vec<String>,
}
#[derive(Clone, Debug)]
pub struct ScalarPlan {
    pub types: Vec<IntegerType>,
    pub native_inputs: Vec<NativeInput>,
    pub values: Vec<SimpleValue>,
    pub conversions: Vec<Conversion>,
    pub operations: Vec<Operation>,
    pub source_types: BTreeMap<String, String>,
    pub aliases: BTreeMap<String, String>,
    pub short_aliases: Vec<String>,
    pub headers: BTreeMap<String, String>,
}
#[derive(Clone, Debug)]
struct Binding {
    symbol: &'static str,
    implementation: String,
    promotion: Promotion,
    form: Form,
    comparison: bool,
    native: bool,
}
// Runtime binding capabilities, not configurable promotion/cast policies. Metadata
// selects enabled bindings and publication selects concrete signatures.
fn capability(name: &str) -> Result<Binding> {
    let (base, form) = if let Some(base) = name.strip_suffix("_assign") {
        (base, Form::Compound)
    } else if let Some(base) = name.strip_prefix("prefix_") {
        (base, Form::Prefix)
    } else if let Some(base) = name.strip_prefix("postfix_") {
        (base, Form::Postfix)
    } else {
        (name, Form::Binary)
    };
    let (symbol, promotion, unary) = match base {
        "add" => ("+", Promotion::DomainMaximum, false),
        "subtract" => ("-", Promotion::DomainMaximum, false),
        "multiply" => ("*", Promotion::DomainMaximum, false),
        "divide" => ("/", Promotion::DomainMaximum, false),
        "remainder" => ("%", Promotion::DomainMaximum, false),
        "bit_and" => ("&", Promotion::DomainMaximum, false),
        "bit_or" => ("|", Promotion::DomainMaximum, false),
        "bit_xor" => ("^", Promotion::DomainMaximum, false),
        "equal" => ("==", Promotion::ExactComparison, false),
        "not_equal" => ("!=", Promotion::ExactComparison, false),
        "less" => ("<", Promotion::ExactComparison, false),
        "less_equal" => ("<=", Promotion::ExactComparison, false),
        "greater" => (">", Promotion::ExactComparison, false),
        "greater_equal" => (">=", Promotion::ExactComparison, false),
        "positive" => ("+", Promotion::Preserve, true),
        "negate" => ("-", Promotion::Preserve, true),
        "bit_not" => ("~", Promotion::Preserve, true),
        "shift_left" => ("<<", Promotion::CheckedShift, false),
        "shift_right" => (">>", Promotion::CheckedShift, false),
        "increment" => ("++", Promotion::Preserve, true),
        "decrement" => ("--", Promotion::Preserve, true),
        _ => return Err(Error("Unknown integer operation binding".into())),
    };
    let increment = matches!(base, "increment" | "decrement");
    ensure(
        match form {
            Form::Prefix | Form::Postfix => increment,
            Form::Compound => matches!(
                promotion,
                Promotion::DomainMaximum | Promotion::CheckedShift
            ),
            _ => !increment,
        },
        "Unknown integer operation binding",
    )?;
    let symbol = if form == Form::Compound {
        match base {
            "add" => "+=",
            "subtract" => "-=",
            "multiply" => "*=",
            "divide" => "/=",
            "remainder" => "%=",
            "bit_and" => "&=",
            "bit_or" => "|=",
            "bit_xor" => "^=",
            "shift_left" => "<<=",
            "shift_right" => ">>=",
            _ => unreachable!(),
        }
    } else {
        symbol
    };
    Ok(Binding {
        symbol,
        implementation: format!("integer_{base}"),
        promotion,
        form: if unary && form == Form::Binary {
            Form::Unary
        } else {
            form
        },
        comparison: promotion == Promotion::ExactComparison,
        native: false,
    })
}
fn bindings(value: &Value, floating: bool) -> Result<BTreeMap<String, Binding>> {
    let mut result = BTreeMap::new();
    for (name, row) in value.object()? {
        let mut binding = capability(name)?;
        let original_promotion = binding.promotion;
        if floating {
            ensure(
                !matches!(original_promotion, Promotion::CheckedShift)
                    && !name.starts_with("bit_")
                    && !name.starts_with("remainder"),
                "Unsupported floating operation",
            )?;
            binding.promotion = Promotion::Floating;
            binding.implementation = binding.implementation.replacen("integer_", "floating_", 1);
        }
        row.fields(&[
            "public_operator",
            "promotion",
            "implementation",
            "result",
            "native_arguments",
            "form",
        ])?;
        row.literal("public_operator", binding.symbol)?;
        row.literal("promotion", binding.promotion.name())?;
        row.literal("implementation", &binding.implementation)?;
        row.literal("form", binding.form.name())?;
        let result_rule = match binding.form {
            Form::Compound | Form::Prefix => "left_reference",
            Form::Postfix => "old_value",
            Form::Unary => "operand_type",
            _ => {
                if binding.comparison {
                    "boolean"
                } else if original_promotion == Promotion::CheckedShift {
                    "left_type"
                } else {
                    "execution_type"
                }
            }
        };
        row.literal("result", result_rule)?;
        if let Some(native) = row.object()?.get("native_arguments") {
            ensure(
                original_promotion == Promotion::DomainMaximum,
                "Native adapters require arithmetic normalization",
            )?;
            native.fields(&["policy", "inputs"])?;
            native.literal("policy", "type_range")?;
            native.literal("inputs", "native_integer_inputs")?;
            binding.native = true;
        }
        result.insert(name.clone(), binding);
    }
    ensure(
        floating || !result.is_empty(),
        "Expected integer operations",
    )?;
    Ok(result)
}
pub(crate) fn names(value: &Value, message: &str) -> Result<BTreeSet<String>> {
    let mut seen = BTreeSet::new();
    for name in value.array()? {
        ensure(seen.insert(name.text()?.into()), message)?;
    }
    Ok(seen)
}
fn headers(publication: &Value) -> Result<BTreeMap<String, String>> {
    let headers = publication.at("headers")?;
    let fields = [
        "types",
        "values",
        "operators",
        "short_names",
        "native_bridge",
    ];
    headers.fields(&fields)?;
    let mut paths = BTreeSet::new();
    let mut result = BTreeMap::new();
    for field in fields {
        let path = crate::public_header(headers.at(field)?)?;
        ensure(paths.insert(path.clone()), "Conflicting public headers")?;
        result.insert(field.into(), path);
    }
    Ok(result)
}
struct DomainMaximum {
    signed: String,
    unsigned: String,
    mixed: String,
}
impl DomainMaximum {
    fn parse(metadata: &Value, types: &BTreeMap<String, IntegerType>) -> Result<Self> {
        let policies = metadata.at("promotion_policies")?;
        policies.fields(&["integer_domain_maximum"])?;
        let policy = policies.at("integer_domain_maximum")?;
        policy.fields(&[
            "same_type",
            "different_signed",
            "different_unsigned",
            "signed_and_unsigned_below_64_bits",
            "signed_and_uint64",
        ])?;
        policy.literal("same_type", "preserve")?;
        policy.literal("signed_and_uint64", "reject")?;
        let get = |field: &str, signed: bool| -> Result<String> {
            let name = policy.at(field)?.text()?;
            ensure(
                types
                    .get(name)
                    .is_some_and(|t| t.bits == 64 && t.signed == signed),
                "Domain maximum must be a registered 64-bit type with matching signedness",
            )?;
            Ok(name.into())
        };
        Ok(Self {
            signed: get("different_signed", true)?,
            unsigned: get("different_unsigned", false)?,
            mixed: get("signed_and_unsigned_below_64_bits", true)?,
        })
    }
    fn resolve(&self, left: &IntegerType, right: &IntegerType) -> Result<String> {
        if left.name == right.name {
            return Ok(left.name.clone());
        }
        if left.signed == right.signed {
            return Ok(if left.signed {
                &self.signed
            } else {
                &self.unsigned
            }
            .clone());
        }
        ensure(
            if left.signed { right.bits } else { left.bits } < 64,
            "Signed and uint64 arithmetic is not admitted",
        )?;
        Ok(self.mixed.clone())
    }
}

pub fn prepare(metadata: &Value, publication: &Value, facts: &TargetFacts) -> Result<ScalarPlan> {
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
    let registry = integer::registry(metadata, facts)?;
    let native_inputs = integer::native_inputs(metadata, facts)?;
    let policy = DomainMaximum::parse(metadata, &registry)?;
    let integer_ops = bindings(metadata.at("operations")?, false)?;
    let mut value_types = BTreeMap::new();
    for (name, row) in metadata.at("values")?.object()? {
        row.fields(&[
            "kind",
            "representation",
            "construction",
            "condition",
            "operations",
        ])?;
        let kind = match (name.as_str(), row.at("kind")?.text()?) {
            ("bool_t", "boolean") => ValueKind::Boolean,
            ("null_t", "null") => ValueKind::Null,
            _ => return Err(Error("Unsupported simple value identity".into())),
        };
        row.literal("representation", kind.representation())?;
        row.literal(
            "construction",
            if kind == ValueKind::Boolean {
                "explicit"
            } else {
                "default"
            },
        )?;
        if kind == ValueKind::Boolean {
            row.literal("condition", "explicit_native_bool")?;
        } else {
            ensure(
                !row.object()?.contains_key("condition"),
                "Null has no condition conversion",
            )?;
        }
        let operations = names(row.at("operations")?, "Duplicate simple value operation")?;
        for op in &operations {
            ensure(
                matches!(op.as_str(), "equal" | "not_equal")
                    || (kind == ValueKind::Boolean && op == "not"),
                "Unsupported simple value operation",
            )?;
        }
        value_types.insert(
            name.clone(),
            SimpleValue {
                name: name.clone(),
                kind,
                operations: operations.into_iter().collect(),
            },
        );
    }
    let floating = metadata.at("floating")?;
    floating.fields(&[
        "type",
        "representation",
        "native_inputs",
        "integer_inputs",
        "mixed_integer",
        "division_by_zero",
        "operations",
    ])?;
    for (field, expected) in [
        ("type", "float64"),
        ("representation", "ieee_binary64"),
        ("integer_inputs", "native_integer_inputs"),
        ("mixed_integer", "exact_type_range"),
        ("division_by_zero", "throw"),
    ] {
        floating.literal(field, expected)?;
    }
    // TargetFacts construction already verifies IEEE binary64; never infer it from Rust's host.
    value_types.insert(
        "float64".into(),
        SimpleValue {
            name: "float64".into(),
            kind: ValueKind::Floating,
            operations: vec![],
        },
    );
    let floating_ops = bindings(floating.at("operations")?, true)?;
    let selected_values = names(publication.at("values")?, "Duplicate published value type")?;
    let mut values = vec![];
    for name in &selected_values {
        values.push(
            value_types
                .get(name)
                .ok_or_else(|| Error("Unknown published value type".into()))?
                .clone(),
        );
    }
    let boolean = selected_values.contains("bool_t");
    let has_float = selected_values.contains("float64");
    ensure(
        boolean || values.iter().all(|v| v.operations.is_empty()),
        "Value operations require published boolean",
    )?;
    let selected = names(publication.at("types")?, "Duplicate published type")?;
    let mut types = BTreeMap::new();
    for name in &selected {
        types.insert(
            name.clone(),
            registry
                .get(name)
                .ok_or_else(|| Error("Unknown published integer type".into()))?
                .clone(),
        );
    }
    ensure(
        !types.is_empty() || !values.is_empty(),
        "Empty runtime publication",
    )?;
    let source_metadata = metadata.at("source_types")?;
    source_metadata.fields(&["int", "bool", "null", "float"])?;
    let mut source_types: BTreeMap<String, String> = BTreeMap::new();
    for (source, target) in source_metadata.object()? {
        let name = target.text()?;
        let valid = if source == "int" {
            registry.get(name).is_some_and(|t| t.signed)
        } else {
            let expected = match source.as_str() {
                "bool" => ValueKind::Boolean,
                "float" => ValueKind::Floating,
                _ => ValueKind::Null,
            };
            value_types.get(name).is_some_and(|t| t.kind == expected)
        };
        ensure(valid, "Source identity disagrees with kind")?;
        if selected.contains(name) || selected_values.contains(name) {
            source_types.insert(source.clone(), name.into());
        }
    }
    let alias_metadata = metadata.at("aliases")?;
    alias_metadata.fields(&["int_t", "float_t"])?;
    let mut aliases = BTreeMap::new();
    for (alias, source) in alias_metadata.object()? {
        let source = source.text()?;
        ensure(
            source == if alias == "int_t" { "int" } else { "float" },
            "Alias must follow the configured int/float default",
        )?;
        source_metadata.at(source)?;
        if let Some(target) = source_types.get(source) {
            aliases.insert(alias.clone(), target.clone());
        }
    }
    let mut short_aliases = vec![];
    for alias in names(publication.at("short_aliases")?, "Duplicate short alias")? {
        alias_metadata.at(&alias)?;
        if aliases.contains_key(&alias) {
            short_aliases.push(alias);
        }
    }
    let mut conversions: Vec<_> = integer::conversions(&types, &native_inputs)
        .into_iter()
        .map(|c| Conversion {
            target: c.target,
            source: c.source,
            native_source: c.native_source,
            lossless: c.lossless,
            implementation: Cast::CheckedInteger,
            can_fail: !c.lossless,
        })
        .collect();
    let mut add =
        |target: &str, source: &str, native_source, lossless, implementation, can_fail| {
            conversions.push(Conversion {
                target: target.into(),
                source: source.into(),
                native_source,
                lossless,
                implementation,
                can_fail,
            })
        };
    let float_inputs = names(
        floating.at("native_inputs")?,
        "Duplicate native floating input",
    )?;
    for input in &float_inputs {
        ensure(
            matches!(input.as_str(), "float" | "double"),
            "Unsupported native floating constructor input",
        )?;
        if has_float {
            add("float64", input, true, true, Cast::Numeric, false);
        }
    }
    ensure(
        float_inputs.contains("double"),
        "float64 requires its native double constructor",
    )?;
    if has_float {
        for input in &native_inputs {
            add(
                "float64",
                &input.cpp,
                true,
                input.digits <= 53,
                Cast::Numeric,
                false,
            );
        }
    }
    for cast in names(metadata.at("scalar_casts")?, "Duplicate scalar cast policy")? {
        match cast.as_str() {
            "integer_to_floating_rounding" | "floating_to_integer_truncate_checked" => {
                if has_float {
                    for t in types.values() {
                        if cast == "integer_to_floating_rounding" {
                            add(
                                "float64",
                                &t.name,
                                false,
                                t.digits() <= 53,
                                Cast::Numeric,
                                false,
                            );
                        } else {
                            add(
                                &t.name,
                                "float64",
                                false,
                                false,
                                Cast::FloatingInteger,
                                true,
                            );
                        }
                    }
                }
            }
            "numeric_to_boolean_nonzero" | "boolean_to_numeric_01" => {
                if boolean {
                    for name in types
                        .keys()
                        .map(String::as_str)
                        .chain(has_float.then_some("float64"))
                    {
                        if cast == "numeric_to_boolean_nonzero" {
                            add("bool_t", name, false, false, Cast::Truth, false);
                        } else {
                            add(name, "bool_t", false, true, Cast::Numeric, false);
                        }
                    }
                }
            }
            _ => return Err(Error("Unsupported scalar cast policy".into())),
        }
    }
    conversions.sort_by(|a, b| (&a.target, &a.source).cmp(&(&b.target, &b.source)));
    let resolver = Resolver {
        registry: &registry,
        selected: &selected,
        native_inputs: &native_inputs,
        policy: &policy,
        boolean,
        has_float,
        conversions: &conversions,
    };
    let mut operations = vec![];
    let mut pairs = BTreeSet::new();
    for request in publication.at("operators")?.array()? {
        request.fields(&["operation", "operands", "both_orders"])?;
        let name = request.at("operation")?.text()?;
        let args = request.at("operands")?.array()?;
        let uses_float = args
            .iter()
            .any(|arg| matches!(arg,Value::Text(name) if name=="float64"));
        let binding = if uses_float {
            &floating_ops
        } else {
            &integer_ops
        }
        .get(name)
        .ok_or_else(|| Error("Unregistered numeric operation".into()))?;
        ensure(
            args.len() == if binding.form.binary() { 2 } else { 1 },
            "Operator operand count disagrees with form",
        )?;
        let left = resolver.operand(&args[0], binding)?;
        let right = if binding.form.binary() {
            Some(resolver.operand(&args[1], binding)?)
        } else {
            None
        };
        let mut resolve = |left: Operand, right: Option<Operand>| -> Result<()> {
            ensure(
                pairs.insert((
                    name.to_owned(),
                    left.cpp.clone(),
                    right.as_ref().map_or("", |r| &r.cpp).to_owned(),
                )),
                "Duplicate operator request",
            )?;
            operations.push(resolver.resolve(name, binding, left, right)?);
            Ok(())
        };
        resolve(left.clone(), right.clone())?;
        if let Some(both) = request.object()?.get("both_orders")
            && both.boolean()?
        {
            ensure(
                binding.form == Form::Binary,
                "Both orders applies only to binary value operators",
            )?;
            if let Some(right) = right
                && left.cpp != right.cpp
            {
                resolve(right, Some(left))?;
            }
        }
    }
    for op in &operations {
        if op.left.native || op.right.as_ref().is_some_and(|r| r.native) {
            let left = if op.form == Form::Compound {
                &op.left.identity
            } else {
                &op.execution
            };
            ensure(
                pairs.contains(&(op.name.clone(), left.clone(), op.execution.clone())),
                "Native bridge requires published same-type execution operator",
            )?;
        }
    }
    operations.sort_by(|a, b| {
        (&a.name, &a.left.cpp, a.right.as_ref().map(|r| &r.cpp)).cmp(&(
            &b.name,
            &b.left.cpp,
            b.right.as_ref().map(|r| &r.cpp),
        ))
    });
    Ok(ScalarPlan {
        types: types.into_values().collect(),
        native_inputs,
        values,
        conversions,
        operations,
        source_types,
        aliases,
        short_aliases,
        headers: headers(publication)?,
    })
}

struct Resolver<'a> {
    registry: &'a BTreeMap<String, IntegerType>,
    selected: &'a BTreeSet<String>,
    native_inputs: &'a [NativeInput],
    policy: &'a DomainMaximum,
    boolean: bool,
    has_float: bool,
    conversions: &'a [Conversion],
}
impl Resolver<'_> {
    fn operand(&self, input: &Value, binding: &Binding) -> Result<Operand> {
        if let Value::Text(name) = input {
            ensure(
                self.selected.contains(name) || (self.has_float && name == "float64"),
                "Operator requires published operand types",
            )?;
            return Ok(Operand {
                cpp: name.clone(),
                identity: name.clone(),
                native: false,
            });
        }
        input.fields(&["native"])?;
        ensure(binding.native, "Native arithmetic is not enabled")?;
        let cpp = input.at("native")?.text()?;
        let native = self
            .native_inputs
            .iter()
            .find(|n| n.cpp == cpp)
            .ok_or_else(|| Error("Unregistered native arithmetic input".into()))?;
        let identity = self
            .registry
            .values()
            .find(|t| t.signed == native.signed && t.digits() == native.digits)
            .ok_or_else(|| Error("Native range has no exact integer identity".into()))?;
        Ok(Operand {
            cpp: cpp.into(),
            identity: identity.name.clone(),
            native: true,
        })
    }
    fn has_cast(&self, target: &str, source: &Operand) -> bool {
        self.conversions.iter().any(|c| {
            c.target == target && c.source == source.cpp && c.native_source == source.native
        })
    }
    fn resolve(
        &self,
        name: &str,
        b: &Binding,
        left: Operand,
        right: Option<Operand>,
    ) -> Result<Operation> {
        ensure(
            !(left.native && right.as_ref().is_some_and(|r| r.native)),
            "Native-only operators are not runtime overloads",
        )?;
        ensure(
            b.form != Form::Compound || !left.native,
            "Compound assignment requires a public left operand",
        )?;
        let (execution, result) = match b.promotion {
            Promotion::Floating => {
                ensure(
                    !b.comparison || self.boolean,
                    "Floating comparisons require published boolean",
                )?;
                for arg in std::iter::once(&left).chain(right.iter()) {
                    if arg.identity != "float64" {
                        ensure(
                            self.registry
                                .get(&arg.identity)
                                .is_some_and(|t| t.digits() <= 53),
                            "Wide integer/floating operations require an explicit cast",
                        )?;
                        ensure(
                            self.has_cast("float64", arg),
                            "Floating normalization requires an admitted cast",
                        )?;
                    }
                }
                if b.form == Form::Compound && left.identity != "float64" {
                    ensure(
                        self.conversions.iter().any(|c| {
                            c.target == left.identity && c.source == "float64" && !c.native_source
                        }),
                        "Floating write-back requires an admitted cast",
                    )?;
                }
                (
                    "float64".into(),
                    if b.comparison {
                        "bool_t".into()
                    } else if b.form == Form::Compound {
                        left.identity.clone()
                    } else {
                        "float64".into()
                    },
                )
            }
            Promotion::Preserve | Promotion::CheckedShift => {
                ensure(
                    !left.native && !right.as_ref().is_some_and(|r| r.native),
                    "Unary and shift operations require runtime operands",
                )?;
                (left.identity.clone(), left.identity.clone())
            }
            Promotion::ExactComparison => {
                ensure(
                    self.boolean,
                    "Integer comparisons require published boolean",
                )?;
                ("direct_values".into(), "bool_t".into())
            }
            Promotion::DomainMaximum => {
                let right = right
                    .as_ref()
                    .ok_or_else(|| Error("Missing binary operand".into()))?;
                let l = &self.registry[&left.identity];
                let r = &self.registry[&right.identity];
                let execution = self.policy.resolve(l, r)?;
                ensure(
                    self.selected.contains(&execution),
                    "Operator requires published execution type",
                )?;
                let target = &self.registry[&execution];
                ensure(
                    target.fits(l.signed, l.digits()) && target.fits(r.signed, r.digits()),
                    "Lossy implicit promotion",
                )?;
                let result = if b.form == Form::Compound {
                    left.identity.clone()
                } else {
                    execution.clone()
                };
                (execution, result)
            }
        };
        let adapt = |arg: &Operand| {
            if b.promotion == Promotion::Floating && arg.identity != execution {
                Adaptation::ExactIntegerToFloating
            } else if matches!(
                b.promotion,
                Promotion::ExactComparison | Promotion::Preserve | Promotion::CheckedShift
            ) {
                Adaptation::Identity
            } else if arg.native {
                Adaptation::LosslessNativeInteger
            } else if arg.identity == execution {
                Adaptation::Identity
            } else {
                Adaptation::LosslessInteger
            }
        };
        let left_conversion = adapt(&left);
        let right_conversion = right.as_ref().map(adapt);
        Ok(Operation {
            name: name.into(),
            symbol: b.symbol,
            implementation: b.implementation.clone(),
            promotion: b.promotion,
            form: b.form,
            left,
            right,
            left_conversion,
            right_conversion,
            execution,
            result,
        })
    }
}
impl ScalarPlan {
    /// Full C++ prepared document for scalar-only publications, with no field normalization.
    pub fn describe(&self) -> serde_json::Value {
        use serde_json::json;
        let operations:Vec<_>=self.operations.iter().map(|op|{
            let mut row=json!({"operation":op.name,"symbol":op.symbol,"implementation":op.implementation,"form":op.form.name(),"promotion":op.promotion.name(),"execution":op.execution,"result":op.result,"write_back":if op.form==Form::Compound{"checked_after_calculation"}else{"none"},"result_category":op.form.result_category()});
            for (side,arg,adaptation) in [("left",Some(&op.left),Some(op.left_conversion)),("right",op.right.as_ref(),op.right_conversion)] {
                if let (Some(arg),Some(adaptation))=(arg,adaptation) {
                    row[side]=json!({"cpp":arg.cpp,"identity":arg.identity,"native":arg.native});
                    row[format!("{side}_conversion")]=json!(adaptation.name());
                }
            }
            row
        }).collect();
        json!({"kind":"prepared_runtime_api","vectors":[],"carriers":[],
            "types":self.types.iter().map(|t|json!({"name":t.name,"representation":t.representation,"signed":t.signed,"bits":t.bits})).collect::<Vec<_>>(),
            "native_inputs":self.native_inputs.iter().map(|n|json!({"cpp":n.cpp,"signed":n.signed,"digits":n.digits})).collect::<Vec<_>>(),
            "values":self.values.iter().map(|v|json!({"name":v.name,"kind":v.kind.name(),"representation":v.kind.representation(),"operations":v.operations})).collect::<Vec<_>>(),
            "conversions":self.conversions.iter().map(|c|json!({"target":c.target,"source":c.source,"native_source":c.native_source,"lossless":c.lossless,"implementation":c.implementation.name(),"can_fail":c.can_fail,"source_category":if matches!(c.implementation,Cast::ParseDecimal|Cast::ParseBooleanText){"const_reference"}else{"value"}})).collect::<Vec<_>>(),
            "operations":operations,"source_types":self.source_types,"aliases":self.aliases,"short_aliases":self.short_aliases,"headers":self.headers
        })
    }
}
