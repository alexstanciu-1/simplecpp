# Expression evaluation — v0.2
Doc Status: normative

Agreed 2026-10-10. This amendment defines eager binary operand sequencing for
Simple C++ v0.2. It supersedes the previous order-independent operand restriction
and unspecified ordering of operand failures, including integer exponentiation.
It is a stronger guarantee than the general expression-order rules of PHP or C++;
it does not promise that the legacy generator implements this amendment.

## Eager binary operations

For a supported eager binary operation:

1. Evaluate the left operand once and complete its selected operand conversion.
2. Preserve that converted value, then evaluate the right operand once and complete
   its selected operand conversion.
3. Perform the selected operation on those two values.

A failure stops subsequent evaluation, conversion and computation. Earlier effects
are not rolled back. If the operation itself fails, both operands have already
completed. A right-side write cannot retroactively change the left scalar value.
Snapshots obey ordinary type semantics, not recursive deep-copy semantics: copying
a shared container value preserves its shared identity.

The rule applies uniformly to admitted arithmetic, power, bitwise/shift, comparison,
strict identity, concatenation and eager logical xor. It does not add operand types,
numeric promotions, overloaded operators or new assignment targets. Ordinary
expression admission still applies; operands cannot introduce implicit locals.
Presence comparisons against literal null observe the other operand once without
copying or consuming its payload or ownership handle.

Grouping and associativity still determine the expression tree. For example,
`a() + b() * c()` evaluates a, b, c in that order, computes the product, then adds.
Power remains right-associative, without reversing operand evaluation order.

## Lazy expressions and other constructs

Logical and/or evaluate the left condition first and evaluate the right only if
needed. Ternary, Elvis and coalescing retain their existing branch-selection rules.
Placing them inside an eager operand does not evaluate skipped branches. Both
operands/branches remain subject to compile-time validation even when unreachable
under a constant condition.

Assignment, calls, construction, indexing and removal retain
their separately documented receiver/argument/key/store contracts. This amendment
does not change their admission rules.

## Compound updates — 2026-10-10

For an admitted compound assignment, resolve the writable destination once and
retain any required object owners. Read and preserve its old value, including
the selected left operand conversion, before evaluating the RHS once. Compute
with those values, complete the write-back conversion/check, then store into the
original destination. The expression returns a value snapshot of the updated
destination, not a reference. A destination/null-owner failure precedes RHS effects.

For example, if `$x` is 10 and `change($x)` sets it to 100 and returns 3,
`$x += change($x)` stores 13. On RHS/computation/conversion failure, no compound
write-back occurs; earlier RHS effects remain. Numeric mixed/dynamic stored-kind
validation uses the preserved left value's kind, not a kind substituted by the
RHS. Existing kind-change errors remain; text concatenation keeps its text contract.

Ordinary admitted value expressions, including fields, calls, nested updates and
lazy expressions, may be RHS operands. They may not introduce implicit locals.
Indexed destinations follow the existing-element path contract in
[array semantics](array_semantics.md#v02-indexed-compound-updates).
Fixed-width compound updates remain deferred.

The AST and prepared facts retain compound-assignment intent and its selected
operator/conversions. Direct C++ `+=` is permitted only when equivalent ordering,
conversion and lifetime are established; it is not the general lowering contract.

## Lowering

The backend must enforce the source order rather than inherit native C++ operator
or function-argument ordering. Ordered converted value temporaries are sufficient.
Temporary storage and helper code may be eliminated only when observable behavior,
including conversion failures, effects and lifetime, is preserved. The requirement
does not itself mandate heap allocation or a runtime-dispatched operator path.

## Canonical numeric logical XOR — 2026-10-19

Logical `xor` admits canonical int/int, float/float, int/float and float/int.
Each operand independently converts to bool: numeric zero is false, including
both signs of floating zero; nonzero values, infinities and NaN are true. There is
no common arithmetic promotion or conversion from float to int. The result is
plain bool, true exactly when the converted truth values differ.

Evaluation is eager and ordered: evaluate and complete the left truth conversion,
save that bool value, then evaluate and complete the right truth conversion.
Either left truth value reaches the RHS; a failure stops subsequent work and
preserves earlier effects. XOR retains its lower-than-assignment precedence.

The same four payload pairings apply with nullable, result, result_or_false and
result_or_bool carriers in either operand, including distinct carrier families.
Each reached carrier must first supply its payload through checked extraction;
missing/error/sentinel states retain their existing extraction failures. Both
boolean sentinel states are unavailable payloads. This is independent of direct
wrapper condition/presence semantics. Bool/numeric mixing, strings, fixed-width
expansion, equality and writable updates are not admitted by this amendment.


## Canonical numeric carrier updates — 2026-10-19

Prefix/postfix `++` and `--` admit nullable, result, result_or_false and result_or_bool
with canonical int or float payloads on established writable destinations. Resolve
the destination once, require its present/success payload, then update that payload
once using scalar numeric mutation. The carrier remains present/successful.
Empty/error/sentinel states raise their existing unwrap failure before mutation,
preserving the entire destination state, including structured error details.

The source result is a carrier value snapshot: prefix returns the updated value;
postfix returns the old value. Neither result is addressable or aliases storage.
Later replacement of the destination cannot alter either snapshot. Earlier successful
updates remain visible if a later expression fails. Native runtime prefix may return
the original carrier by reference, but lowering must copy its result at the source
expression boundary. Native postfix returns an independent old carrier value.

This bounded admission includes existing locals, reference parameters and admitted
instance/static properties. It does not admit indexed updates, compounds, fixed-width
or nested payloads, new writable-target forms, or checked integer overflow semantics.


## Mixed canonical numeric ordering — 2026-10-19

For `<`, `<=`, `>` and `>=` between canonical int and float, in either order,
convert the int operand to canonical float and then perform floating comparison.
This rule applies to plain operands and admitted scalar carriers after checked
payload extraction. Each operand's snapshot, extraction and conversion completes
left-to-right before computation; a left extraction failure suppresses RHS evaluation.
The result is plain bool. Absent/error/sentinel carriers fail extraction, rather than
normalizing to zero or ordering their states.

Integer-to-float rounding is observable. For example,
`9007199254740993 > 9007199254740992.0` is false after rounding the integer to float;
`<=` is true for that pair. This is floating-domain ordering, not an exact
mathematical comparison of the original integer and float. Comparisons involving
NaN are false for all four operators; signed zeros compare equally, and infinities
follow floating ordering. No rounding-mode controls are introduced.

This amendment does not admit mixed equality/identity, float/mixed spaceship,
bool/numeric ordering or additional fixed-width domains. Their contracts remain
separate. It introduces no runtime implementation changes.


## Float-destination mixed compounds — 2026-10-19

For admitted `+=`, `-=`, `*=` and `/=` with a canonical float destination payload
and canonical int RHS payload, convert the extracted int RHS to canonical float
before computation. Plain and scalar-carrier operands use the same directional
rule. Compute a float, finish conversion/wrapping to the destination carrier, then
write once into the retained original destination. The expression returns an
independent destination-typed value snapshot.

The compound ordering above remains unchanged: stabilize destination and snapshot/
extract its old float value before evaluating RHS; evaluate, extract and convert
RHS before computation and write-back. RHS effects remain if a later operation
fails. Integer zero divisors throw `division_by_zero`, including NaN or infinite
left payloads. Other arithmetic follows the existing float contract.

Integer-to-float rounding is observable: a float destination initially 0.0 updated
with `+= 9007199254740993` stores 9007199254740992.0. No exact-integer arithmetic,
narrowing or new boundary-conversion rule is introduced. This does not admit
int-destination/float-RHS compounds, indexed destinations, fixed-width payloads or
non-arithmetic mixed compounds. Ordinary assignment and parameter conversions are
unchanged; operator promotion does not grant implicit conversion at those boundaries.


## Cross-family matching-payload strict identity — 2026-10-19

`===` and `!==` admit distinct nullable, result, result_or_false and result_or_bool
carriers with matching canonical int, float, bool or string payloads. The result is
plain bool. Snapshot the complete left operand, then the complete right operand,
then compare normalized source-visible values using the shared strict-identity
contract. Absent/error/sentinel states are compared without checked extraction.
An unequal or unavailable left state does not suppress RHS evaluation; a source
exception does. RHS mutation cannot change the saved left state.

Successful carriers compare by scalar identity regardless of family. Empty nullable
represents null; false/true sentinels represent their bool values; structured Result
errors retain their structured identity. Thus a false sentinel can be identical to
a successful bool false payload, but not integer zero or an empty string. Comparison
does not alter either stored state. Float signed zeros compare identically, NaN is
non-identical even to itself, and strings retain complete byte identity. `!==`
negates `===`. Ordinary cross-family equality and mixed-payload admission remain
separate; this amendment does not broaden those domains.


## Mixed canonical numeric strict identity — 2026-10-19

`===` and `!==` admit canonical int/float in both operand orders, plain or carried
by nullable, result, result_or_false and result_or_bool. Preserve each original
numeric kind: successful int and float payloads are never identical, even when
numerically equal. No integer-to-floating promotion or checked payload extraction
occurs. Ordinary mixed numeric equality is not admitted by this amendment.

Carriers still compare normalized visible states: two empty nullable values are
identical; matching bool sentinels are identical; structured Result errors compare
all their identity metadata. Null, bool sentinel, error and numeric payload kinds
remain distinct. `!==` negates `===`; both return plain bool. Full left and right
states are eagerly snapshotted in source order before comparison. A source failure
stops later evaluation, but an unequal or unavailable state does not. Runtime
comparison does not mutate either state. The source snapshot and exception rules
above apply equally to scalar prerequisites and carrier forms.

Bool/numeric, string/numeric, fixed-width extensions, nested payloads, ordinary
mixed equality and floating spaceship remain separate admission decisions.


## Bool/numeric strict identity — 2026-10-19

`===` and `!==` admit canonical bool/int and bool/float in both orders, plain or
carried by nullable, result, result_or_false and result_or_bool. Normalize each
operand independently under the shared strict-identity contract. Successful numeric
payloads never match bool payloads: false differs from integer or floating zero,
and true differs from any nonzero number, infinity or NaN. No truth conversion or
numeric promotion occurs.

A bool payload can match a numeric carrier's bool sentinel: false matches a false
sentinel, and true matches a true sentinel. This compares visible values without
changing stored state or confusing a numeric payload with a sentinel. Empty
nullable remains null; structured Result errors compare complete identity metadata.
Matching null, bool sentinel and error states follow the existing normalization
rules independently of declared payload kinds. `!==` negates `===`.

Both complete operand values are eagerly snapshotted left-to-right. Unequal or
unavailable states do not suppress RHS evaluation; source failures do. RHS effects
remain on failure, and RHS replacement cannot alter the saved left state. Ordinary
bool/numeric equality, logical mixing, strings, fixed-width/nested extensions and
updates remain separate; this amendment admits strict identity only.


## Heterogeneous string strict identity — 2026-10-20

`===` and `!==` admit canonical string/int, string/float and string/bool in both
orders, plain or carried by nullable, result, result_or_false and result_or_bool.
Each operand normalizes independently under the shared strict-identity contract.
Successful string payloads never match numeric or bool payloads, including empty
and numeric-looking strings. No string parsing, truth conversion, numeric promotion
or checked extraction occurs.

Matching null states, bool sentinels and structured Result errors can compare
identically across different declared payload kinds. A string carrier's false/true
sentinel can match a successful bool payload; its successful string payload cannot.
Stored states remain distinct and unmodified, including complete string bytes,
numeric representations and error identity metadata. `!==` negates `===`; both
return plain bool.

Complete operands are eagerly snapshotted left-to-right. An unequal or unavailable
state still evaluates RHS; a source exception stops later evaluation. RHS
replacement cannot alter the saved left state, and earlier RHS effects survive a
later failure. This amendment admits strict identity only. Ordinary heterogeneous
equality, concatenation, logical mixing, spaceship, updates, fixed-width types and
nested payloads retain their separate admission boundaries.


## Mixed numeric ordinary equality for nullable and Result — 2026-10-20

`==` and `!=` admit canonical int/float in both orders for plain scalars,
nullable/nullable, nullable/plain, plain/nullable, result/result, result/plain and
plain/result. Here result is the structured-error carrier. Both operators return
plain bool. This does not admit cross-family or bool-sentinel carrier combinations.

Compare present payloads by converting int to float and using floating numeric
equality, including its rounding at large integer magnitudes. Signed zeros compare
equal; NaN compares unequal even to itself. `!=` negates `==`. This is distinct
from strict identity, where successful int and float payloads remain different kinds.

Two empty nullable values compare equal; empty versus present/plain compares
unequal. Two Result failures compare equal regardless of error metadata; failure
versus success/plain compares unequal. These ordinary equality rules preserve
the existing state policy across payload kinds. Strict Result identity continues
to compare structured error contents.

Snapshot complete carrier operands eagerly from left to right, then compare their
states. Only present payload comparisons perform numeric promotion; no guarded
payload extraction or carrier-to-carrier conversion is inserted. Plain scalar
operands use the existing numeric conversion path. Unavailable or unequal states
do not skip RHS evaluation; source exceptions do. RHS replacement cannot alter the
saved left value, and earlier RHS effects survive a later source failure. Comparison
does not change stored states, numeric representations or error details.

Sentinel carriers, cross-family equality, other payload kinds, spaceship, updates
and fixed-width/nested extensions remain separate.


## Mixed numeric ordinary equality for sentinel carriers — 2026-10-20

`==` and `!=` also admit canonical int/float payload pairs in both orders for
same-family result_or_false or result_or_bool carriers, and either carrier versus
a plain numeric operand. This does not admit cross-family ordinary equality.

Present payloads compare with int-to-float numeric equality, including rounding,
signed-zero equality and NaN inequality. Matching sentinel states compare equal;
different sentinel states compare unequal. Either sentinel is unequal to every
present numeric payload or plain number, including zero and one. No bool-to-number
truth conversion or absent-payload extraction occurs. `!=` negates `==`.

Complete operands are eagerly snapshotted left-to-right before comparison.
Unavailable or unequal states do not skip RHS; source exceptions do. Preserve
saved-left snapshots across RHS replacement and retain earlier RHS effects when a
later source operation fails. Comparison preserves the stored state and payload
representation. Strict identity, bool payloads, matching-payload strings, fixed-width
and boxed behavior are unchanged. Other payload pairs, cross-family equality,
spaceship and updates remain separate.


## Cross-family matching non-bool ordinary equality — 2026-10-20

`==` and `!=` admit distinct pairs of nullable, structured Result, false-sentinel
and bool-sentinel carriers with matching canonical int, float or string payloads.
The result is plain bool. Both present payloads compare by scalar equality:
float NaN is unequal, signed zeros equal, and strings compare complete bytes.
Matching false sentinel states compare equal across the two sentinel families.
Different unavailable states (null, structured error, false, true) compare unequal,
as does any unavailable state versus a present payload, including zero or empty
string. `!=` negates `==`; no truth conversion or unavailable-payload extraction
occurs. Stored states and error metadata remain unchanged.

Evaluate and snapshot each complete operand left-to-right before comparing states.
Unavailable states do not suppress RHS evaluation. A source exception stops later
evaluation; earlier effects remain visible. RHS replacement cannot change the
saved left operand. Comparison performs no write-back.

This extension leaves matching-bool and mixed-numeric cross-family ordinary
comparisons separate. Same-family/plain contracts and strict identity are unchanged;
fixed-width, boxed, nested and other payload domains are not admitted here.


## Cross-family mixed numeric ordinary equality — 2026-10-20

`==` and `!=` also admit canonical int/float payload pairs in both orders across
distinct nullable, structured Result, false-sentinel and bool-sentinel families.
The result is plain bool. Both present payloads compare by int-to-float numeric
equality, including rounding near ±2^53 and int64 limits, signed-zero equality and
NaN inequality. No stored payload representation is converted in place.

The cross-family state rules remain unchanged: matching false sentinel states
compare equal; different unavailable states and unavailable/payload pairs compare
unequal, including sentinel versus zero or one. `!=` negates `==`. No truth
conversion or unavailable-payload extraction occurs; error metadata is preserved.

Complete operand snapshots are evaluated eagerly left-to-right. Unavailable states
do not skip the RHS. Source failures stop later evaluation, while earlier effects
remain visible. RHS replacement cannot alter the saved left operand. There is no
comparison write-back. Strict identity and existing same-family/plain equality
are unchanged. Cross-family bool, other heterogeneous payloads, fixed-width,
boxed, nested, spaceship and update domains remain separate.


## Canonical-carrier completion policies — 2026-10-20

Matching bool cross-family ordinary equality preserves storage states: two bool
payloads compare by value; matching false sentinels compare equal across sentinel
families; sentinel versus wrapped bool remains unequal. Different unavailable
states are unequal. Strict identity and carrier/plain bool rules remain unchanged.
Heterogeneous bool/numeric and string/bool/numeric ordinary equality is rejected
in preparation; explicit conversion is required. Int-destination/float-RHS numeric
compounds likewise reject implicit narrowing. No runtime coercion is inferred.

Floating and mixed canonical numeric `<=>` promote int to float and return canonical
int -1, 0 or 1. Either NaN operand throws `unordered_comparison`. Guarded extraction
and source failures preserve their existing left-to-right precedence. Signed zeros
compare equal; infinities order normally.

Canonical integer `**=` uses checked integer power and the existing compound
snapshot/extraction/write-back protocol. Negative exponent and overflow errors
prevent compound write-back while preserving earlier RHS effects. The returned
value is a snapshot of the updated destination.

String-carrier `.` and `.=` use text conversion: present strings preserve complete
bytes; empty nullable, false sentinels and both result_or_bool sentinel states
convert to empty string; structured Result failure throws. Convert the saved left
operand before evaluating/converting the RHS. On successful `.=` write-back the
original destination receives a string payload, including when it was previously
empty or a sentinel. Failed conversion/computation causes no compound write-back;
earlier source effects remain. Indexed and wider payload capabilities must be
verified through their existing owners and are not implied by these signatures.


## Canonical scalar float strict identity — 2026-10-20

Plain canonical float operands admit `===` and `!==`, returning bool under the
same numeric identity rule already used for successful float carrier payloads.
Equal finite values and matching infinities are identical; positive and negative
zero are identical. NaN is non-identical to every value, including itself, without
throwing. `!==` negates `===`. This is value identity, not floating bit-pattern
identity; observing identity does not change either operand's representation.

Evaluate and snapshot the complete left value, then the right value, before
comparison. A source exception stops subsequent evaluation; unequal values do not.
RHS writes cannot change the saved left operand. No numeric promotion is introduced:
int/float strict identity still preserves their distinct scalar kinds. The throwing
NaN rule for spaceship is a separate operation contract. Fixed-width, boxed and
other payload capabilities are not expanded by this amendment.

## Bounded scalar and carrier text contexts — 2026-10-20

For `.` and `.=` only, the ready plain integer text domain comprises canonical
int, signed 8/16/32/64-bit integers and unsigned 8/16/32-bit integers. Canonical
float, bool and string and literal null retain their scalar text rules. All pairs
in this domain, including the four carriers (`nullable`, `result`,
`result_or_false`, `result_or_bool`) of canonical int/float/bool/string, admit
concatenation and produce plain string.

A present carrier payload renders exactly as its corresponding plain scalar in
this text context. In particular, carrier float text uses the scalar text helper's
formatting, not the distinct explicit float-to-string cast formatting. Present
bool true produces `"1"` and false produces empty text. Empty nullable and both
boolean sentinel states produce empty text; a true sentinel is distinct from a
present true bool payload. Structured Result error throws during required payload
extraction, preserving its message, line and file. Strings preserve all bytes,
including embedded NUL and non-ASCII bytes.

Convert the saved left value before evaluating/converting the right value. A left
conversion failure suppresses RHS evaluation. A right failure preserves earlier
source effects and performs no compound write-back. `.=` admits string and the
four string-carrier destinations with any RHS in the bounded domain. Success
stores one string payload into the captured destination and returns its value
snapshot. Local, property and static destinations retain their established
receiver capture and once-only write-back rules.

This amendment adds no boxed/carrier combinations, indexed capabilities, uint64
text, fixed-width carrier payloads, nested carriers or object/container text.
Existing boxed and indexed text contracts remain separate. Assignment, argument
passing, equality, explicit casts, echo and interpolation do not acquire these
text-context conversions.

## v0.2 shared object identity — 2026-10-23

The frontend admits the existing [shared strict-identity contract](../runtime/specs/spec.md#7a-shared-strict-identity-semantics-with-php-adapter-exposure)
for `===` and `!==` on shared object handles, including optional class/interface
values and explicit shared struct handles. Payload types must match or have an
admitted base/derived relationship in either direction. Ancestry is checked during
preparation and observed for invalidation; the backend does not resolve relationships.
Unrelated views and shared/nonshared pairs reject with an identity-specific diagnostic.

Both operands retain their original type and absence state. Evaluation snapshots
left before right, exactly once. No checked nullable extraction, pointee copy,
structural field comparison or ownership transfer is introduced. Compatible absent
handles compare identical; a present handle differs from absence. Native identity
uses the adjusted compatible base pointer. Ordinary `==`/`!=`, weak observation and
unique-handle identity admission are not added by this slice. Explicit null comparisons
continue through the existing presence operation. Both native output modes use the
existing runtime helper; prelowering keeps templates in middleware.

## Uniform one-layer carrier arithmetic — 2026-10-27

For `+`, `-`, `*`, `/` and `%`, the payload-state carriers `nullable<T>`,
`result<T>`, `result_or_false<T>` and `result_or_bool<T>` participate uniformly:
project at most one carrier layer from each operand, then apply the ordinary
numeric operand admission, promotion and calculation rules. The other operand
may be a plain admitted numeric value or another admitted carrier. The result
is the ordinary numeric result, not a reconstructed carrier. This supersedes
older same-family-only arithmetic admission where it conflicts.

Payload types must qualify before execution; absence cannot rescue an invalid
payload combination. No implicit recursive peeling occurs for a payload such as
`nullable<int>` inside `result<nullable<int>>`. Boolean, null, string, boxed,
container and ownership payloads gain no arithmetic permission from this rule.
Existing signed/unsigned incompatibility and operation-specific restrictions
remain in force. Type formation is a separate requirement.

Evaluate the left operand once, obtain its checked owned payload, and perform
its selected numeric conversion before evaluating the right operand. Then obtain
and convert the right payload before calculating. A missing payload, structured
error or either boolean sentinel fails checked extraction; none is replaced by
zero, false or a propagated carrier. Failure preserves prior effects and prevents
later evaluation. Owned scalar extraction remains valid after carrier mutation or
cleanup. This rule does not add carrier compound/update, equality, identity,
condition, text or explicit-cast admission; those have separate state policies.

Implementation coverage is tracked in the runtime-core completion plan; this
contract does not claim that every frontend or carrier public recipe is migrated.
