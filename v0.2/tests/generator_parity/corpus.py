"""Intentional migration fixtures, derived from reviewed v0.2 publication inputs.

No policy implementation: these are explicit requests and mutations of requests.
"""
import copy
import json
from dataclasses import dataclass
from pathlib import Path


@dataclass
class Case:
    name: str
    metadata: bytes
    publication: bytes
    rejection: str = ""


def encode(value):
    return json.dumps(value, ensure_ascii=False, separators=(",", ":")).encode()


def cases(root: Path):
    def load(name):
        return json.loads((root / name).read_bytes())

    metadata = load("runtime/metadata/runtime.json")
    default = load("tests/generator_parity/numeric_publication.json")
    compiler = load("tests/generator_parity/compiler_publication.json")
    ownership = load("tests/ownership/publication.json")
    result = []

    def add(name, publication, meta=metadata, rejection=""):
        result.append(Case(name, encode(meta), encode(publication), rejection))

    add("default", default)
    add("compiler", compiler)
    add("ownership", ownership)
    scalar = copy.deepcopy(compiler)
    scalar.update(types=["int32", "int64"], values=["bool_t", "null_t", "float64"], operators=[])
    add("scalar_minimal", scalar)
    values_only = copy.deepcopy(scalar)
    values_only.update(types=[], values=["bool_t", "null_t"])
    add("values_only", values_only)
    nullable = copy.deepcopy(scalar)
    nullable["carriers"] = {"header": "scpp/carriers.hpp", "specializations": [{"family": "nullable", "payload": "int64"}]}
    add("nullable_only", nullable)
    text_only = copy.deepcopy(scalar)
    text_only["string"] = {"header": "scpp/string.hpp", "operations": [], "casts": []}
    add("string_storage_only", text_only)
    mixed = copy.deepcopy(scalar)
    mixed["operators"] = [{"operation": "add", "operands": ["int32", "int64"]}]
    add("runtime_mixed_only", mixed)
    one_way = copy.deepcopy(scalar)
    one_way["operators"] = [
        {"operation": "add", "operands": ["int32", "int32"]},
        {"operation": "add", "operands": ["int32", {"native": "int"}]},
    ]
    add("native_one_way", one_way)
    alias = copy.deepcopy(metadata)
    alias["source_types"]["int"] = "int32"
    add("configured_alias", default, alias)
    explicit = copy.deepcopy(metadata)
    explicit["carriers"]["extraction"] = "explicit_checked_one_layer_value_copy"
    add("explicit_carriers", default, explicit)
    renamed = copy.deepcopy(default)
    for group in [renamed["headers"]]:
        for key, value in group.items():
            group[key] = value.replace("scpp/", "scpp/custom/")
    for key in ["string", "vectors", "carriers"]:
        renamed[key]["header"] = renamed[key]["header"].replace("scpp/", "scpp/custom/")
    add("custom_headers", renamed)

    vectors = copy.deepcopy(default)
    del vectors["carriers"]
    sources = {
        "int32": ["int64", "string"], "int64": ["int32", "string"],
        "string": ["int64", "float64", "bool_t"], "float64": ["int64"],
        "bool_t": ["string"], "null_t": [],
    }
    vectors["vectors"]["specializations"] = [
        {"element": element, "native_index": False, "native_value": "none",
         "operations": ["equal", "not_equal"], "casts_from": ["vector_" + source for source in casts]}
        for element, casts in sources.items()
    ]
    add("vector_casts", vectors)
    for family in ["value", "unique", "const"]:
        p = copy.deepcopy(ownership)
        p["ownership"]["records"] = p["ownership"]["records"][:1]
        record = p["ownership"]["records"][0]
        record["families"] = [] if family == "const" else [family]
        record["const_families"] = ["shared", "weak", "unique"] if family == "const" else []
        add("ownership_" + family + "_only", p)

    # Full type registry, all integer operation forms, mixed promotion and float adapters.
    # The existing integer_full_publication test covers the larger Cartesian matrix.
    full = copy.deepcopy(scalar)
    full["types"] = ["int8", "int16", "int32", "int64", "uint8", "uint16", "uint32", "uint64"]
    full["operators"] = []
    binary = "add subtract multiply divide remainder bit_and bit_or bit_xor shift_left shift_right equal not_equal less less_equal greater greater_equal add_assign subtract_assign multiply_assign divide_assign remainder_assign bit_and_assign bit_or_assign bit_xor_assign shift_left_assign shift_right_assign".split()
    unary = "positive negate bit_not prefix_increment postfix_increment prefix_decrement postfix_decrement".split()
    for t in full["types"]:
        full["operators"] += [{"operation": op, "operands": [t, t]} for op in binary]
        full["operators"] += [{"operation": op, "operands": [t]} for op in unary]
    for left, right in [("int8", "int16"), ("uint8", "uint16"), ("int32", "uint32")]:
        full["operators"].append({"operation": "add", "operands": [left, right], "both_orders": True})
    full["operators"] += [
        {"operation": "equal", "operands": ["int64", "uint64"], "both_orders": True},
        {"operation": "shift_left", "operands": ["int8", "uint64"]},
        {"operation": "add_assign", "operands": ["int8", "int64"]},
    ]
    float_binary = "add subtract multiply divide equal not_equal less less_equal greater greater_equal add_assign subtract_assign multiply_assign divide_assign".split()
    full["operators"] += [{"operation": op, "operands": ["float64", "float64"]} for op in float_binary]
    full["operators"] += [{"operation": op, "operands": ["float64"]} for op in unary if op != "bit_not"]
    for t in ["int8", "int16", "int32", "uint8", "uint16", "uint32"]:
        full["operators"].append({"operation": "add", "operands": [t, "float64"], "both_orders": True})
    add("numeric_forms", full)

    def reject(name, path, value, message, base=default, on_metadata=False):
        p, m = copy.deepcopy(base), copy.deepcopy(metadata)
        parent = m if on_metadata else p
        for key in path[:-1]:
            parent = parent[key]
        parent[path[-1]] = value
        add(name, p, m, message)

    reject("unknown_root", ["typo"], True, "unknown field typo")
    reject("version_decimal", ["version"], 1.0, "Unsupported metadata version")
    reject("version_string", ["version"], "1", "Unsupported metadata version")
    reject("namespace", ["namespace"], "other", "Unsupported namespace")
    reject("width_decimal", ["types", "int32", "bits"], 32.0, "Unsupported integer width", on_metadata=True)
    reject("implicit_integer", ["construction", "explicit"], False, "Integer constructors must be explicit", on_metadata=True)
    reject("bad_policy", ["promotion_policies", "integer_domain_maximum", "same_type"], "widen", "Unsupported same_type", on_metadata=True)
    reject("unknown_type", ["types"], ["int128"], "Unknown published integer type", base=scalar)
    reject("duplicate_type", ["types"], ["int32", "int32"], "Duplicate published type", base=scalar)
    reject("unsafe_header", ["headers", "types"], "../outside.hpp", "Invalid public header path")
    reject("header_collision", ["headers", "types"], "scpp/values.hpp", "Conflicting public headers")
    reject("unknown_operator", ["operators"], [{"operation": "magic", "operands": ["int64", "int64"]}], "Unregistered numeric operation", base=scalar)
    reject("duplicate_operator", ["operators"], [one_way["operators"][0]] * 2, "Duplicate operator request", base=scalar)
    reject("signed_uint64", ["operators"], [{"operation": "add", "operands": ["int64", "uint64"]}], "Signed and uint64 arithmetic is not admitted", base=compiler)
    reject("wide_float", ["operators"], [{"operation": "add", "operands": ["int64", "float64"]}], "explicit", base=scalar)
    reject("missing_native_execution", ["operators"], [{"operation": "add", "operands": ["int64", {"native": "int"}]}], "Native bridge requires published same-type execution operator", base=scalar)
    reject("unrequested_native", ["operators"], [{"operation": "add", "operands": ["int32", {"native": "short"}]}], "Unregistered native arithmetic input", base=scalar)
    reject("operand_count", ["operators"], [{"operation": "add", "operands": ["int32"]}], "Operator operand count", base=scalar)
    reject("text_null", ["string", "casts"], ["null_t"], "Text cast requires")
    reject("vector_unpublished", ["vectors", "specializations", 0, "element"], "uint8", "Vector requires supported published element type")
    reject("vector_identity_cast", ["vectors", "specializations", 0, "casts_from"], ["vector_int64"], "Vector identity uses copy construction")
    reject("carrier_nested", ["carriers", "specializations", 0, "payload"], "nullable_int64", "payload")
    reject("carrier_policy", ["carriers", "extraction"], "unchecked", "Unsupported carrier extraction policy", on_metadata=True)
    reject("ownership_weak", ["ownership", "records", 0, "families"], ["weak"], "weak requires matching shared", base=ownership)
    reject("ownership_const_value", ["ownership", "records", 0, "const_families"], ["value"], "invalid or duplicate family", base=ownership)
    reject("ownership_record_keyword", ["ownership", "records", 0, "name"], "class", "invalid or conflicting record name", base=ownership)
    reject("ownership_constructor", ["ownership", "records", 0, "constructors"], [["string"]], "constructor argument must be a published scalar", base=ownership)
    reject("ownership_throw_policy", ["ownership", "value", "special_members"], "always_noexcept", "unsupported special_members", base=ownership, on_metadata=True)

    raw = [
        ("json_duplicate_decoded_key", b'{"version":1,"\\u0076ersion":1}', "duplicate object key"),
        ("json_trailing", encode(scalar) + b' null', "trailing"),
        ("json_trailing_comma", b'{"types":[1,]}', "byte"),
        ("json_leading_zero", b'{"version":01}', "byte"),
        ("json_invalid_utf8", b'{"x":"\xc0\xaf"}', "UTF-8"),
        ("json_lone_surrogate", b'{"x":"\\ud800"}', "surrogate"),
        ("json_depth", b'[' * 129 + b'null' + b']' * 129, "nesting limit"),
        ("json_wrong_root", b'[]', "runtime publication: expected object"),
        ("json_huge_exponent", b'{"version":1e9999}', "Unsupported metadata version"),
    ]
    for name, text, message in raw:
        result.append(Case(name, encode(metadata), text, message))
    return result
