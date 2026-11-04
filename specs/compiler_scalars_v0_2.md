# Default-runtime compiler scalars

Doc Status: normative
Date: 2026-11-04
Scope: the active v0.2 PHP compiler; int/int64, null, bool and byte only.

## Availability and source values

The always-available default-runtime module supplies int64, null, bool and byte.
Runtime metadata owns membership, source-name registration, literal policies,
representations and the supported conversion rules. There is no independent
per-type activation option in this slice. Optional-module loading and further
scalar/container types remain separate work.

`int` and `int64` name the same signed 64-bit type. `bool` admits `true` and `false`.
`null` is its own value type, distinct from void, false and an unknown value.
`byte` is an unsigned eight-bit value in 0..255, semantically distinct from uint8
and int64. It has no dedicated literal spelling in this slice.

The spellings `true`, `false` and `null` are common to all three source flavors.
They are case-sensitive. Null is also a valid type nominator in every flavor.

## Written destinations authorize conversion

A written destination type is an explicit conversion boundary. No separate cast
expression is required for initialization:

| C++ flavor | PHP flavor | JS flavor |
| --- | --- | --- |
| `byte value = 8;` | `$value byte = 8;` | `let value: byte = 8;` |
| `bool ready = 1;` | `$ready bool = 1;` | `let ready: bool = 1;` |
| `null empty = null;` | `$empty null = null;` | `let empty: null = null;` |

This decision applies to the implemented typed local/global declarations. It does
not introduce implicit arithmetic promotion, truthiness, assignments, argument or
return conversion rules. Future boundaries must request the appropriate conversion
context deliberately. A typed declaration is not represented as an authored cast AST.

| Source → destination | Rule |
| --- | --- |
| Same type | Identity, also allowed without an explicit destination |
| bool → int64 | false becomes 0; true becomes 1 |
| int64 → bool | zero becomes false; any nonzero value becomes true |
| int64 → byte | Checked 0..255, never truncation/wrapping |
| byte → int64 | Exact widening |
| bool ↔ byte | No direct conversion; an integer boundary is required |
| null ↔ another type | Not admitted in this slice |

The cross-type rules require explicit intent. A written destination type supplies
that intent. Untyped inference preserves the initializer type and does not request
cross-type conversion. Local inference remains unsupported; existing global literal
inference now recognizes integer, boolean and null literals.

Preparation rejects a known out-of-range byte conversion. Unknown integer values
retain a runtime check; failure throws `std::out_of_range` with
`integer_cast_out_of_range`, preserving an existing destination. The operand is
evaluated exactly once. Same-type copies and widening conversions need no range check.

Global initialization remains literal-only, including true, false and null and
signed decimal integer literals. A written global type may convert that literal.
Local initialization admits the supported literals and local/global references;
general unary expressions are still deferred. Global reads do not reuse initializer
constants: an unknown global payload must not be folded into a reader's cache.

## Boundaries unchanged

Entry exit status requires int64. The current echo slice accepts int64 or its
existing output-only string literal; it does not acquire boolean/null/byte formatting.
Void functions, calls, symbols, ownership and source lookup retain their existing rules.

Explicit cast-expression syntax is deferred in every flavor. The generated type
API accepts an explicit-intent argument now, independently of future cast syntax.
No operators, overloads, nullable types, float/string variable support or container
behavior are introduced by this slice.
