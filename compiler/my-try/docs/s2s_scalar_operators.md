# Bounded scalar operators
Doc Status: supporting

Reconciled 2026-10-19 against scoped verification evidence. This guide records the my-try frontend
and C++ S2S slice for [catalog chapter 02](catalog/02_expressions.md). Root language
and runtime specifications remain authoritative. Imported legacy examples do not
activate additional operand types or coercions.

## Scalar admission follow-up — 2026-10-17

Exact bool `==` / `!=` now return bool. Canonical float/float and int/float pairs
admit `+`, `-`, `*`, `/` returning float; float unary `+` / `-` are admitted.
Dedicated boolean/floating owners publish the existing decision shape, including
operator-only canonical int-to-float conversion. Exact float compound updates are covered by the later compound proof; narrowing compounds remain deferred; the V-L04 follow-up
below now admits ordinary float comparisons and mutations. Zero divisors raise `division_by_zero`,
including floating positive/negative zero and both int/float operand orders.
Preserving this existing runtime behavior was agreed on 2026-10-18; the stale
IEEE documentation is corrected. See the [proof and resolved contract](planning/v0_1_compatibility_scalar_admission.md).

The older bounded tables below describe the preceding slices; this follow-up
supersedes their rejection of the specific float arithmetic forms above.

## Float comparisons and mutation — 2026-10-19

[V-L04 verification](planning/float_operators_l04.md) admits exactly float/float
`==`, `!=`, `<`, `<=`, `>`, `>=`, returning bool, and canonical float prefix/postfix
`++`/`--`. The O1 and E4NR extensions below add int-to-float ordering and ordinary equality. They preserve ordinary
IEEE value comparisons, including signed-zero equality and unordered NaN behavior.
Prefix yields an updated float value snapshot; postfix yields the previous value
snapshot. Neither result is assignable or reference-bindable, regardless of the
runtime prefix operator's reference return. Existing local/reference-parameter and
property writable-target rules still apply.

The focused generated-C++ proof covers all ten forms, ordered once-only operands,
class/struct/static storage, signed zero, infinities and NaN, plus rejection and
incremental recovery. Scalar/sequencing and property regressions passed on dev002;
`division_by_zero` remains unchanged. No runtime implementation or native compiler
rebuild was involved. Later bounded slices below extend compounds, lifted operators
and mixed ordering; strict float identity, spaceship and new target categories remain separate.

## Numeric logical admission — 2026-10-19

[V-L05 verification](planning/numeric_logical_l05.md) adds `!` for canonical int
and float, plus `&&`, `||`, `and`, `or` for int/int, float/float and int/float in
both orders. Every result is bool. Zero (including either float signed zero) is
false; nonzero, infinity and NaN follow the existing numeric truth rule. Each
operand uses truth conversion independently; no common arithmetic value is needed.

The LHS is evaluated and converted once before the RHS is conditionally reached.
AND skips RHS when LHS is false; OR skips RHS when LHS is true. Both operands still
prepare even if runtime selection would skip one. The existing native-bool emitter
owns laziness; eager materialization and overloaded wrapper logical calls are not
used. Word aliases keep their lower assignment precedence. Bool/numeric pairs and fixed-width expansion remain excluded. The numeric-XOR
amendment and guarded follow-ups below extend this original bounded slice.
The guarded follow-up below corrects V-L08 for nullable canonical int; other
payload domains remain separate.

## Ordinary string comparisons — 2026-10-19

[V-L03 verification](planning/string_comparisons_l03.md) admits exact string/string
`==`, `!=`, `<`, `<=`, `>`, `>=` and `<=>`. Equality compares complete bytes;
ordering is lexicographic by bytes, including embedded NUL and high bytes. Numeric-looking
strings are not coerced, and neither locale collation nor Unicode normalization applies.
Six comparisons return bool; spaceship returns canonical int exactly -1, 0 or 1.

`String_Operators` selects identity operand conversions and named string operations.
The common eager emitter copies the left string before evaluating and copying the
right, then compares their native byte values. RHS mutation cannot alter the saved
LHS. Exceptions stop later evaluation while preserving earlier effects. Strict
identity remains on its existing path; wrappers, mixed-type and table semantics
are separate. No runtime implementation change was needed.

## Guarded wrapper operators — current through 2026-10-19

Nullable, `result`, `result_or_false` and `result_or_bool` use independent checked
payload extraction before ordinary scalar admission. Binary pairs may use the same
carrier, different carriers, or a carrier and a plain admitted scalar in either
order. Results are plain scalars. The bounded payload/operator set is:

| Payloads | Binary spellings | Result |
| --- | --- | --- |
| int/int | `+ - * / % **`, `< <= > >=`, `& \| ^ << >>`, `&& \|\| and or xor`, `<=>` | int; bool for ordering/logical |
| float/float | `+ - * /`, `< <= > >=`, `&& \|\| and or xor` | float; bool for ordering/logical |
| int/float, either order | `+ - * /`, `&& \|\| and or xor` | float; bool for logical |
| bool/bool | `&& \|\| and or xor` | bool |
| string/string | `< <= > >=`, `<=>` | bool; int for spaceship |

The [guarded-bool XOR proof](planning/guarded_bool_xor.md) adds all 24 same-family,
cross-family and wrapper/plain bool signatures. XOR evaluates and extracts the left
operand, snapshots its bool payload, then evaluates and extracts the right for either
left value. The first failure stops further evaluation; absent/error/sentinel states
do not become false. XOR keeps its lower-than-assignment precedence and returns bool.
String XOR remains excluded; numeric XOR is admitted by the follow-up below.

The [guarded spaceship proof](planning/guarded_spaceship.md) adds 48 signatures:
24 canonical-int/int and 24 string/string pairs across same-family, cross-family
and wrapper/plain forms. Results are exact canonical int -1/0/1. Independent
extraction retains its existing errors and eager left snapshots; string spaceship
uses byte order. This gate excludes fixed-width, float, bool and mixed payload
pairs even where ordinary scalar admission has a broader integer domain.

The [numeric XOR proof](planning/numeric_xor.md) admits all four canonical numeric
payload pairings, plain or guarded, for 100 signatures. Preparation records
`condition_xor` with independent operand truthiness facts; eager emission saves
each converted bool in source order after any checked extraction. No arithmetic
promotion is used. Either left truth value reaches the RHS; failures stop later
work. Signed zeros are false; NaN/infinities and other nonzero values are true.
See the [numeric XOR amendment](../../../specs/expression_evaluation.md#canonical-numeric-logical-xor--2026-10-19).

Unary int `+ - ~ !`, float `+ - !` and bool `!` are also guarded. Extraction retains
each operand's own payload type; scalar owners select numeric promotion, truth
conversion and byte comparison. Eager emission saves the checked converted left
value before RHS effects, including local/property replacement. Logical RHS
expressions are evaluated and checked only when reached, although statically
invalid expressions still reject even in skipped branches.

Absent nullable operands retain `invalid_nullable_unwrap_empty`. Result operands
retain their family-specific extraction errors. Both boolean sentinel states lack
payloads and throw when reached; payload false remains a valid bool. The first
reached failure wins, and earlier effects remain visible. Division by either sign
of zero retains `division_by_zero`, including NaN/infinite numerators. Remainder
keeps its separate zero error. String ordering uses complete unsigned-byte values,
including embedded NUL; empty and numeric-looking strings remain ordinary payloads.

Direct condition, selection, presence and take keep their separate contracts.
The true boolean sentinel remains truthy/present there; boolean-sentinel Result
coalescing still rejects. Existing nullable-string `!` remains condition negation.
V-D13's contradictory `nullable_arithmetic` forbidden configuration was removed
in the first slice. The later numeric compound proofs below establish bounded guarded write-back.

Equality/identity beyond the completed matching-payload slices, mixed numeric
compounds, indexed updates, spaceship for other types and additional payload/operator
domains remain separate. Bool/numeric mixing and string logical operations are not admitted.

The [cross-family proof](planning/guarded_cross.md) covers all 660 newly admitted
signatures over 12 ordered distinct-family pairs. Earlier evidence remains in the
[int](planning/guarded_wrappers_01.md), [boolean-sentinel int](planning/guarded_wrappers_02.md),
[float](planning/guarded_float.md), [mixed numeric](planning/guarded_mixed.md),
[bool](planning/guarded_bool.md) and [string](planning/guarded_string.md) reports.
Those dated reports describe their original bounded slices; their cross-family
deferrals are superseded only for the table above. No runtime implementation
changes, full matrix rerun or native compiler rebuild were involved.

## Nullable matching-payload equality — 2026-10-19

The [nullable-int proof](planning/nullable_int_equality.md),
[bool/string extension](planning/nullable_bool_string_equality.md), and
[nullable-float extension](planning/nullable_float_equality.md) admit `== != === !==`
for `?T/?T`, `?T/T` and `T/?T`, where both payloads match and `T` is canonical
`int`, `float`, `bool` or `string`: 48 signatures returning bool. Two empty states compare
equal; empty versus any payload compares unequal, including zero, false, `""` and
`"0"`. Present integers compare exactly; float value equality makes signed zeros equal
and NaN unequal to every present value, including itself. Bools compare their values, and strings
compare complete byte sequences (no numeric interpretation or Unicode normalization).
Strict identity and equality agree within these matching-payload domains;
their negated forms invert the result.

`Wrapper_Equality` owns this admission independently of `Guarded_Operators`.
Named wrapper-state comparison decisions retain identity conversions of complete operand
values, with no extraction facts. Existing eager emission snapshots left then right
before calling the matching runtime equality/identity helper. An empty left operand
still evaluates the RHS. Source failures stop later evaluation and preserve effects.
RHS replacement cannot alter the saved left presence state or payload.

Literal-null presence comparisons keep their existing path. Guarded arithmetic and
ordering still throw on unavailable payloads. The Result extension below admits matching `result<T>` pairs. Sentinel families,
fixed-width payloads, mixed payloads, cross-family equality and writable updates remain separate. Existing boxed
comparison paths are not widened or intercepted by this bounded candidate.

## Structured-error Result equality — 2026-10-19

[The Result equality proof](planning/result_equality.md) admits 48 `== != === !==`
signatures over `result<T>/result<T>`, `result<T>/T`, `T/result<T>` for canonical
int, float, bool and string. `Wrapper_Equality` generalizes the former nullable owner;
semantic carrier roles and exact payload matching admit only these bounded pairs.
The same four comparison operations retain whole-state identity conversions and
existing eager emission. Equality and identity remain separate helper calls.

Two successes compare payloads. Failure versus success/plain payload is unequal
and non-identical. Two failures compare equal with `==`; `===` instead compares
structured error status, message, file and line. Negated forms invert their respective
results. A stored failure does not throw or skip the RHS; a source exception does
stop subsequent evaluation. Errors and values are snapshotted before RHS replacement.
`error::$no_error` cannot construct a failure. Guarded operators still require payloads.

The proof checks all four payload domains, independent error-field variations,
source errors, whole-state local/property replacement and recovery. Sentinel Result
families, mixed payloads, cross-family equality, explicit error-sentinel comparisons
and writable updates remain separate. No runtime implementation changes.

## False-sentinel bool equality — 2026-10-19

[The bounded proof](planning/false_bool_equality.md) admits 12 `== != === !==`
signatures over `result_or_false<bool>` with itself or plain bool in either order.
`Wrapper_Equality` recognizes the false-sentinel role only for a canonical bool
payload; other payloads and the boolean-sentinel family remain separate.

For two carriers, equality keeps sentinel and payload states distinct: sentinel
versus wrapped false is unequal. Strict identity normalizes the sentinel to visible
false, making that pair identical. When comparing the carrier with plain bool,
the sentinel equals and is identical to plain false. Negated operators invert their
respective results. These comparisons never change the stored carrier state.

All three states (sentinel, payload false, payload true), eager source errors and
local/property replacement snapshots are verified. Source `false` constructs a bool
payload; `false_sentinel` constructs the sentinel. Direct comparisons with the
explicit `false_sentinel` token remain rejected; this slice does not add that path.
Guarded `!` still throws on the sentinel. No runtime implementation changes.

## Two-sentinel bool equality — 2026-10-19

[The four-state proof](planning/bool_sentinel_equality.md) admits 12 `== != === !==`
signatures over `result_or_bool<bool>` with itself or plain bool in either order.
The existing bounded boolean-sentinel payload rule now admits both sentinel carrier
roles. Same-carrier/exact-payload checks and complete-state snapshots are unchanged.

Two carrier operands compare equal only when their states and bool values match:
true sentinel differs from payload true, and false sentinel differs from payload
false. Strict identity compares visible bool values instead. Wrapper/plain bool
comparisons also compare visible values. Different sentinels remain unequal and
non-identical. Negated forms invert their respective results; no comparison changes
the carrier state or extracts an unavailable payload.

All four states, eager source errors, local/property replacement, literal bool
versus explicit sentinel construction and retained recovery pass on dev002. Guarded
negation still rejects both sentinel states. Direct `false_sentinel`/`true_sentinel`
comparisons, non-bool payloads, cross-family pairs and updates remain separate.
No runtime implementation changes or full matrix rerun.

## Non-bool sentinel equality — 2026-10-19

[The bounded proof](planning/nonbool_sentinel_equality.md) adds 48 signatures for
`result_or_false<T>` and `result_or_bool<T>`, with canonical int/float/string:
all four comparisons for matching carriers, and strict identity/non-identity with
plain T in either order. Carrier recognition and operator/arrangement admission
are separate checks in `Wrapper_Equality`. Complete-state snapshots and existing
comparison operations remain unchanged; no runtime or emitter changes.

Matching sentinels compare equal and identical; opposite sentinels differ. A
sentinel differs from every non-bool payload. Present values use scalar comparison
semantics, including NaN and byte strings. No comparison extracts absent payloads.
The earlier 24 wrapper/plain runtime blockers are resolved by the approved
[sentinel/plain clarification and repair](planning/sentinel_plain_equality.md).

## Sentinel/plain equality — 2026-10-19

Both sentinel carriers now admit all four comparisons with matching canonical
int/float/bool/string payloads, in either order. For non-bool plain payloads, a
sentinel compares unequal (`== false`, `!= true`); a present payload uses scalar
equality. This is state-aware comparison, not truth conversion or guarded unwrap.
Bool sentinel distinctions and existing mixed/boxed delegation are preserved.

The runtime generator owns a shared sentinel/plain comparison helper, restricted
to exact canonical payload matches. The temporary compiler arrangement gate is
removed. The 24 new signatures and previous 48 non-bool signatures pass on dev002,
with independent runtime controls and source snapshot/recovery proofs. Mixed
payloads, cross-family source comparisons, explicit sentinel tokens and updates
remain separate. No full matrix rerun.

## Canonical carrier updates — 2026-10-19

[U1 and V-R10](planning/carrier_updates.md) admit 32 prefix/postfix `++`/`--` forms
for all four carriers of canonical int/float. The runtime generator checks mutable
payload access before updating once; absent/error/sentinel states remain unchanged
and throw existing unwrap errors. Native prefix returns the same carrier reference;
native postfix returns an old carrier value. Source results are independent carrier
snapshots, including prefix. Existing writable locals, reference parameters and
admitted instance/static properties retain their target validation. Indexed targets,
other compounds, fixed-width/nested payloads and new receiver forms remain separate.

## Canonical-int compounds — 2026-10-19

[The bounded compound proof](planning/int_carrier_compounds.md) admits `+= -= *= /=`
for canonical-int carriers and carrier/plain pairs in either direction.
All four carriers use existing mutation preparation, ordered extraction and write-back:
stabilize destination and old value; extract left before RHS; evaluate then extract
right; compute and convert before one commit. Failure performs no compound commit,
while prior RHS effects remain. Results are destination-typed value snapshots.
The [remainder/bitwise/shift extension](planning/int_bitwise_compounds.md) adds
`%= &= |= ^= <<= >>=` with the same ordering and write-back contract. Remainder
preserves `modulo_by_zero`; bitwise and shifts preserve native integer semantics.
The [cross-family extension](planning/int_cross_compounds.md) adds all 120 ordered
distinct-carrier int signatures. Each operand extracts independently; the computed
int wraps into the destination carrier, without converting or normalizing carrier states.
Existing local/reference/instance/static storage is supported. Indexed and power
compounds remain separate; runtime and emitter are unchanged.

## Exact-float compounds — 2026-10-19

[The float compound proof](planning/float_compounds.md) verifies four scalar float/float
prerequisites and all 96 carrier signatures for `+= -= *= /=`. Floating operator
preparation owns the exact-float arithmetic domain shared by scalar and guarded
admission; the C2 extension below permits only float-destination mixed promotion. Existing extraction,
stable destinations, computation, wrapping and write-back retain the sequence above.
Both signed-zero divisors throw `division_by_zero`, even with NaN/infinite numerators.
Other arithmetic follows the native float contract; unavailable carriers throw on
extraction. Results are destination-typed snapshots. Narrowing, indexed,
fixed-width and non-arithmetic float compounds remain deferred. No runtime changes.

## Mixed numeric ordering (O1) — 2026-10-19

[The O1 proof](planning/mixed_ordering.md) verifies eight scalar prerequisites and
192 carrier signatures for `< <= > >=` on canonical int/float pairs in either order.
The [language amendment](../../../specs/expression_evaluation.md#mixed-canonical-numeric-ordering--2026-10-19)
selects int-to-float conversion before comparison: rounding near the float precision
boundary is observable, NaNs make every ordering false, and signed zeros compare
equally. Each left snapshot/extraction/conversion precedes RHS evaluation; unavailable
carriers throw and results are plain bool. Equality, identity and spaceship keep their
separate gates. Existing emission/runtime implement the approved floating-domain rule.

## Float-destination mixed compounds (C2W) — 2026-10-19

[The directional C2 proof](planning/widening_compounds.md) verifies 96 carrier
signatures and four scalar prerequisites for float destination payloads with int RHS
payloads using `+= -= *= /=`. The shared floating-compound owner admits the existing
int-to-float operand conversion, followed by float computation and destination wrapping.
Old-left snapshots, ordered extraction and once-only write-back follow the existing
compound protocol. Integer zero divisors preserve `division_by_zero`; prior RHS effects
remain on failure. Ordinary assignment/parameter conversions are unchanged. Narrowing
int-destination/float-RHS, indexed, fixed-width and non-arithmetic forms stay separate.

## Cross-family matching-payload strict identity — 2026-10-19

[The E3I proof](planning/cross_identity.md) adds `===`/`!==` between distinct
nullable/Result/sentinel carriers with matching canonical int, float, bool or string
payloads. Preparation retains whole-state identity conversions; runtime normalization
compares source-visible values without guarded extraction or storage mutation.
Left-carrier normalization has dispatch precedence to avoid competing overloads.
Source operands remain eager left-to-right snapshots. Bool sentinels can match bool
payloads; null, structured errors and non-bool zero/empty payloads retain their kinds.
Ordinary cross-family equality remains separate; the E4I extension below admits
only canonical int/float mixed-payload identity.

## Mixed canonical numeric strict identity — 2026-10-19

[The E4I proof](planning/mixed_identity.md) adds four plain and 96 carrier signatures
for int/float `===`/`!==` in both orders. `Scalar_Identity` owns scalar identity
preparation and the shared canonical mixed-kind pair rule; each operand retains its
own type and identity conversion. Wrapper preparation snapshots full states with
no checked extraction. Present int/float payloads are never identical; matching
null/sentinel/error states still follow normalized identity. Ordinary mixed equality,
other heterogeneous payloads and float spaceship remain separate.

## Bool/numeric strict identity — 2026-10-19

[The E5BNI proof](planning/bool_numeric_identity.md) adds eight scalar and 192 carrier
signatures for bool/int and bool/float `===`/`!==` in both orders. The strict-identity
owner's distinct-kind rule covers canonical bool/int/float; it never performs truth
conversion or numeric promotion. Each operand retains whole-state snapshots.
Bool payloads can match numeric carriers' bool sentinel states, while present
numeric payloads remain distinct from bool values. Null and structured errors retain
their normalized kinds and metadata. Ordinary equality, strings, fixed-width types,
logical mixing and updates remain separate. No runtime changes were needed.
The E5SI extension below subsequently admits heterogeneous string identity.

## Heterogeneous string strict identity — 2026-10-20

[The E5SI proof](planning/string_other_identity.md) adds twelve scalar and 288
carrier signatures for string/int, string/float and string/bool `===`/`!==`
in both orders. `Scalar_Identity` owns the shared distinct-kind capability for
canonical int, float, bool and string; wrapper preparation reuses it. Each
operand keeps its original type and complete state, with no checked extraction,
string parsing, numeric promotion or truth conversion.

Successful string payloads differ from numeric/bool payloads. Matching normalized
null, bool sentinel and structured error states can still be identical. A string
carrier's bool sentinel can match a bool payload without changing its stored state.
The existing ordered snapshot renderer handles source effects and failures.
Ordinary equality, concatenation, logical mixing, spaceship, updates and
fixed-width/nested extensions remain separate. Runtime implementation is unchanged.

## Mixed numeric nullable/Result ordinary equality — 2026-10-20

[The E4NR proof](planning/mixed_equality.md) adds four scalar and 24 carrier
signatures for int/float `==`/`!=` in both orders. Scalar comparisons promote int
to float. Nullable and structured Result comparisons snapshot complete states;
the runtime promotes only present payloads. No checked extraction or carrier
conversion occurs. The shared wrapper owner separates ordinary pair admission
from strict identity and uses semantic carrier roles.

Two nullable absences or two Result failures compare equal; unavailable versus
present/plain compares unequal. Failure metadata matters only to strict identity.
Floating comparison preserves signed-zero equality, NaN inequality and int-to-float
rounding. Source order, RHS effects and saved-left snapshots use the existing
renderer. Sentinel carriers, cross-family pairs, other payloads and spaceship
remain separate. No runtime implementation changes were needed.

## Current contracts

The [2026-10-10 evaluation amendment](../../../specs/expression_evaluation.md)
supersedes historical eager-operand restrictions in this guide: eager binaries
evaluate and convert left, then right, into values before computation. The common
renderer owns sequencing, including power/xor/spaceship; lazy logical/selection
paths retain conditional evaluation. Fields, calls and other admitted expressions
use the same path. The [compound amendment](../../../specs/expression_evaluation.md#compound-updates--2026-10-10)
also admits ordinary RHS values, snapshots the old destination before RHS effects
and retains field owners until write-back. Existing-element indexed compounds now
use the [indexed-update contract](../../../specs/index_operator.md) and
[Chapter 06 proof](catalog/06_containers.md#existing-element-compound-updates--2026-10-10):
snapshot before RHS, then re-resolve the path before committing without recreating a
missing leaf. Fixed-width compound admission remains a separate gap.

| Source operation | Exact operand types | Result | Selected C++ lowering |
| --- | --- | --- | --- |
| `**` | canonical `int`, `int` | canonical `int` | Checked shared `scpp::pow` |
| `+`, `-`, `*`, `/`, `%` | same-signedness integers | Wider type; canonical `int` wins when present | Prepared common-domain conversions, existing runtime wrapper arithmetic |
| `==`, `!=`, `<`, `<=`, `>`, `>=` | same-signedness integers | canonical `bool` | Prepared common-domain conversions, existing runtime wrapper comparisons |
| `===`, `!==` | canonical `int`, `int` | canonical `bool` | `scpp::php::identical` / `not_identical` |
| `==`, `!=`, `<`, `<=`, `>`, `>=` | `string`, `string` | canonical `bool` | Ordered value snapshots, exact native byte comparison |
| `<=>` | `string`, `string` | canonical `int`, exactly -1/0/1 | Ordered value snapshots, lexicographic byte ordering |
| `===`, `!==` | `string`, `string` | canonical `bool` | Same strict helpers; byte-exact contents, no coercion |
| `===`, `!==` | `bool`, `bool` | canonical `bool` | Same strict helpers; equal boolean values, no truthiness conversion |
| `<=>` | same-signedness integers | canonical `int`, exactly -1/0/1 | Snapshot each converted operand once, compare with `<` and `>` |
| `.` | mixed, ordinary scalar or literal null | canonical `string` | Prepared text conversion then string-wrapper `+` |
| `&&`, `\|\|`, `and`, `or` | canonical `bool`, `bool`, or any canonical `int`/`float` pair | canonical `bool` | Convert each operand independently to native bool around native lazy operators, wrap result |
| `xor` | canonical `bool`, `bool` | canonical `bool` | Snapshot native bools left-to-right, compare with `!=`, wrap result |

Integer division truncates toward zero; remainder has the dividend's sign. Zero
raises the existing runtime errors `division_by_zero` / `modulo_by_zero`.
This follows [runtime arithmetic semantics](../../../runtime/specs/spec.md), not
standard PHP's division result type. Signed overflow and minimum-int divided by -1
retain the runtime's native C++ limitations; no wrapping or checked arithmetic is promised.

Concatenation now uses a separate text context for mixed, ordinary scalar and null
operands. The [separated-family amendment](../../../specs/dynamic_types.md#v02-separated-mixeddynamic-families--2026-10-05)
extends the boxed path to dynamic; configured nullable/fixed-width text admission
remains missing and Result text remains a contract question. It reuses
`php::to_string`, not explicit casts or checked string extraction. `.=` uses the same
text domain with string/mixed targets. See [chapter 05](catalog/05_dynamic_boundaries.md#text-concatenation-write-back--2026-10-04).
Strict comparison
helpers preserve the distinction from ordinary comparison, even though the current
exact canonical-int identity slice produces the same truth values. Ordinary numeric
comparison and arithmetic now follow the [2026-10-10 width contract](../../../specs/compact_layout_types.md#7-v02-integer-arithmetic-and-numeric-comparisons).
Signed/unsigned pairs reject; fixed-width compound updates remain deferred.

`&&`, `||`, `and` and `or` evaluate the RHS lazily; `xor` evaluates both sides
left-to-right. Both sides are still prepared and typechecked;
an invalid RHS is rejected even when a constant left side would skip it at runtime.
The explicit native-bool bridge follows runtime spec section 6.2 and avoids invoking
overloaded wrapper logical operators, which would evaluate both sides.

The imported spaceship card mentions `scpp::cmp`, which is absent from the current
runtime. The selected lowering instead uses an immediately invoked lambda with two
existing backend temporaries, initialized left-to-right, then compares their values.
It neither subtracts potentially extreme values nor returns a C++ ordering category.

## Grammar and ownership

Precedence from weakest to strongest is:

1. `or`
2. `xor`
3. `and`
4. `=`, `+=`, `-=`, `*=`, `/=`, `%=`, `.=` and bitwise/shift compound assignments
5. `||`
6. `&&`
7. `|`
8. `^`
9. `&`
10. `==`, `!=`, `===`, `!==`, `<=>`
11. `<`, `<=`, `>`, `>=`
12. `.`
13. `<<`, `>>`
14. `+`, `-`
15. `*`, `/`, `%`

Power `**` binds above prefix unary operators and associates right-to-left. Its
RHS accepts a unary expression, so signed exponents parse before domain checking.
Assignments associate right-to-left; the binary levels listed above otherwise associate left-to-right;
unsupported comparison chains fail the exact operand-type policy. Parentheses control the same binary tree without retained
grouping nodes. A read-only call lookahead distinguishes a constant followed by `<`
from a generic call. Longest-token recognition preserves strict comparisons, `<=>`,
`->`, floating exponents and leading-dot floats.

`Parser_Run` owns syntax; `Operator_Preparation` owns source normalization, operand
preparation and candidate dispatch. `Integer_Operators::decide_binary` owns the exact
integer pair policy. Bounded string/boolean candidates remain private preparation
methods. All use existing `operator_decision` records and two identity conversions
in `operator_operand` context. Only enum cases were added; no retained structures
or fields changed. C++ generation consumes the selected operation and conversions.

Existing `expression_statement_node` now accepts ordinary scalar expressions,
including `$a + 1;`, grouped expressions and concatenation. Plain assignment
statements use the same expression grammar; explicitly typed declarations remain
a separate statement form.

Calls, mutations and compound updates remain rejected as binary operands. Logical
operators also admit assignments to existing locals/parameters. Eager operators
inspect nested logical trees too, so casts and grouping cannot hide writes. Casts
and nested admitted binaries are otherwise supported. Eager runtime operand
error ordering is not newly specified; this slice does not introduce effect analysis.

## Legacy review and proof

Legacy recursive arithmetic/comparison lowering and strict identity helpers informed
the target forms. The important departures are bounded prepared operand types,
explicit string conversion, genuinely lazy logical lowering and the replacement of
the unavailable spaceship helper. Nested and chained catalog examples use the same
recursive implementation and need no special cases.

[Operator tests](../tests/operators.php) check decisions, result types, conversions,
precedence, rejected operand families, cleanup and incremental preparation.
[Tokenizer tests](../tests/tokenizer.php) cover punctuation boundaries.
[PHP-host S2S fixtures](../tests/s2s.php) check generation and syntax purity, with
additional C++ probes for exact values/types and runtime exceptions.

The 2026-10-01 batch compiled and executed 62 new generated programs/probes with
Clang C++20: division/remainder signs, truncation and zero guards; concatenated string
contents/types; integer comparison truth values and limits; exact spaceship -1/0/1;
logical truth tables, precedence and skipped/executed throwing RHS; nested/chained
expressions and expression statements. Focused tokenizer, grouping, operator,
conversion, AST, parse-collection, token-cleanup and incremental-C++ PHP tests pass.
The complete PHP-host S2S generation/purity suite also passes. This is generated
program execution, not native execution of the compiler. The earlier 105-fixture
[native compiler checkpoint](portability/conversion_review.md) predates these changes.
LLVM and legacy STAN remain deferred.

## Unary operators

Added 2026-10-02 following agreement on one `unary_expression_node`. It owns one
operand and the operator token location. Its prepared facts retain the existing
`operator_decision` with one identity operand conversion. Maintenance visits the
operand and remaps the operator token; inspection uses a lazy one-child iterator.
Preparation owns operand permission and selected operation; emission only renders
that selected operation through the existing runtime operators.

Unary `+`, `-`, `~` accept canonical `int` and return canonical `int`; `!` accepts
canonical `bool` and returns canonical `bool`. Results are values, not assignable
storage. Explicit casts can establish the required type. Other numeric widths,
floating operands and implicit truthiness remain rejected in this scalar slice.
Admitted effectful operands are now sequenced by their enclosing eager binary.

Prefix operators nest right-to-left and bind above ordinary binary levels,
but below exponentiation.
Grouping controls their operand: `-2 * 3` differs from `-(2 * 3)`. `++` and `--`
are distinct mutation tokens; repeated unary signs require
separation, as in `- -3`. Strict comparisons and arrow tokens retain longest matching.

The grammar keeps `(NAME) + expression` and `(NAME) - expression` as grouped-name
binary expressions. Casts of signed operands use explicit grouping, for example
`(int)(-2)`; `-(int)3.5` also works. This preserves syntax-only disambiguation of
casts from grouped constants without consulting symbol resolution. `!` and `~`
can follow a cast directly because they cannot be binary continuations.

Decimal literal magnitudes retain the existing signed-64-bit bound. Consequently
`-9223372036854775808` is rejected at literal preparation, while `-PHP_INT_MAX - 1`
and `~PHP_INT_MAX` produce the exact minimum integer. The runtime negates native
signed values; negating an already minimum-valued integer has no new overflow
guarantee. No constant folding or widened literal type was introduced.

Legacy `Generator::renderExpr` recursively emits parenthesized unary operations;
this slice keeps those target forms with explicit prepared type permission.
Focused operator tests verify decisions, result types, non-addressability, child
inspection, cleanup, grouping, rejections, incremental operator replacement and
fresh-output equivalence after token compaction. PHP-host S2S generation/purity and
19 generated C++ programs/probes cover values, nesting, casts, logical composition
and exact signed integer limits. Native compiler execution remains unverified for
this extension; LLVM is deferred.

## Increment and decrement

Added 2026-10-02 after agreement on a dedicated `mutation_expression_node`. It owns
one target expression, the operator token location and a postfix flag. A separate
prepared mutation record reuses `operator_decision`, with one identity operand
conversion and canonical `int` result. The target's existing prepared variable
reference owns resolved storage identity; mutation facts do not duplicate it.

`Mutation_Preparation` accepts only established canonical `int` locals or parameters.
It resolves the target through the normal read path, requires addressability, and
selects one of four integer operations through the shared operator decision entry.
Unlike plain assignment, mutation never introduces a declaration. Constants, literal
values, calls, cast targets, other numeric types, fields and indexes are rejected.
Grouping a variable does not change its target identity.

Prefix increment/decrement returns a snapshot of the updated value; postfix returns
a snapshot of the previous value. Results are not addressable or valid reference
arguments. Expression statements reuse these expressions and discard their results.
Postfix syntax binds tighter than prefix syntax; repeated mutations such as
`++$x++` are rejected because their target is another value-producing mutation.

C++ lowering uses the existing runtime `++`/`--` operators and explicitly copies
the result into the prepared integer result type, for example
`static_cast<scpp::int_t<>>(++local_x)`. The target occurs exactly once. This avoids
leaking the runtime prefix operator's reference result into the source value
contract. No mutation-specific lambda, runtime helper or semantic lookup in the
backend is needed. This refines legacy direct operator emission with an explicit
snapshot boundary. Runtime native signed overflow behavior remains unchanged;
proofs stay inside representable bounds.

Assignments and returns consume the snapshot normally. Existing call emission
already evaluates value arguments into temporaries left-to-right, so multiple
mutation arguments retain their individual values. Value parameters mutate only
the local copy; reference parameters update caller storage. Binary operators now
sequence mutations, including those beneath casts or unary expressions. Unary
preparation admits its single effectful operand without introducing new bindings.

[Mutation tests](../tests/mutations.php) verify selected operations/conversions,
non-addressability, spans, single-child inspection, cleanup, rejected targets/types,
reference-argument rejection, prefix/postfix incremental edits, token compaction and
fresh/incremental equivalence. The PHP-host [S2S suite](../tests/s2s.php) verifies
syntax purity and generates 20 mutation programs/probes for Clang C++20 execution:
all eight catalog forms, independent old/new values, later-update snapshots,
self-assignment, grouped targets, value/reference parameters, argument sequencing
and exact minimum/maximum representable results. Native execution of the compiler
was not rerun; LLVM remains deferred.

## Compound assignment

Added 2026-10-02 with the agreed `compound_assignment_expression_node`. It owns the
target, operator token and RHS. Attached facts retain the existing binary
`operator_decision` plus a `conversion_decision` in assignment context for write-back.
The result type is the target type; the result is a non-addressable value snapshot.

The original `+=`, `-=`, `*=`, `/=` and `%=` slice requires canonical `int` on both
sides; chapter 05 adds mixed numeric stored-kind updates. `.=` accepts string/mixed
targets and mixed, ordinary scalar or null right operands through text conversion.
Targets are existing locals or parameters. Target validation is shared with
increment/decrement through `Mutation_Preparation::prepare_target`; neither form
implicitly declares storage. Fields, indexes, calls/casts as targets, mixed widths
remain rejected. Text write-back can replace a mixed payload kind with string.

The parser recognizes the six compound tokens with longest matching and places
compound assignment below symbolic binary precedence and above keyword logic. It retains nested syntax
right-to-left, but preparation rejects effectful RHS expressions, including another
assignment, compound update, mutation or call. Pure nested binary/unary expressions
and explicit casts retain their existing permissions. A compound update itself
remains excluded from binary operands. Assignment initializers, returns and existing
sequenced value-argument handling can consume its snapshot; reference arguments cannot.

Preparation normalizes the compound token to its base binary kind, then uses the
same exact operand policy as ordinary computation. The backend's shared
`render_binary_decision` consumes operand conversions for both forms; it does not
rediscover source semantics. Compound emission binds the target once to a local
reference in an immediately invoked lambda, computes the result once, applies the
retained write-back conversion, assigns it, and returns a copy using the explicit
result type. RHS text appears once. Failed computation occurs before the store.

This gives `$x /= 0` and `$x %= 0` the existing runtime error codes while leaving
`$x` unchanged. Native integer truncation, signed remainder and overflow limitations
are inherited from the ordinary binary operators. String self-concatenation computes
before assignment, and a captured concatenation result remains independent of later
updates. No runtime helper was added. The local lambda introduces a target reference
but no heap allocation; compile-time cost is unbenchmarked.

Legacy emission uses direct arithmetic compound operators and expands concatenation
into assignment plus string addition. This slice instead shares prepared computation
and write-back uniformly, and requires explicit non-string conversion. It does not
inherit the legacy generator's broad coercion permissions.

[Compound tests](../tests/compound_assignments.php) prove target restrictions,
selected operations, operand/write-back conversions, result non-addressability,
child order, cleanup, rejection-before-publication and incremental/fresh equivalence.
[Tokenizer tests](../tests/tokenizer.php) cover all six tokens. PHP-host S2S generation
and syntax-purity checks pass; 26 generated Clang C++20 programs/probes cover all
seven catalog rows, precedence, updated-value snapshots, self-use, signed arithmetic,
value/reference parameters, argument sequencing, exact strings and zero-divisor
errors with unchanged caller storage. Existing generated fixtures retain identical
C++ after factoring the shared renderer. Native compiler execution was not rerun;
LLVM remains deferred.

## Bitwise and shift operators

Added 2026-10-02: canonical `int` pairs now support `&`, `|`, `^`, `<<`, `>>` and
compound forms `&=`, `|=`, `^=`, `<<=`, `>>=`. Result types remain canonical `int`.
Existing binary/compound nodes, operator decisions and write-back conversions are
reused; no AST structure or field was added. Non-int operands retain their type
restrictions; eager values and compound RHS now admit sequenced effects. Stable
property targets follow CH10 with owner retention across RHS evaluation.

The agreed [runtime contract](../../../runtime/specs/spec.md) delegates to native
C++20 integer behavior. Counts 0 through 63 are valid for the canonical signed
64-bit representation. Right shift sign-extends and rounds toward negative infinity:
`-3 >> 1` is `-2`. Left shift follows congruence modulo 2^64, so `1 << 63` is the
minimum signed integer and `PHP_INT_MAX << 1` is `-2`. This is the shift rule, not
a new wrapping guarantee for addition or multiplication. Negative counts and counts
at least 64 remain undefined behavior; no masking, runtime check or compile-time
rejection was added. See [C++20 N4861](https://timsong-cpp.github.io/cppwp/n4861/expr.shift)
and [checked-count debt](planning/operators.md#debt-checked-shift-counts).

The lexer retains individual `<` and `>` tokens so nested generic applications,
casts and calls keep their existing grammar. `token_list::operator_text_at` exposes
physically adjacent matching angle tokens as a shift spelling in expression context.
The parser consumes both, and preparation reads the same spelling from retained
tokens. Spaced `< <` or `> >` are not shifts. Compound `<<=`/`>>=` are longest-match
single tokens. Incremental body comparison includes source bytes, so inserting
whitespace between shift characters cannot reuse the previous valid expression.

Shift precedence is below addition and above concatenation. Bitwise precedence is
`&` above `^` above `|`, all below comparisons and above `&&`. Ordinary reference
parameter `&` and logical `&&`/`||` retain their grammar. Compound normalization
removes the trailing `=` instead of assuming a one-character base operator.

The existing runtime bitwise/shift helpers and legacy direct operator forms are
reused with prepared exact types. No runtime changes or special backend arithmetic
were introduced. Compound writes continue to use one target binding, one computation
and an updated-value snapshot.

[Operator tests](../tests/operators.php) cover selected operations/conversions and
operand rejections; [compound tests](../tests/compound_assignments.php) cover all five
new write-back decisions. [Bitwise tests](../tests/bitwise.php) cover retained
multi-token shift spellings, incremental binary/compound changes, compaction,
whitespace invalidation and recovery. Invalid counts are prepared/emitted only,
never executed. Grouping regressions retain nested generic parsing.

PHP-host S2S generation/purity passes. The 37 new Clang C++20 programs/probes cover
precedence/associativity, all five compound forms, snapshots/reference updates,
counts 0 and 63, negative operands, sign-bit changes, and exact native value/type
assertions. Prior generated fixtures are byte-identical. Native execution of the
compiler was not rerun; LLVM remains deferred.

## Assignment expressions and keyword logic

Added 2026-10-02: `and`, `or` and `xor` accept canonical bools. `and`/`or` share
prepared semantic operations with `&&`/`||`, but bind below assignment. For example,
with `$a` already declared, `$a = true and false;` stores `true`; `$a = true && false;`
stores `false`. Parentheses around the whole logical RHS select that logical result.
`(true) and ...` is grouping, not cast syntax. Legacy catalog cards reject the word
operators; v0.2 deliberately admits them under these explicit precedence/type rules.

The existing assignment node and `prepared_assignment` carry this behavior. A nested
assignment evaluates its RHS once, converts to the target type, stores once and returns
a non-addressable copy of the stored value. C++ uses an immediately invoked lambda
with an explicit value return type. Failed RHS computation leaves the target unchanged.
Return values, typed initializers, casts and sequenced value arguments can consume the
snapshot; reference arguments cannot. Assignment targets in these contexts must be
established writable storage. The original local/parameter-only bound is historical:
later property, container-write and indexed-compound contracts define their admitted
targets and lifetime/commit boundaries. This does not make indexed reads borrowable.

Standalone first assignments and their direct right-associative chains retain their
existing declaration behavior and statement-owned lowering. Grouping normalizes away,
so `$a = ($b = 3);` has the same chain as `$a = $b = 3;`. An invocation-local preparation
flag permits declarations only along that chain, never through a logical operand,
call argument, typed initializer or field base. Thus `$a = true and false;` requires
an already established `$a`, even though its left operand always executes. This slice
does not introduce branch-sensitive initialization or conditional declarations.

Logical operands are prepared left-to-right and both must be valid, including a
statically skipped RHS. `and`/`or` use native bool short circuiting. `xor` snapshots the
left bool, then the right bool, and compares those snapshots. This preserves results
when both operands write the same local. The later eager/compound amendments admit
ordinary value expressions in these positions, including writes to established
locals, calls and nested updates; they do not permit implicit-local declarations.
General effect analysis remains deferred.

[Logical tests](../tests/logical.php) cover AST precedence, assignment facts, grouped
write collection, diagnostics, cleanup, incremental edits, token compaction and failed
edit recovery. [S2S tests](../tests/s2s.php) preserve syntax/scopes during preparation
and generation. The 38 new Clang C++20 programs/probes cover the three catalog rows,
lazy writes and exceptions, XOR snapshots/order, assignment conversions, reference
parameter writes, sequenced arguments, single evaluation and failed stores. Existing
operator, mutation, compound, grouping, tokenizer and collection PHP checks pass.
Prior generated fixtures remain byte-identical. Native execution
of the compiler was not rerun; LLVM remains deferred.

## Integer exponentiation

Added 2026-10-02 under the [normative contract](../../../specs/integer_power.md):
canonical `int ** int -> int`, right-associative and above prefix unary precedence.
The existing binary AST and decision records are reused. The lexer recognizes `**`;
a dedicated parser step between unary and postfix parsing handles both `-2 ** 2`
and `2 ** -2`. Ordinary arithmetic effect restrictions remain in force.

`Integer_Operators` selects `integer_power`; C++ lowering consumes that decision,
includes `operators/arithmetic/power.hpp` and calls registered shared `scpp::pow`.
The runtime computes exact power by squaring, with bounded unsigned-magnitude
products checked before multiplication. This represents the magnitude of the signed
minimum safely. It skips unused final squaring, preserving `MAX ** 1` and `MIN ** 1`.
The loop takes logarithmic time in the exponent, including large exponents for 0/±1.

Zero exponent returns 1, including `0 ** 0`. Negative exponents throw
`power_negative_exponent`; out-of-range results throw `power_overflow`. Neither
failure uses floating-point approximation, promotion or signed-overflow behavior.
The imported legacy helper requirement is now fulfilled for canonical ints only;
legacy generator support is unchanged. Floating power and `**=` are deferred.

[Runtime tests](../../../tests/runtime/native/test_pow.cpp) pass with Clang C++20
undefined-behavior sanitization. They exercise both signed boundaries, exact and
out-of-range powers, huge exponents, stable error metadata, API type restrictions,
and an independent repeated-multiplication oracle for small inputs.
[Power tests](../tests/power.php) cover AST precedence, decisions, cleanup, incremental
header addition/removal, compaction and error recovery. PHP-host S2S generation and
syntax-purity checks pass, as do the affected operator/tokenizer/grouping/logical/
compound/mutation checks. All 25 new generated C++ programs/probes compile and run,
including negative-exponent/overflow errors and unchanged assignment storage on failure.
All 346 prior generated fixtures remain byte-identical. Native execution of the
compiler itself was not rerun; LLVM remains deferred.

## String interpolation

Added 2026-10-02 under the [scalar interpolation contract](../../../specs/string_interpolation.md).
Double-quoted `$name` and `{$name}` insert existing scalar locals/parameters using
the same conversion rules as explicit string casts. Single quotes and escaped dollars
remain literal. Fields, indexes, calls, writes and arbitrary expressions inside
insertions are deferred; explicit braces disambiguate literal suffixes.

The lexer keeps its complete string token. `Parser_Run` splits an interpolated token
into `interpolation_text_node` and `interpolation_value_node` parts owned by
`interpolated_string_node`. Each part retains its byte offset and length relative to
that token. Value parts own normal variable references and distinct collected read
occurrences, even for repeated names within one token. This preserves collection
order and source provenance without manufacturing tokens. Ordinary strings retain
their existing literal representation.

Preparation owns escape decoding and attached per-insertion conversion decisions.
The new `interpolation` conversion context admits the existing scalar string-cast
policy without widening assignment or concatenation. C++ emits a value-returning
lambda with one local string and sequential `append` statements. Each insertion is
read and converted once, and the returned string remains independent of later writes.
The existing runtime supplies all conversion, append and binary-safe literal behavior;
no new runtime API is introduced.

[Interpolation tests](../tests/interpolation.php) prove parts/children, source ranges,
read identities, conversion decisions, rejection before publication, recursive cleanup,
incremental edits, retained-body compaction and recovery from parser/semantic errors.
PHP-host S2S generation and syntax purity pass. All 20 new generated Clang C++20
programs pass exact byte comparisons covering scalar types, braced/unbraced names,
name boundaries, escapes, embedded NULs, UTF-8, self-reassignment, snapshots,
parameters, concatenation and compound concatenation. Focused grouping, operator,
tokenizer and collection PHP checks pass. All 371 prior generated fixtures remain
byte-identical. Native compiler execution was not rerun; LLVM remains deferred.

## General bracket operator

Added 2026-10-02: `base[expression]` and `base[]` share the
[normative bracket model](../../../specs/index_operator.md). The existing index node
now has an optional argument; absence means arity zero and carries no append/read
policy. Postfix parsing applies suffixes uniformly to primary expressions, including
call results and literals. Normal expression grammar handles a present argument.

`Operator_Preparation::prepare_index` owns both forms, preparing the receiver before
any explicit argument and entering the existing operator-decision boundary with
`operator_kind::index`. No overload candidate is implemented yet. String receivers
receive a specific deferred-contract diagnostic; other receivers receive a deferred
resolution diagnostic. Both report arity. Assignment and mutation target paths enter
that same preparation boundary. Result types, effects and writability await actual
overload contracts; no placeholder facts or C++ lowering are published.

[Indexing tests](../tests/indexing.php) prove both arities, ordinary expression
arguments, postfix bases, optional-child traversal, parser-only retention/compaction,
shared read/write/update diagnostics and failure recovery. Focused grouping, mutation,
compound, operator, interpolation and collection checks pass, as does PHP-host S2S
generation/purity. All 391 prior generated programs remain byte-identical.
There are no new generated bracket programs because lowering is
intentionally deferred. LLVM only gains an absent-argument guard on its parked
fixed-array path. Native compiler validation was deferred at implementation time;
the subsequent checkpoint below includes both bracket arities as rejection cases.

## Native chapter checkpoint

The subsequent 2026-10-02 native validation passes: 341 source fixtures produce
identical PHP/native C++, all generated base programs execute, 74 supplementary
instrumented programs pass, and 37 rejection/recovery cases agree. Type proofs,
15 float-spelling assertions and the final incremental native rebuild also pass.
Two portability-only source adaptations were required; see the
[checkpoint, timings and evidence](portability/conversion_review.md).
LLVM and legacy STAN remain skipped.

## Remaining decisions

The [preparation/emission audit](planning/preparation_emission_audit.md) confirms the
bounded scalar operation/conversion boundary. Bounded copy eligibility and source-record
capabilities are implemented; straight-line non-void fallthrough rejects in preparation.
The entry-return mismatch is resolved by
[registry normalization of redundant value modifiers](../../../specs/type_use_modifiers.md).
Duplicate declarations remain explicit PE-03 debt, alongside conditional runtime-template
capabilities. Chapter 02 is [concluded for this positive pass](catalog/02_expressions.md#positive-pass-conclusion--2026-10-02).
The latest repairs have focused PHP/generated-C++ proof; the native checkpoint above
predates them. Deferred work gates affected expansions without reopening the six
agreed chapter notes.

- Checked shift counts are [explicit debt](planning/operators.md#debt-checked-shift-counts).
  Field/index target capabilities and eager operands follow their later bounded
  contracts; lifted mutations and boxed bitwise admission remain gaps.
- Floating-point power, other carrier types and `**=` remain deferred.
- Broader scalar/carrier comparison and lifted-operator admission remain incomplete;
  see the [operator issue ledger](planning/language_verification_issues.json).
- String indexing remains explicitly deferred. Registered container reads/writes,
  indexed compounds and bounded interpolation have their own supported contracts;
  they are not globally deferred bracket/interpolation work.

These remain open under the user's requirement to discuss new structures. All six
imported prose notes are now reconciled and agreed.
The October 02 closeout was a bounded checkpoint. Current per-card reconciliations
distinguish implemented slices, missing configured admission and explicit deferrals;
it is not a whole-language or whole-operator completion claim.
