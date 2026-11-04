# Default-runtime compiler scalars

Doc Status: normative
Date: 2026-11-04
Scope: the active v0.2 PHP compiler; int/int64, null, bool, byte and float/float64.

## Availability and source values

The always-available default-runtime module supplies int64, null, bool, byte and float64.
Runtime metadata owns membership, source-name registration, literal policies,
representations and the supported conversion rules. There is no independent
per-type activation option in this slice. Optional-module loading and further
scalar/container types remain separate work.

`int` and `int64` name the same signed 64-bit type. `bool` admits `true` and `false`.
`null` is its own value type, distinct from void, false and an unknown value.
`byte` is an unsigned eight-bit value in 0..255, semantically distinct from uint8
and int64. It has no dedicated literal spelling in this slice. `float` and `float64` name the
same IEEE binary64 type; source values use decimal-point/exponent notation.

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
| int64 → float64 | Binary64 rounding allowed; not necessarily lossless |
| float64 → int64 | Truncate toward zero; reject nonfinite/out-of-range truncated value |
| bool → float64 | Exactly 0.0 or 1.0 |
| float64 → bool | Both signed zeros are false; nonzero, infinity and NaN are true |
| byte ↔ float64 | No direct conversion |
| bool ↔ byte | No direct conversion; an integer boundary is required |
| null ↔ another type | Not admitted in this slice |

The cross-type rules require explicit intent. A written destination type supplies
that intent. Untyped inference preserves the initializer type and does not request
cross-type conversion. Local inference remains unsupported; existing global literal
inference now recognizes integer, floating, boolean and null literals.

Preparation rejects a known out-of-range byte conversion. Unknown integer values
retain a runtime check; failure throws `std::out_of_range` with
`integer_cast_out_of_range`, preserving an existing destination. The operand is
evaluated exactly once. Same-type copies and widening conversions need no range check.

Global initialization remains literal-only, including true, false and null and
signed decimal integer/floating literals. A written global type may convert that literal.
Local initialization admits the supported literals and local/global references;
signs directly on floating literals are admitted, while general unary expressions
are still deferred. Global reads do not reuse initializer
constants: an unknown global payload must not be folded into a reader's cache.

## Boundaries unchanged

Entry exit status requires int64. The current echo slice accepts int64 or its
string literals and typed string locals; it does not acquire boolean/null/byte formatting.
Void functions, calls, symbols, ownership and source lookup retain their existing rules.

Explicit cast-expression syntax is deferred in every flavor. The generated type
API accepts an explicit-intent argument now, independently of future cast syntax.
No operators, overloads, nullable types, general string operations or container
behavior are introduced by this slice.

## Floating-literal policy

All flavors use the same generated decimal-literal policy: round to IEEE binary64
using nearest, ties to even; reject a value whose rounded result is infinite.
Underflow is accepted, including subnormals and rounding to zero. Preserve the sign
of zero, including negative underflow. Exact source spelling remains available in
the token generation. This does not promise exact decimal arithmetic.

Examples: `1e309` is an error, `5e-324` becomes the minimum positive subnormal,
`1e-400` becomes positive zero and `-1e-400` becomes negative zero. Decimal values
slightly above the maximum finite value are accepted if they round to that finite
value. `NaN` and `Infinity` source literal spellings are not introduced.

The PHP bootstrap requires a compatible IEEE binary64 host. Preparation settles
literal values and diagnoses overflow; C++ lowering must preserve those exact bits,
not round a shortened decimal rendering or delegate source validity to Clang.
No floating environment controls or fast-math semantics are introduced.

Float-to-int64 checks use the truncated value and bounds [-2^63, 2^63), never a
rounded floating representation of INT64_MAX. Known failures diagnose during
preparation. Unknown values use the existing checked runtime boundary, including
nonfinite rejection, one operand evaluation and unchanged destination on failure.
A written type supplies conversion intent; int64-to-float64 rounding may therefore
make a later float64-to-int64 conversion fail even when the original integer fit.

Echo and entry return keep their int64 requirements. A float value must pass through
an int64 destination before those integer operations; this slice adds no float echo.

## First string source slice

The default-runtime module activates source type `string`. Explicitly typed locals
may initialize from a quoted literal or another string local, with owning value-copy
semantics. The same rules apply in cpp/php/js and in entry or void-function bodies.
Strings preserve bytes, including embedded NUL; echo writes their full length.
Direct literal echo may avoid materializing owning storage.

Existing quoted escape rules and UTF-8 source validation remain unchanged. No
implicit or typed-destination text conversions are admitted in this slice. String
globals, inference, uninitialized locals, operators, indexing, methods, interpolation
and string function results remain deferred. Runtime capabilities alone do not
activate these compiler features.
