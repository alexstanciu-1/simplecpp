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
