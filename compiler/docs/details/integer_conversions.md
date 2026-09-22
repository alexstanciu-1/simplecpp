# First integer conversion
Doc Status: supporting

Implemented in the PHP prototype: **implicit widening within an explicitly
declared integer family, preserving signedness**. This is one language operation
with signed and unsigned backend primitives. It applies equally to arguments,
returns, local initialization and assignment.

The current [Simple C++ strict-authoring skill](../../../../../simple_cpp_compiler/.codex/skills/simple-cpp-php-strict/SKILL.md)
permits same-signed widening and requires explicit casts for narrowing or
signedness changes. The older fixed-width planning document remains background,
not authority for additional behavior.

## Rules and metadata

`named_type_definition.integer_family` is optional shared language metadata.
A nonempty family identity explicitly opts integer definitions into the widening
rule; both definitions must name that family. Omission leaves cross-type
conversion unsupported. Family membership is not inferred from a spelling,
width, signedness or shared representation.

The [language catalog](../../language/named_types.json) groups its
integer definitions under `simple_cpp.integer`, including the newly exposed
`int32` and `uint8`. The matching algorithm does not interpret that string.

| Source and destination | Result |
|---|---|
| Same canonical value type | Identity; reuse the source value |
| Same integer family and signedness; destination has more bits | Widen; preserve the mathematical value |
| Narrower destination | Unsupported implicit conversion |
| Changed signedness | Unsupported implicit conversion, including equal widths |
| Different type IDs with equal widths | Unsupported; no alias relationship is inferred |
| Missing/different family, void, or another type category | No integer widening |

Explicit cast syntax, lossy conversion policy, fractional literals, ownership and
cleanup remain separate slices. Rejected conversions produce source diagnostics;
the compiler never truncates implicitly or changes a literal's type to fit its
destination.

## One value path

`Conversion_Resolver` now consumes a source/destination/purpose request against a
fixed type snapshot and selects identity or the `integer_widen` primitive for
implicit boundaries. The [conversion selection model](conversion_selection.md)
also supports separately authorized provider calls.
Identity keeps the existing value ID. Widening appends a destination-typed
`typed_value` whose `conversion_value` payload contains the earlier input value
ID and selected operation. The source value keeps its original type.

Arguments, writes and returns reference that converted result. Their old
conversion flags were removed: the unary value is the authoritative plan.
Nested calls and conversions use iterative cursors, preserving left-to-right
argument evaluation without recursive PHP execution or copied ASTs.

Lifetime analysis ends the source temporary with `conversion_input`, then starts
the result's lifetime. Its `consumer_id` is the result value ID; for
`argument_copy` it is a call ID, and for statement-boundary ends it is zero.
Both source and result must satisfy the existing scalar copy/no-cleanup contract.

Lowering produces a distinct lowered value and `convert` instruction. The
`conversion_operands` payload identifies the input and prepared backend primitive:
`sext` for signed widening, `zext` for unsigned widening. LLVM emission consumes
that decision. Body conversion and native-entry adaptation share primitive LLVM
formatting; the language conversion policy and native status policy remain separate.

## Reuse and proof

The family is part of the shared type definition already tracked by checked
bodies. Catalog edits change the existing provider context and use full selection;
no separate conversion cache or invalidation path is introduced. Body edits use
ordinary selective replacement. Workers read fixed inputs and build private
flat records; joins retain unchanged results. Only actual conversions add value
and lifetime rows.

[The focused proof](../../tests/features/integer_conversions.php) covers native
three-file compilation at all four implicit boundaries, nested calls, signed and
unsigned extension, debug exports, reverse worker completion, unchanged reuse,
body-edit caller/object reuse, failure/repair, metadata revocation and fresh-build
agreement. Different provider names and 13/37-bit widths exercise the same rule.
Test-only LLVM callers also pass a negative signed value and a high-bit unsigned
value into actual compiler-emitted functions and compare the full-width results.

The shipped literal default remains 64-bit `int`. Since explicit casts and
contextual literal typing are not implemented, the native source tests use the
existing `type_catalog_path` configuration with a narrower literal default.
This supplies genuine narrow inputs through the normal pipeline; it does not
change the shipped language default or claim support for narrow construction syntax.
