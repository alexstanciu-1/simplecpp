# Default-runtime compiler scalars

Doc Status: normative
Date: 2026-11-05
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
not introduce implicit arithmetic promotion. Condition admission is separately
defined by `conditional_statements_v0_2.md`. Typed function parameters and returns request
explicit conversion intent under the [function contract](compiler_entry_v0_2.md#typed-value-parameters-and-returns). A typed declaration is not represented as an authored cast AST.

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
cross-type conversion. Local inference uses the initializer type except null, as specified below. Existing global literal
inference recognizes integer, floating, boolean and null literals; this local slice does not change it.

Preparation rejects a known out-of-range byte conversion. Unknown integer values
retain a runtime check; failure throws `std::out_of_range` with
`integer_cast_out_of_range`, preserving an existing destination. The operand is
evaluated exactly once. Same-type copies and widening conversions need no range check.

Global initialization remains literal-only, including true, false and null and
signed decimal integer/floating literals. A written global type may convert that literal.
Local initialization admits the supported literals and local/global references;
int64 and float64 arithmetic/unary expressions are admitted as specified below. Global reads do not reuse initializer
constants: an unknown global payload must not be folded into a reader's cache.

## Boundaries unchanged

Entry exit status requires int64. The current echo slice accepts int64 or its
string literals and typed string locals; it does not acquire boolean/null/byte formatting.
Void functions, calls, symbols, ownership and source lookup retain their existing rules.

Explicit cast expressions use the same generated explicit-intent conversion API;
the bounded syntax and value semantics are defined below.
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
globals, uninitialized locals, operators, indexing, methods and interpolation remain deferred. String value parameters and
returns follow the typed-function contract. Runtime capabilities alone do not
activate these compiler features.

## Local assignment extension

Plain `=` to an already initialized local is supported in all flavors, in entry and
void-function bodies. The RHS is evaluated before the destination changes. Its
prepared type must satisfy the destination's generated implicit conversion rules;
current metadata admits same-type identity only, including int/int64 and
float/float64 aliases. An earlier written declaration type does not authorize
explicit conversions on later writes. Thus `byte b = 8;` is valid but `b = 9;`
requires an explicit `(byte)` conversion or an already-byte source.

Strings use owning value-copy assignment and preserve embedded NUL. Self-assignment
is valid and has no observable effect for the supported built-in value types.
Reads use source-order value facts; a write does not change earlier read facts or
the declaration's initializer facts. Unknown values retain required runtime checks.

Direct global writes, assignment expressions and member/index writes remain deferred.
The bounded compound-statement extension is defined below. PHP first-write declarations follow the local inference rules below.

## Initializer-based local inference

PHP `$value = initializer;` declares a local on its first write in the current body.
Subsequent bare writes are assignments. C++ uses `auto value = initializer;` and JS
uses `let value = initializer;`; their bare writes still require an existing local.
Explicit declarations still diagnose duplicates, including after an inferred declaration.

Preparation infers the exact resolved type of the initializer once. Integer literals
infer int64, floating literals float64, boolean literals bool, and quoted literals
string. References preserve their source type, including byte. A small integer
literal does not infer byte. Later writes cannot widen or replace the local's type;
ordinary generated implicit conversion rules apply. A written type still requests
the existing explicit-intent initialization conversion.

An inferred initializer whose type is null rejects, including a null-typed reference.
An explicit `null` type remains valid. No later assignment is consulted to infer a
nullable type. Reading the local during its own initializer rejects, and ordinary
forward references to later locals remain unavailable.

PHP classification depends only on prior declarations in this body. A first bare
write introduces a local even if a same-named global exists; it does not write that
global. Its own initializer cannot use the same unqualified name as a backdoor to
the global. Before that local declaration, an ordinary read may still resolve an
available global under existing lookup rules. Function and implicit-main bodies
have independent local collections. No source profile/configuration is introduced.


## Explicit scalar cast expressions — 2026-11-05

All three active flavors admit `(Type) operand`, e.g. `echo (int) 8.75;` or
`echo (int) (byte) value;` (PHP writes `$value`). This common Simple C++ spelling
is a runtime value conversion, not a JavaScript/TypeScript type assertion.
The target is one registered default-runtime source type name: int/int64, bool,
byte, float/float64, null or string. Void casts, user-defined/qualified targets,
nullable types, templates and alternative cast spellings are outside this slice.

Casts bind at unary precedence, above all binary operators, and nest right to left.
`(int) 8.75 + 1` means `((int) 8.75) + 1`; casting a whole binary expression requires
parentheses around that expression. Grouping remains grouping when no cast operand
follows, including `(null)`. Existing signed floating literals are operands;
int64 and float64 unary signs are supported below.

A source cast requests explicit conversion intent under the conversion table above.
It introduces no new source/destination pair and does not compose conversions
implicitly. Same-type casts are permitted, including string and null identity.
String/numeric/text conversions remain deferred; explicit bool conversion does not
inherit the separate string truthiness rule.

The operand is evaluated once. Known range failures diagnose during preparation;
unknown values retain the generated checked runtime conversion. The result is a
value of the destination type; it does not provide stable mutable-reference storage,
even for an identity cast. Typed initialization, assignment, argument and return
boundaries keep their own existing checks. Casting to null does not enable null
inference. Cast expressions do not broaden the literal-only initializer contract
for globals, constants or parameter defaults.


## Int64 compound assignment statements — 2026-11-05

All three flavors admit `+=`, `-=`, `*=`, `/=` and `%=` on existing initialized
int/int64 local storage and parameters (including mutable reference parameters).
The RHS must produce the same canonical int64 type; there is no implicit numeric
promotion or narrowing. An explicit cast may supply an int64 RHS.
PHP compound writes cannot introduce a local. Constants and direct globals are not
writable destinations in this slice.

These spellings select ordinary binary operations through one compound-assignment
mechanism. The destination is resolved once, its old value is preserved before the
RHS evaluates once, and the computed int64 value is then stored once. The
[compound-update ordering contract](expression_evaluation.md#compound-updates--2026-10-10)
applies. On failure, no compound write-back occurs; earlier RHS effects remain.
In particular, starting at 10, `value += change(value)` stores 13 if `change`
sets the same storage to 100 and returns 3.

The existing int64 arithmetic contract is unchanged: native signed-overflow
limitations, truncation toward zero for division, and checked zero divisors for
division/remainder. No wrapping, saturation or checked-overflow policy is added.

This slice admits statements only, not compound expressions yielding a value.
Other compound operators/types, fields/indexes and
implicit result-to-destination conversions remain deferred. Body statements and [for header clauses](loop_statements_v0_2.md#compound-for-headers--2026-11-05)
use the ordinary shared assignment path.


## Discarded-value int64 updates — 2026-11-05

All flavors admit prefix/postfix `++` and `--` on existing initialized int/int64
locals and parameters, including mutable reference parameters. Supported positions
are standalone statements and both initializer/update clauses of bounded for loops:

```text
cpp: ++value; value--; for (int i = 0; i < 10; i++) {}
php: ++$value; $value--; for ($i int = 0; $i < 10; $i++) {}
js:  ++value; value--; for (let i: int = 0; i < 10; i++) {}
```

The destination is resolved once and updated once. No implicit local declaration
is allowed, including in PHP. Value parameters change only their local storage;
reference parameters change the caller's storage. Supported type/operator pairs
are explicit runtime metadata capabilities, independent of arithmetic admission.
Native signed-overflow behavior is unchanged; no checked, wrapping or saturating
policy is added. Runtime execution tests stay within representable int64 bounds.

This slice discards results. Consuming prefix/postfix values in initialization,
assignment, calls, returns, conditions or larger expressions remains deferred.
It does not change the eventual value-snapshot contract: prefix yields the new
value, postfix the old value, neither addressable. Repeated updates such as `++x++`
and non-simple targets (calls, casts, literals, fields, indexes) are rejected.
Constants, direct global writes, other types and parenthesized-target spelling
remain outside this slice.

For header initialization runs once; updates run after normal body completion or
continue. Break, return and false initial conditions suppress the update. Existing
scope, shadowing and loop preparation rules apply. Updating storage invalidates its
cached scalar payload; the compiler does not retain the initializer value as if
no mutation occurred or introduce arithmetic folding in this slice.

## Int64 unary signs — 2026-11-05

All three flavors admit unary `+` and `-` on int/int64 expressions, producing an
int64 value. The operand is evaluated once; the result is not writable storage or
an admissible mutable-reference argument. No implicit numeric admission is added
for bool, byte, string or null. Float64 signs have their own same-type contract below.

Signs bind above binary operators and nest right-to-left. `- -8` is nested
negation; `--8` retains its distinct update token and is not two unary signs.
Casts of signed integer expressions use grouping, e.g. `(int)(-8)`; `-(int)3.5`
is also supported. Existing cast/grouping lookahead is unchanged. The existing
signed floating-literal path, including signed zero, is unchanged.

Unary expressions prepare their positive integer literal child under the existing
int64 bound. Thus `-9223372036854775808` in an execution expression is rejected;
`-9223372036854775807 - 1` constructs the minimum without an oversized literal.
Existing signed-literal handling at literal-only declaration boundaries is unchanged.
Negating an already minimum-valued integer preserves native signed-overflow
limitations; no checked, wrapping or saturating semantics are introduced.

Runtime metadata owns admission, result identity and direct C++ lowering. No new
constant folding or expression-evaluation-order policy is introduced by this slice.

## Float64 arithmetic — 2026-11-05

All three flavors admit same-type `float`/`float64` binary `+ - * /` and unary
`+ -` on general expressions. Both names identify binary64; results have that same
identity. Precedence, associativity and operand evaluation follow the existing
expression contract. Unary results are values, not reference storage.

Division by either `+0.0` or `-0.0` throws the existing `division_by_zero` domain
error, including zero, infinite and NaN numerators. Other arithmetic follows the
native binary64 contract: overflow may produce infinity; NaNs propagate; underflow
may produce subnormals or signed zero. Unary plus preserves the value and unary
minus changes its sign, including zero. No NaN payload guarantee, floating
exception/environment controls or fast-math behavior is introduced.

Literal preparation still rejects overflow and accepts underflow; arithmetic
results are not subjected to the finite-literal restriction. NaN and Infinity
literal spellings remain unavailable. Preparation does not constant-fold these
operations. Mixed int64/float64 arithmetic, floating remainder, compound writes
and updates remain deferred. Explicit conversions may establish float64 operands.
Runtime metadata controls admission and lowering; checked division remains behind
the public runtime API. This does not expand the separately deferred execution-order work.
