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
`byte` and `uint8` name the same unsigned eight-bit integer in 0..255, distinct from int64. It has no dedicated literal spelling in this slice. `float` and `float64` name the
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
operations. Mixed int64/float64 arithmetic is admitted by the amendment below; floating remainder remains deferred.
Discarded-value float updates are specified below.
Same-type compound statements are specified below. Explicit conversions may establish float64 operands.
Runtime metadata controls admission and lowering; checked division remains behind
the public runtime API. This does not expand the separately deferred execution-order work.

## Float64 compound statements — 2026-11-05

All flavors admit `+=`, `-=`, `*=`, `/=` on existing float/float64 locals and
value/mutable-reference parameters, including the supported `for` header clauses.
The RHS may have canonical float64 type or the int64 type admitted below.
Int-destination/float-RHS compounds, `%=` and expression-result assignments remain deferred.
Globals, constants, fields and indexed destinations are not admitted by this slice.

The existing compound protocol applies: resolve storage once, preserve its old
value before evaluating the RHS once, compute via the approved float operation,
then store. On failure, do not perform the final store; prior RHS effects remain.
Division by either zero sign throws `division_by_zero`; other arithmetic retains
the float64 infinity/NaN/underflow/signed-zero contract. A completed compound write
invalidates the previous constant fact; its result is not folded during preparation.

## Discarded-value float64 updates — 2026-11-05

All flavors admit prefix/postfix `++` and `--` on established float/float64 locals
and value/mutable-reference parameters, as standalone statements and supported
`for` initializer/update clauses. Resolve storage once and update it once by adding
or subtracting binary64 `1.0`, using the existing native floating contract.
The four spellings do not expose expression results in this slice. Assignment from
an update, argument/return updates, globals, constants and complex targets remain
deferred or rejected by their existing boundaries.

Native rounding is observable: sufficiently large finite values may remain unchanged.
Infinities remain infinite and NaNs remain NaN; no checked-overflow, saturation,
NaN payload guarantee or floating-environment controls are introduced. Updates
invalidate prior constant knowledge even where native rounding leaves a value
unchanged. Preparation does not fold updates or synthesize a literal/RHS expression.

## Conversion contexts and mixed ordering — 2026-11-05

Conversion permission is described by a metadata context list, independently of
conversion behavior. `explicit_cast` authorizes written casts; `typed_destination`
authorizes typed initialization, value arguments, parameter defaults and returns.
`assignment` describes ordinary reassignment; `inference` preserves initializer
identity. Current cross-type conversion rows allow only the first two contexts.
Identity remains allowed in every context. Reference binding requires its existing
exact storage identity; truthiness remains a separate generated condition policy.

Explicit directional promotion rows now admit int64/float64 `< <= > >=` in both
orders, with float64 execution and bool result. These rows authorize operand
conversion only for their listed operations, reusing the defined int64-to-float64
rounding conversion. They do not grant assignment or compound conversion rights.
Missing conversion definitions or execution operations are generator errors.

The mixed-ordering contract in `expression_evaluation.md` applies: integer rounding
is observable, NaN makes all four comparisons false, and signed-zero/infinity
ordering follows binary64. The bounded compiler slice supports plain scalar values;
carriers remain deferred; mixed equality, arithmetic and float-destination
compound writes are specified below.
Each operand is prepared once. Promotion settles conversion/execution facts on the
existing binary node, without synthetic cast nodes or arithmetic constant folding.

## Mixed int64/float64 arithmetic — 2026-11-06

All three flavors admit `+ - * /` between plain canonical int64 and float64,
in either operand order. Convert the integer operand to binary64 before computation;
the execution and result type are float64. Source aliases int/float preserve these
identities. Binary64 rounding is observable: under the normal rounding environment,
`9007199254740993 - 9007199254740992.0` is 0.0, not 1.0.
No exact-integer intermediate or floating-environment controls are introduced.

Mixed division is floating division: `7 / 2.0` and `7.0 / 2` yield 3.5;
unchanged int64 division `7 / 2` yields 3. Integer zero and either floating zero
sign throw `division_by_zero`, including nonfinite numerators. Other results retain
the float64 arithmetic contract above, including overflow, NaN, underflow and signed
zero. Literal overflow rejection remains separate from arithmetic results.

Metadata authorizes these operator-local conversions; ordinary assignment,
reference binding and compound admissions are unchanged. Floating remainder and bool/byte arithmetic remain deferred; mixed equality is admitted below.
Float-destination/int64-RHS compounds are admitted below.
Prepare each operand once and retain existing execution/conversion facts; no
arithmetic constant folding or expanded evaluation-order machinery is introduced.

## Float-destination/int64-RHS compounds — 2026-11-06

All flavors admit `+= -= *= /=` with an existing float64 local, value parameter
or mutable-reference parameter destination and an int64 RHS, including supported
`for` header clauses. The float-destination mixed-compound contract in
`expression_evaluation.md` applies: snapshot the old destination before evaluating
the RHS, convert int64 to float64, compute, then store once. Conversion rounding
above 2^53 is observable. Integer zero division throws `division_by_zero`, including
NaN/infinite numerators. Failure suppresses the final store but preserves earlier
RHS effects. Other results follow the existing floating arithmetic contract.

Permission is explicit in metadata and distinct from ordinary binary promotion and
boundary conversions. Int64 destinations with float64 RHS remain rejected. Globals,
indexed/field destinations, carriers and value-producing compound expressions remain
outside this compiler slice. Preparation visits the RHS once, retains its conversion
on the assignment node and invalidates old constant knowledge; no folding is added.

## Mixed int64/float64 equality — 2026-11-06

All three flavors admit plain int64/float64 `==` and `!=` in either operand order.
Convert the integer to float64 before comparison; the result is bool. The plain
scalar equality contract in `expression_evaluation.md` applies: rounding is
observable near ±2^53 and int64 limits, signed zeros compare equal, and NaN compares
unequal. `!=` negates `==`. Under the normal rounding environment,
`9007199254740993 == 9007199254740992.0` is true. Storage is never converted in place.

Metadata authorizes the existing numeric-floating conversion for equality and
selects float64 execution with bool result. Each operand is prepared once using
existing binary facts and lowering. No constant folding, new runtime helper or
expanded sequencing mechanism is introduced. Strict identity, carriers,
bool/byte mixed numeric equality remain deferred; string equality is admitted below.

## String comparison — 2026-11-06

All three flavors admit exact string/string `==`, `!=`, `<`, `<=`, `>` and `>=`, returning bool.
Ordering is lexicographic over unsigned bytes; the shorter equal prefix sorts first.
Embedded NUL and high bytes participate normally, without numeric interpretation.
Equality compares the full byte sequence and length, including embedded NUL bytes;
`!=` is its negation. Comparison is case-sensitive and independent of storage
identity. Empty strings compare equal. There is no numeric-string conversion,
Unicode normalization or locale collation: `"10" != "010"` and a composed Unicode
sequence differs from a byte-distinct decomposed sequence.

Use existing eager operand snapshots to preserve the left value before RHS effects.
No operand storage is modified by comparison. Runtime public operators own byte
comparison; compiler metadata admits and lowers these operations. Preparation visits
each operand once without constant folding. This extends the earlier bounded
string-identity discussion with ordinary equality; it does not admit strict identity,
carriers, heterogeneous comparisons, concatenation or text conversion in
this compiler slice.

## Agreed family and representation model — 2026-11-06

The scalar model has five semantic families: null, bool, integer, floating and
string. Integer representations supply width/signedness/range; floating representations
supply format/precision/range. int aliases int64 and float aliases float64.
**Byte aliases uint8 and has ordinary integer behavior**, replacing the earlier
distinct bitwise-only byte policy and its special conversion exclusions. Aliases
share canonical identity, including reference binding. Runtime metadata still owns
module activation; this decision does not activate all optional numeric names.
Char and collections remain separate decisions.

When a context permits conversion, integer-to-integer is value-preserving and
range-checked where needed; integer-to-floating allows rounding; floating-to-integer
truncates toward zero and rejects nonfinite/out-of-range results. Floating widening
is exact; narrowing rounds, allows underflow/signed zero and rejects finite overflow.
Bool converts to numeric 0/1; numeric zero converts to false and nonzero (including
NaN) to true. Same-type/null identity and string value-copy semantics remain.
Cross-family null and numeric/bool text conversions are not admitted by this agreement.

Explicit casts and typed initialization/value arguments/defaults/returns authorize
defined conversions. Ordinary assignment remains same-canonical-type-only. References
require exact canonical identity. Inferred locals retain initializer identity and
reject bare null. Operator promotion, compound write-back and truthiness keep separate
permissions. No new default-expression grammar or user-defined overload resolution
is implied.

This is the agreed replacement model, not a claim of completed migration: the canonical byte/uint8 identity is now implemented, while conversion rows and
operator coverage remain bounded pending subsequent slices.
The [implementation plan and conversion tables](../v0.2/specs/planning/scalar_families_and_conversions.md)
track the migration and remaining policy decisions. Earlier implementation-slice
restrictions above describe existing coverage where migration is pending.


### Canonical uint8 identity checkpoint — 2026-11-06

Byte and uint8 are both default-runtime source names for one uint8 identity.
Alias assignment, value arguments/returns and reference binding use that identity;
ordinary assignment from a different numeric identity remains rejected. Integer
literals still have int64 identity. The current compiler retains int64↔uint8
conversion and equality coverage; the family-derived conversion/operator model
above is not yet fully implemented. The public runtime replaces class byte with
class uint8 and a byte alias, requiring regeneration and a native rebuild.

## Integer family promotion — 2026-11-06

The integer policy in [compact layout types](compact_layout_types.md#7-v02-integer-arithmetic-and-numeric-comparisons)
supersedes the older mixed-signedness rejection. Active int64 and uint8 support
`+ - * / %` and `== != < <= > >=` in both operand orders. Same-type arithmetic
preserves identity; mixed byte/int arithmetic selects int64. Comparisons are exact.
Ordinary assignment remains same-type-only. Byte/float arithmetic, new unary/update
capabilities, optional integer representations are deferred. Integer compound write-back is defined below.

## Integer compound write-back — 2026-11-06

For active int64/int and uint8/byte, `+= -= *= /= %=` use ordinary binary promotion,
then convert the calculated result back to the destination through checked integer
conversion. Same-type byte arithmetic remains uint8 arithmetic; identity write-back
does not reinterpret it as wider checked arithmetic. Mixed byte/int calculation is
int64, with range-checked uint8 write-back where required. The RHS is not narrowed
before calculation. Cross-type ordinary assignment remains prohibited.

A failed calculation or conversion prevents the final compound store; effects already
performed by RHS evaluation remain. Existing old-destination snapshot semantics and
zero-divisor errors apply. Checked write-back does not change signed-overflow behavior.
The same rule applies to supported locals/parameters and for-header compounds.
Float-to-integer compounds and additional writable locations remain deferred.

## Mixed numeric families — 2026-11-06

For active int64/int and uint8/byte paired with float64/float, `+ - * /` and
`== != < <= > >=` are admitted in either operand order. Convert the integer operand
to the floating operand's representation before execution. Arithmetic returns that
floating type; comparisons return bool. This supersedes earlier byte/float exclusions.
Existing int64 rounding above 2^53, checked zero division, floating nonfinite results,
underflow and signed zero apply unchanged; uint8 converts exactly to float64.

Floating-destination `+= -= *= /=` accepts either active integer RHS through the same
promotion, including supported locals/parameters and for-header positions. Keep the
existing destination snapshot, single RHS evaluation and failed-store rules.
Integer-destination floating compounds and floating remainder remain deferred.
This does not authorize bool numeric operators, byte truthiness, new unary/update
behavior, optional representations or cross-type ordinary assignment.

## Integer-family signs, updates and truthiness — 2026-11-06

Active int64/int and uint8/byte support unary `+` and `-`, preserving the operand's
canonical identity. Unary plus preserves its value. Unsigned negation reduces modulo
2^width: `-(byte)1` is uint8 255, and `-(byte)0` is uint8 zero. Restore this representation
before an enclosing expression consumes it; `(-(byte)1) + 1` is int64 256.
Operands are evaluated once. Unary results remain non-addressable values. Existing
signed-overflow and signed-literal magnitude limitations are unchanged.

Prefix/postfix `++` and `--` are admitted at the existing discarded-value statement
and for-header positions for both active integer identities. They preserve storage
identity; unsigned updates wrap: byte 255 increments to zero and zero decrements to
255. No checked narrowing is inserted. This deliberately differs from `byte += 1`,
whose int64 RHS selects int64 calculation followed by checked byte write-back.
Locals, value parameters and mutable reference parameters retain existing write rules;
value-producing updates and additional writable locations remain deferred.

Integer truthiness is zero=false, nonzero=true, including uint8/byte. The same rule
applies to supported conditions and `!`, `&&`, `||`, preserving short-circuit behavior.
This supersedes earlier byte-condition exclusions. Null conditions remain deferred.
No new bool arithmetic, implicit assignment conversions or optional widths are added.

## Integer bitwise and shift group — 2026-11-06

Active int64/int and uint8/byte admit `& | ^ ~ << >>` and discarded-value compound
`&= |= ^= <<= >>=` at the existing local/parameter and for-header write positions.
Bool, floating, string and null operands are rejected. Bitwise binary operators use
the existing integer domain promotion: byte/byte returns byte; byte/int returns int64.
`~` preserves the operand identity, including unsigned width: `~(byte)0` is byte 255.
In contrast, `(byte)~0` complements int64 zero and fails checked narrowing of -1.

Shifts preserve the left operand's identity, independently of count identity. Either
active integer representation may supply the count. Counts must be nonnegative and
less than the declared left width (8 or 64); invalid counts fail before shifting.
Counts are never narrowed to the left representation or masked. Native public bindings
currently report `std::domain_error("shift_count_out_of_range")`; the source ValueError
contract is unchanged, and source exception syntax is not introduced by this slice.
Right shift of signed values sign-extends; unsigned right shift zero-fills. Left shift
retains the declared-width bit pattern: byte 128 shifted left by one is byte zero;
int64 one shifted left by 63 is the minimum int64. This adds no signed arithmetic
wrapping guarantee beyond shifts.

Bitwise compounds calculate in the selected integer domain, then use existing checked
write-back. Shift compounds calculate directly in the left domain and use identity
write-back. Failed checks prevent the final store; completed RHS effects remain.
No new expression-order enforcement, constant folding or writable locations are added.

Precedence from tighter to looser: unary, multiplicative, additive, shifts, ordering,
equality, bitwise &, bitwise ^, bitwise |, logical &&, logical ||. Binary levels associate
left. Unary operators associate right. Adjacent angle tokens form shifts only in
expression context; whitespace-separated `< <` and `> >` are invalid. Reference-parameter
`&` and logical operator syntax remain unchanged. All three flavors use these rules.

## String-only concatenation — 2026-11-06

PHP admits string/string `.` and `.=`; C++/JS admit string/string `+` and `+=`.
PHP `+` remains numeric. Concatenation returns string, preserving complete byte
sequences, embedded NUL and empty operands. No implicit scalar-to-text conversion
is admitted. PHP dot binds below shifts and above ordering, following the reviewed
donor precedence; C++/JS addition retains additive precedence.

Compound concatenation uses existing local/reference-parameter writable locations,
including existing for-header positions. It snapshots the previous left string,
computes the new value, and assigns only after successful computation. String
globals, value-producing assignments, new writable locations and text conversions
remain deferred. Existing operand sequencing and string value-copy contracts apply.

## Eager logical xor — 2026-11-06

All three flavors spell eager logical exclusive-or `xor`; `^` remains integer
bitwise XOR. Each operand independently accepts the existing generated truthiness
contract: bool, int64, uint8/byte, float64 and string. Null remains rejected.
This deliberately broadens the older canonical-numeric-only admission; it does not
add text conversion, nullable types or bool/numeric arithmetic promotion.

Evaluate and convert the left operand to bool once, then the right operand once,
even when the left is false. Return bool inequality of those saved truth values.
A left failure prevents RHS evaluation; a right failure preserves earlier effects.

Xor binds below `||` and is left-associative. To preserve its lower-than-assignment
contract while assignment expressions remain deferred, an initializer or assignment
RHS containing xor at its outer precedence level must be parenthesized:
`bool value = (left xor right);`. Ungrouped `value = left xor right;` is rejected,
not reinterpreted as assigning the xor result. Conditions, returns and call arguments
admit the full expression grammar.

## Plain null equality — 2026-11-06

All three flavors admit null/null `==` and `!=`, returning true and false
respectively. Both operands are evaluated, preserving effects and failures.
Null versus any non-null scalar is rejected for ordinary equality; neither
truthiness nor a null-to-value conversion is implied. This is separate from
strict identity and does not introduce nullable types.

## Plain scalar strict identity — 2026-11-06

All active scalar types admit `===` and `!==` with each other, returning bool.
Different canonical representations are never identical: byte 1 is not identical
to int64 1 or float64 1. Aliases share canonical identity, so byte and uint8 do not
differ. No numeric promotion or text conversion participates.

For the same canonical type, identity compares values. Null is identical to null.
Strings compare complete bytes and length, including NUL. Float identity uses
ordinary IEEE equality: positive/negative zero are identical, matching infinities
are identical, and NaN is nonidentical to every value including itself.
`!==` negates this result.

Both operands are evaluated once, left then right, even when their different
canonical types determine the result. A left failure prevents RHS evaluation;
a right failure preserves earlier effects. Strict identity shares equality
precedence. This does not broaden heterogeneous ordinary `==`/`!=`.

## Scalar three-way comparison — 2026-11-06

All flavors admit `<=>` for int64/uint8 pairs, numeric pairs containing float64,
and string/string. The result is canonical int64 -1, 0 or 1. Bool, null and
heterogeneous string pairs remain rejected. Precedence is the equality level.

Integer pairs use the agreed lossless integer-domain promotion. Mixed floating
pairs convert the integer to float64 first: rounding above 2^53 remains observable.
Either floating NaN throws `unordered_comparison`; signed zeros compare equal and
infinities order normally. Strings compare complete unsigned-byte sequences with
the shorter equal prefix first, including embedded NUL, without text conversion.

Operands are evaluated once using the existing ordered snapshot path. This operator
does not authorize compound writes, nullable operands or optional numeric widths.

## Canonical integer power and current error boundary — 2026-11-06

All flavors admit exact int64/int64 ** and discarded-value **= at existing
writable locations. The value, precedence, associativity and checked-overflow rules
in [integer_power.md](integer_power.md) apply. Byte, float, bool, null and string
power remain rejected; no promotion into this operation occurs.

For the current v0.2 runtime, the user approved standard std::runtime_error
with what() equal to power_negative_exponent or power_overflow.
Structured component/operator fields and the older wrapper-based public signature
are not introduced. The named native-value binding is scpp::int64_power.
Restoring structured error fields is explicit runtime debt; it does not relax
calculation, failure order or successful-write-only guarantees.
