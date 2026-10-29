use scpp_api_tooling::{
    integer, json,
    target::{TargetContext, TargetFacts},
};
use serde_json::{Value, json as object};

fn context() -> Value {
    object!({"schema":1,"context_id":"a".repeat(64),"configuration":{
        "target":"x86_64-pc-linux-gnu","compiler":"/fixture/clang++","compiler_version":"test fixture",
        "compiler_sha256":"b".repeat(64),"flags":["-std=c++23"],"probe_sha256":"c".repeat(64),
        "preprocessed_sha256":"d".repeat(64),"driver_sha256":"e".repeat(64)}})
}
fn facts() -> Value {
    let mut native = serde_json::Map::new();
    for (name, bits, signed) in [
        ("int", 32, true),
        ("unsigned int", 32, false),
        ("long", 64, true),
        ("unsigned long", 64, false),
        ("long long", 64, true),
        ("unsigned long long", 64, false),
    ] {
        native.insert(name.into(), object!({"signed":signed,"digits":bits-u32::from(signed),"size":bits/8,"alignment":bits/8}));
    }
    let mut fixed = serde_json::Map::new();
    for bits in [8, 16, 32, 64] {
        for signed in [true, false] {
            fixed.insert(format!("{}{bits}",if signed {"int"} else {"uint"}),object!({"signed":signed,"digits":bits-u32::from(signed),"size":bits/8,"alignment":bits/8}));
        }
    }
    object!({"schema":1,"context_id":"a".repeat(64),"char_bits":8,"pointer_bytes":8,"standard_library":"libc++:fixture",
        "native_integers":native,"fixed_integers":fixed,
        "double":{"iec559":true,"radix":2,"digits":53,"max_exponent":1024,"size":8,"alignment":8},
        "has_int128":true,"ownership":{"shared":{"size":16,"alignment":8},"weak":{"size":16,"alignment":8},"unique":{"size":8,"alignment":8}}})
}
fn metadata() -> Value {
    object!({"version":1,"types":{"int8":{"signed":true,"bits":8},"int64":{"signed":true,"bits":64},"uint64":{"signed":false,"bits":64}},
        "representation":{"binding":"fixed_width_integer","default_initialization":"zero"},
        "construction":{"public_integer_inputs":"checked","native_integer_inputs":["int","unsigned long long"],"native_conversion":"checked","explicit":true}})
}
fn publication() -> Value {
    object!({"version":1,"namespace":"scpp","types":["uint64","int8","int64"],"operators":[],"values":[],"short_aliases":[],
        "headers":{"types":"scpp/integers.hpp","values":"scpp/values.hpp","operators":"scpp/numeric.hpp","short_names":"scpp/short_names.hpp","native_bridge":"scpp/native_bridge.hpp"}})
}
fn bytes(value: &Value) -> Vec<u8> {
    serde_json::to_vec(value).unwrap()
}
fn target() -> TargetFacts {
    TargetFacts::parse(
        &bytes(&facts()),
        &TargetContext::parse(&bytes(&context())).unwrap(),
    )
    .unwrap()
}

#[test]
fn json_preserves_numbers_and_decoded_strings() {
    let value = json::parse(
        br#"{"numbers":[18446744073709551615,-0,1.2300e+99,1e9999],"text":"\u0000\uD83D\uDE00"}"#,
        "fixture",
    )
    .unwrap();
    let items = value.at("numbers").unwrap().array().unwrap();
    for (item, expected) in items
        .iter()
        .zip(["18446744073709551615", "-0", "1.2300e+99", "1e9999"])
    {
        assert_eq!(item, &json::Value::Number(expected.into()));
    }
    assert_eq!(value.at("text").unwrap().text().unwrap(), "\0😀");
    assert!(json::parse(b"1.0", "fixture").unwrap().unsigned().is_err());
    assert!(json::parse(b"1e0", "fixture").unwrap().unsigned().is_err());
}
#[test]
fn json_rejects_duplicate_keys_utf8_escapes_and_syntax() {
    for input in [
        br#"{"a":1,"\u0061":2}"#.as_slice(),
        br#"{"x":{"a":1,"a":2}}"#,
        br#""\ud800""#,
        b"[] null",
        b"[1,]",
        b"01",
        b"{",
        b"\"\xc0\xaf\"",
    ] {
        let error = json::parse(input, "fixture.json").unwrap_err().to_string();
        assert!(error.contains("fixture.json: byte"), "{error}");
    }
    assert!(
        json::parse(br#"{"x":{"a":1,"a":2}}"#, "fixture")
            .unwrap_err()
            .0
            .contains("duplicate object key")
    );
}
#[test]
fn json_depth_is_bounded_at_128_containers() {
    let accepted = format!("{}null{}", "[".repeat(128), "]".repeat(128));
    assert!(json::parse(accepted.as_bytes(), "fixture").is_ok());
    assert!(json::parse(format!("[{accepted}]").as_bytes(), "fixture").is_err());
}
#[test]
fn target_facts_expose_measured_ranges_and_layouts() {
    let target = target();
    assert_eq!(target.native_integers()["long"].digits, 63);
    assert_eq!(target.fixed_integers()["uint8"].digits, 8);
    assert_eq!(target.ownership()["shared"].size, 16);
    assert!(target.has_int128());
}
#[test]
fn reject_stale_malformed_and_incompatible_native_facts() {
    let expected = TargetContext::parse(&bytes(&context())).unwrap();
    for (path, value) in [
        (vec!["schema"], object!(2)),
        (vec!["context_id"], object!("f".repeat(64))),
        (vec!["pointer_bytes"], object!(4)),
        (vec!["double", "digits"], object!(24)),
        (vec!["ownership", "shared", "alignment"], object!(0)),
        (vec!["ownership", "shared", "alignment"], object!(3)),
        (vec!["ownership", "unique", "size"], object!(u64::MAX)),
        (vec!["fixed_integers", "int8", "digits"], object!(8)),
        (vec!["native_integers", "int", "signed"], object!(false)),
        (vec!["surprise"], object!(true)),
    ] {
        let mut invalid = facts();
        let mut node = &mut invalid;
        for key in &path[..path.len() - 1] {
            node = &mut node[*key];
        }
        node[path[path.len() - 1]] = value;
        assert!(
            TargetFacts::parse(&bytes(&invalid), &expected).is_err(),
            "{path:?}"
        );
    }
    let mut missing = facts();
    missing.as_object_mut().unwrap().remove("ownership");
    assert!(TargetFacts::parse(&bytes(&missing), &expected).is_err());
    let mut cross = context();
    cross["configuration"]["target"] = object!("aarch64-linux-gnu");
    assert!(TargetContext::parse(&bytes(&cross)).is_err());
}
#[test]
fn preparation_resolves_typed_checked_construction() {
    let plan = integer::prepare(
        &json::parse(&bytes(&metadata()), "m").unwrap(),
        &json::parse(&bytes(&publication()), "p").unwrap(),
        &target(),
    )
    .unwrap();
    assert_eq!(
        plan.types
            .iter()
            .map(|t| t.name.as_str())
            .collect::<Vec<_>>(),
        ["int64", "int8", "uint64"]
    );
    assert_eq!(plan.conversions.len(), 12);
    let find = |to, from| {
        plan.conversions
            .iter()
            .find(|c| c.target == to && c.source == from)
            .unwrap()
    };
    assert!(find("int64", "int").lossless);
    assert!(!find("int8", "int").lossless);
    assert!(!find("uint64", "int64").lossless);
    assert!(!find("int64", "uint64").lossless);
    assert!(find("uint64", "unsigned long long").lossless);
    assert_eq!(plan.describe()["kind"], "prepared_integer_construction");
}
#[test]
fn partial_preparation_rejects_unsupported_publications_and_bad_inputs() {
    let m = json::parse(&bytes(&metadata()), "m").unwrap();
    for (field, value) in [
        ("types", object!(["int128"])),
        ("types", object!(["int8", "int8"])),
        ("version", object!(1.0)),
        (
            "operators",
            object!([{"operation":"add","operands":["int8","int8"]}]),
        ),
        ("values", object!(["bool_t"])),
        ("vectors", object!({})),
        ("short_aliases", object!(["int_t"])),
    ] {
        let mut p = publication();
        p[field] = value;
        assert!(
            integer::prepare(&m, &json::parse(&bytes(&p), "p").unwrap(), &target()).is_err(),
            "{field}"
        );
    }
    let p = json::parse(&bytes(&publication()), "p").unwrap();
    let mut m = metadata();
    m["construction"]["explicit"] = object!(false);
    assert!(integer::prepare(&json::parse(&bytes(&m), "m").unwrap(), &p, &target()).is_err());
    m = metadata();
    m["types"]["int8"]["bits"] = object!(8.0);
    assert!(integer::prepare(&json::parse(&bytes(&m), "m").unwrap(), &p, &target()).is_err());
}

fn scalar_metadata() -> Value {
    let mut m = metadata();
    for bits in [8, 16, 32, 64] {
        for signed in [true, false] {
            m["types"][format!("{}{bits}", if signed { "int" } else { "uint" })] =
                object!({"signed":signed,"bits":bits});
        }
    }
    m["promotion_policies"] = object!({"integer_domain_maximum":{
        "same_type":"preserve","different_signed":"int64","different_unsigned":"uint64",
        "signed_and_unsigned_below_64_bits":"int64","signed_and_uint64":"reject"}});
    m["operations"] = object!({});
    for (name, symbol, implementation, promotion, form, result) in [
        (
            "add",
            "+",
            "integer_add",
            "integer_domain_maximum",
            "binary",
            "execution_type",
        ),
        (
            "add_assign",
            "+=",
            "integer_add",
            "integer_domain_maximum",
            "compound",
            "left_reference",
        ),
        (
            "less",
            "<",
            "integer_less",
            "exact_integer_comparison",
            "binary",
            "boolean",
        ),
        (
            "shift_left",
            "<<",
            "integer_shift_left",
            "left_with_checked_count",
            "binary",
            "left_type",
        ),
        (
            "negate",
            "-",
            "integer_negate",
            "preserve_operand",
            "unary",
            "operand_type",
        ),
    ] {
        m["operations"][name] = object!({"public_operator":symbol,"implementation":implementation,
            "promotion":promotion,"form":form,"result":result});
    }
    m["operations"]["add"]["native_arguments"] =
        object!({"policy":"type_range","inputs":"native_integer_inputs"});
    m["values"] = object!({
        "bool_t":{"kind":"boolean","representation":"native_bool","construction":"explicit","condition":"explicit_native_bool","operations":["equal","not_equal","not"]},
        "null_t":{"kind":"null","representation":"empty","construction":"default","operations":["equal","not_equal"]}});
    m["source_types"] = object!({"int":"int64","bool":"bool_t","float":"float64","null":"null_t"});
    m["aliases"] = object!({"int_t":"int","float_t":"float"});
    m["floating"] = object!({"type":"float64","representation":"ieee_binary64","native_inputs":["float","double"],
        "integer_inputs":"native_integer_inputs","mixed_integer":"exact_type_range","division_by_zero":"throw","operations":{}});
    for name in ["add", "add_assign", "less", "negate"] {
        let mut binding = m["operations"][name].clone();
        binding["promotion"] = object!("floating_exact_integer");
        binding["implementation"] = object!(
            binding["implementation"]
                .as_str()
                .unwrap()
                .replace("integer_", "floating_")
        );
        m["floating"]["operations"][name] = binding;
    }
    m["scalar_casts"] = object!([
        "integer_to_floating_rounding",
        "floating_to_integer_truncate_checked",
        "numeric_to_boolean_nonzero",
        "boolean_to_numeric_01"
    ]);
    m
}
fn scalar_publication() -> Value {
    let mut p = publication();
    p["types"] = object!([
        "int8", "uint8", "int16", "uint16", "int32", "uint32", "int64", "uint64"
    ]);
    p["values"] = object!(["bool_t", "null_t", "float64"]);
    p["short_aliases"] = object!(["int_t"]);
    p
}
fn scalar_plan(
    m: &Value,
    p: &Value,
) -> scpp_api_tooling::Result<scpp_api_tooling::scalar::ScalarPlan> {
    scpp_api_tooling::scalar::prepare(
        &json::parse(&bytes(m), "metadata")?,
        &json::parse(&bytes(p), "publication")?,
        &target(),
    )
}

#[test]
fn scalar_domain_matrix_and_direct_extreme_comparisons() {
    use scpp_api_tooling::scalar::{Adaptation, Promotion};
    let m = scalar_metadata();
    let mut p = scalar_publication();
    let mut admitted = 0;
    for (left, l) in m["types"].as_object().unwrap() {
        for (right, r) in m["types"].as_object().unwrap() {
            p["operators"] = object!([{"operation":"add","operands":[left,right]}]);
            if (left == "uint64" && r["signed"] == true)
                || (right == "uint64" && l["signed"] == true)
            {
                assert!(scalar_plan(&m, &p).unwrap_err().0.contains("not admitted"));
            } else {
                let plan = scalar_plan(&m, &p).unwrap();
                let expected = if left == right {
                    left.as_str()
                } else if l["signed"] == true || r["signed"] == true {
                    "int64"
                } else {
                    "uint64"
                };
                assert_eq!(plan.operations[0].execution, expected);
                admitted += 1;
            }
        }
    }
    assert_eq!(admitted, 56);
    p["operators"] =
        object!([{"operation":"less","operands":["int64","uint64"],"both_orders":true}]);
    let plan = scalar_plan(&m, &p).unwrap();
    assert_eq!(plan.operations.len(), 2);
    for op in plan.operations {
        assert_eq!(op.promotion, Promotion::ExactComparison);
        assert_eq!(op.execution, "direct_values");
        assert_eq!(op.result, "bool_t");
        assert_eq!(op.left_conversion, Adaptation::Identity);
    }
}

#[test]
fn scalar_mutation_shift_and_native_dependencies_are_resolved() {
    use scpp_api_tooling::scalar::{Adaptation, Form};
    let m = scalar_metadata();
    let mut p = scalar_publication();
    p["operators"] = object!([
        {"operation":"add_assign","operands":["int8","int32"]},
        {"operation":"shift_left","operands":["int8","uint64"]}]);
    let plan = scalar_plan(&m, &p).unwrap();
    assert_eq!(plan.operations[0].form, Form::Compound);
    assert_eq!(
        (&*plan.operations[0].execution, &*plan.operations[0].result),
        ("int64", "int8")
    );
    assert_eq!(
        plan.operations[1].right_conversion,
        Some(Adaptation::Identity)
    );
    assert_eq!(
        plan.describe()["operations"][0]["write_back"],
        "checked_after_calculation"
    );
    p["operators"] = object!([{"operation":"add","operands":["int8",{"native":"int"}]}]);
    assert!(scalar_plan(&m, &p).unwrap_err().0.contains("same-type"));
    p["operators"]
        .as_array_mut()
        .unwrap()
        .push(object!({"operation":"add","operands":["int64","int64"]}));
    let plan = scalar_plan(&m, &p).unwrap();
    let op = plan
        .operations
        .iter()
        .find(|op| op.right.as_ref().unwrap().native)
        .unwrap();
    assert_eq!(op.right.as_ref().unwrap().identity, "int32");
    assert_eq!(op.right_conversion, Some(Adaptation::LosslessNativeInteger));
}

#[test]
fn scalar_float_admission_uses_cast_policy_and_preserves_explicit_wide_casts() {
    use scpp_api_tooling::scalar::{Adaptation, Cast};
    let m = scalar_metadata();
    let mut p = scalar_publication();
    let plan = scalar_plan(&m, &p).unwrap();
    let wide = plan
        .conversions
        .iter()
        .find(|c| c.source == "int64" && c.target == "float64")
        .unwrap();
    assert!(!wide.lossless && !wide.can_fail);
    assert_eq!(wide.implementation, Cast::Numeric);
    for integer in ["int64", "uint64"] {
        p["operators"] = object!([{"operation":"add","operands":[integer,"float64"]}]);
        assert!(scalar_plan(&m, &p).unwrap_err().0.contains("explicit cast"));
    }
    p["operators"] = object!([{"operation":"add_assign","operands":["int32","float64"]}]);
    let plan = scalar_plan(&m, &p).unwrap();
    assert_eq!(
        plan.operations[0].left_conversion,
        Adaptation::ExactIntegerToFloating
    );
    assert_eq!(plan.operations[0].result, "int32");
    for (policy, message) in [
        ("integer_to_floating_rounding", "normalization"),
        ("floating_to_integer_truncate_checked", "write-back"),
    ] {
        let mut changed = m.clone();
        changed["scalar_casts"]
            .as_array_mut()
            .unwrap()
            .retain(|c| c != policy);
        assert!(scalar_plan(&changed, &p).unwrap_err().0.contains(message));
    }
}

#[test]
fn scalar_values_defaults_and_fail_closed_publication() {
    let mut m = scalar_metadata();
    let mut p = scalar_publication();
    m["source_types"]["int"] = object!("int32");
    let plan = scalar_plan(&m, &p).unwrap();
    assert_eq!(plan.aliases["int_t"], "int32");
    assert_eq!(plan.short_aliases, ["int_t"]);
    assert!(
        !plan
            .conversions
            .iter()
            .any(|c| c.source == "null_t" || c.target == "null_t")
    );
    p["types"] = object!([]);
    assert!(scalar_plan(&m, &p).unwrap().types.is_empty());
    p["values"] = object!(["null_t"]);
    assert!(
        scalar_plan(&m, &p)
            .unwrap_err()
            .0
            .contains("published boolean")
    );
    m["values"]["null_t"]["operations"] = object!([]);
    assert!(scalar_plan(&m, &p).is_ok());
    p["vectors"] = object!({});
    assert!(scalar_plan(&m, &p).unwrap_err().0.contains("Unknown field"));
}

fn string_metadata() -> Value {
    let mut m = scalar_metadata();
    m["construction"]["native_integer_inputs"]
        .as_array_mut()
        .unwrap()
        .push(object!("long long"));
    m["string"] = object!({"type":"string","source_type":"string","storage":"private_std_string","count_type":"int64",
        "construction":"explicit","null_pointer":"reject","text_casts":["decimal_integer","decimal_float","boolean_words"],
        "operations":["add","add_assign","equal","not_equal","less","less_equal","greater","greater_equal"]});
    m
}
fn string_publication() -> Value {
    let mut p = scalar_publication();
    p["string"] = object!({"header":"scpp/string.hpp","operations":["add","add_assign","equal"],"casts":["int64","float64","bool_t"]});
    p
}
fn runtime_plan(
    m: &Value,
    p: &Value,
) -> scpp_api_tooling::Result<scpp_api_tooling::runtime::RuntimePlan> {
    scpp_api_tooling::runtime::prepare(
        &json::parse(&bytes(m), "metadata")?,
        &json::parse(&bytes(p), "publication")?,
        &target(),
    )
}
#[test]
fn string_plan_resolves_casts_borrows_mutation_and_count() {
    use scpp_api_tooling::scalar::Cast;
    let plan = runtime_plan(&string_metadata(), &string_publication()).unwrap();
    assert_eq!(plan.scalars.source_types["string"], "string");
    assert_eq!(plan.string.as_ref().unwrap().count_type, "int64");
    let parse = plan
        .scalars
        .conversions
        .iter()
        .find(|c| c.source == "string" && c.target == "bool_t")
        .unwrap();
    assert_eq!(parse.implementation, Cast::ParseBooleanText);
    assert!(parse.can_fail && !parse.lossless);
    let out = plan.describe();
    assert_eq!(
        out["string"]["operations"][1]["result_category"],
        "left_reference"
    );
    assert_eq!(
        out["string"]["methods"][2]["result_category"],
        "borrowed_bytes_until_mutation_or_destruction"
    );
    let row = out["conversions"]
        .as_array()
        .unwrap()
        .iter()
        .find(|c| c["source"] == "string")
        .unwrap();
    assert_eq!(row["source_category"], "const_reference");
}
#[test]
fn string_demands_validate_missing_capabilities_and_dependencies() {
    let m = string_metadata();
    let mut p = string_publication();
    p["string"]["casts"] = object!(["null_t"]);
    assert!(runtime_plan(&m, &p).is_err());
    p = string_publication();
    p["string"]["header"] = p["headers"]["types"].clone();
    assert!(runtime_plan(&m, &p).unwrap_err().0.contains("Conflicting"));
    let mut m = m;
    m["string"]["operations"] = object!([]);
    assert!(runtime_plan(&m, &string_publication()).is_err());
    p = string_publication();
    p.as_object_mut().unwrap().remove("string");
    m["string"]["null_pointer"] = object!("empty");
    assert!(runtime_plan(&m, &p).is_err()); // validate even unrequested registered metadata
}
