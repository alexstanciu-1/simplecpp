# V-L07 remaining signature inventory
Doc Status: planning

Snapshot: 2026-10-20, after mixed-numeric cross-family ordinary equality (E4). This is a concrete
planning list, not a new semantic specification or authorization to implement all
listed combinations. No compiler/runtime changes or compiler tests were run to
create it. Existing completed proofs remain authoritative evidence of execution.

[Remaining signatures](remaining.csv) lists **744 exact signatures**:
**zero remaining support targets** and **744 design/scope decisions**.
Each row identifies the operand types, operator, unary/binary/compound form, group,
planning status and matching matrix definition when one exists. An empty matrix
reference means no exact definition was found, not that the operation is invalid.

[All signatures](signatures.csv) also includes completed coverage and explicit
boundaries. [Summary](summary.json) contains counts, group descriptions and input
hashes; [matrix reconciliation](matrix_reconciliation.json) maps every existing
wrapper operator definition. Rebuild with `python3
compiler/my-try/docs/planning/vl07_inventory/build.py` from any directory. This is
local data processing only; it does not execute compiler tests or the operator matrix.

## Universe and counting

Payloads: canonical `int`, `float`, `bool`, `string`.
Carriers: `?T`, `result<T>`, `result_or_false<T>`, `result_or_bool<T>`.
A binary/compound pair uses any two of these carriers or one carrier and a plain
payload, in either order. Both-plain signatures belong to scalar prerequisites.
There are 24 ordered carrier/plain arrangements for each ordered payload pair:
16 carrier/carrier plus eight carrier/plain. Cross-family means distinct carriers,
not different payload types.

The grid enumerates 26 binary spellings, 12 compound spellings and eight unary/update
forms, plus comparisons with literal null in both orders. Word aliases are distinct
source spellings. Prefix/postfix are distinct. States, values and writable target
categories are proof dimensions, not additional type signatures. False/true sentinel
probes belong under the corresponding bool comparison signature; they must not be
mistaken for bool payloads.

| Disposition | Signatures | Meaning |
| --- | ---: | --- |
| Verified completed slices | 3,208 | 2,712 binary + 64 unary + 432 compound; reproduces current total |
| Remaining support targets | 0 | Missing capability families with bounded candidate signatures |
| Needs design/scope decision | 744 | Decide semantics/prerequisites before calling them implementation obligations |
| Separate existing paths | 129 | 128 null comparisons + preserved nullable-string negation; no new pass claim |
| Scope exclusions | 10,767 | Other grid cells outside approved payload/operator domains |
| Total grid | 14,848 | Includes negative/out-of-scope cells, not a completion denominator |

“Scope exclusion” does **not** mean all 10,767 cells are permanent language
incompatibilities or freshly proved rejections. X1/X2 record explicit bounded
exclusions; X3 follows the canonical-int power contract; X4 is the residual review
boundary. A future contract can move a row without losing its identity/history.

## Remaining support targets

All proposed-core support targets are now verified. The compiler admits 240 canonical-int
and 96 exact-float carrier compounds through independent extraction and existing stable
mutation/write-back paths. Four scalar float prerequisites are verified separately.
Indexed, narrowing and power compounds remain outside this completed core.
[C2W](../widening_compounds.md) separately adds 96 float-destination mixed compounds
plus four scalar prerequisites; it does not enable narrowing.

| Completed compound group | Signatures | Evidence |
| --- | ---: | --- |
| C1IA: same-carrier/plain int arithmetic | 48 | [Proof](../int_carrier_compounds.md) |
| C1IB: same-carrier/plain int remainder/bitwise/shift | 72 | [Proof](../int_bitwise_compounds.md) |
| C1IX: cross-family int | 120 | [Proof](../int_cross_compounds.md) |
| C1: all float carrier/plain arrangements | 96 | [Proof](../float_compounds.md) |
| Total | 336 | |

Matching-payload equality and numeric carrier updates are also complete within their
approved bounds. Cross-family bool ordinary equality still needs a state-policy decision.
[E3I](../cross_identity.md) verifies the 96 matching-payload cross-family strict identities.

## Decisions that must not be silently counted as fixes

| Group | Exact expansion | Count | Decision |
| --- | --- | ---: | --- |
| E3 | Two ordinary comparisons × matching bool × 12 ordered distinct-carrier pairs | 24 | Bool payload/sentinel state policy |
| E5 | Two ordinary comparisons × 10 ordered heterogeneous pairs × 24 arrangements | 480 | Ordinary coercion policy and state normalization |
| O2 | `<=>` on float/float and both int/float orders × 24 arrangements | 72 | Unordered NaN policy and scalar admission |
| C2 | Four arithmetic compounds × int-destination/float-RHS × 24 arrangements | 96 | Narrowing, error policy and destination write-back |
| C3 | `**=` on int/int × 24 arrangements | 24 | Power assignment is outside current power contract |
| S1 | `.` and `.=` on string/string × 24 arrangements | 48 | Concatenation conversion versus guarded extraction, empty nullable string and write-back |
| Total | | 744 | |

These groups are review candidates. Some may become implementations, some intentional
exclusions, and some separate issues. The list preserves that distinction rather
than presenting all rejected operations as regressions.

## Contract, matrix and test mapping

Semantic owners are `runtime/specs/spec.md` §§6.10/7a/9,
`runtime/specs/config.json` carrier lifting/compound rules,
`specs/expression_evaluation.md`, and `specs/integer_power.md`.
The matrix is a derived inventory, not higher authority than these contracts or
explicit v0.2 amendments. In particular, historical compound state expectations
must not override the current evaluation/write-back contract.

All **376** indexed wrapper operator definitions are accounted for:
232 map to exact grid rows; 144 conditional-selection definitions stay with their
separate owner. There are no wrapper unary definitions in that index. The matrix
also lacks distinct power, word aliases, XOR and spaceship items. Thus subtraction
of matrix rows alone cannot recover V-L07's remaining signature list.

| Group | Existing reusable proof | Missing probe |
| --- | --- | --- |
| E3/E4/E5 | `tests/wrapper_equality_fixture.php`, `tests/string_identity.py`, runtime strict identity tests | Group-specific state normalization oracle; eager evaluation, error precedence, mutation snapshots, retained recovery |
| C1/C2/C3 | `tests/int_carrier_compounds.py`, `tests/eager_binary.py`, expression-evaluation contract | Extend bounded carrier compound proof to remaining operators/families and agreed conversion rules |
| O2 | `tests/mixed_ordering.py`, `tests/guarded_spaceship.py` | Agree unordered spaceship behavior before extending scalar/carrier proofs |
| S1 | Existing scalar string and eager operand proofs | Explicit wrapper conversion/concat contract, state combinations and destination behavior |

Paths in this table are relative to `compiler/my-try/`. Completed signatures are
reconstructed from the bounded contracts in `docs/s2s_scalar_operators.md`; the
builder asserts their exact 2,712 binary / 64 unary / 432 compound totals. This is static reconciliation with dated
proofs, not another verification run. Missing probes should run on dev002 using the
shared runner once their semantics and implementation scope are approved.

## Beyond this finite universe

These are explicit scope decisions, not forgotten zero-count tasks:

- Fixed-width integer/float payloads: define which widths, mixed-width conversion
  rules and operators belong to V-L07 before expansion. Canonical proofs do not cover them.
- Nested wrappers and object/container payloads: recursive type expansion is not a
  finite denominator; agree depth and admitted payload capabilities first.
- `mixed`, `dynamic`, tables and pointer/object identity: separate semantic owners
  and existing issues; table semantics remain separate as requested.
- Conditions, ternary/elvis/coalesce, presence and `take`: separate contracts and
  ledgers. Null comparisons are listed as a reconciliation boundary, not new work.
- Explicit error-sentinel comparison syntax: `result<T>` failure equality is covered
  by completed E2R; comparison against an `error` value needs a distinct source-contract review.
- References, properties and indexed writable targets: acceptance dimensions for
  each admitted update signature; do not multiply type-signature totals by lvalue kind.

## Using this list for progress

The original proposed core remains **2,080/2,080 signatures (100%)**.
[O1 mixed ordering](../mixed_ordering.md) adds 192 approved signatures beyond that
original denominator; C2W, E3I and E4I add 96 each, E5BNI adds 192, E5SI adds 288, E4NR and E4S add 24 each, E3NB adds 72 and E4 adds 48, bringing verified coverage to 3,208.
Eight scalar ordering, eight scalar float-compound, twenty-four heterogeneous strict-identity, four mixed numeric ordinary-equality and four plain numeric-XOR prerequisites are excluded
from that carrier inventory. This is a **bounded core-scope metric**, not
completion of all V-L07 or engineering effort. The 744 design candidates and wider
domains remain visible outside that denominator until disposition is agreed.

E1 completed with 12 signatures on dev002; see [the report](../nullable_float_equality.md).
The initial inventory recorded 1,556 completed / 524 support targets; E1 moves 12
from remaining to verified without changing the 2,080 proposed core denominator.

E2R completed 48 structured-error Result signatures; see [the report](../result_equality.md).
E2FB completed 12 false-sentinel bool signatures; see [the report](../false_bool_equality.md).
E2BB completed 12 two-sentinel bool signatures; see [the report](../bool_sentinel_equality.md).
E2NB completed 48 non-bool signatures; see [the report](../nonbool_sentinel_equality.md).
E2 completed the final 24 wrapper/plain ordinary equality signatures; see
[the repair](../sentinel_plain_equality.md). The original 24 native failures remain
in the earlier evidence archive; all 24 now compile and execute with sentinel/plain
non-bool equality false. No matching-payload E2 support target remains.

U1 completed 32 carrier updates and V-R10 is repaired; see [the proof](../carrier_updates.md).
The compound core is complete. The next discussion should choose among the explicit
design candidates or wider-domain prerequisites; none is automatically authorized.

## E3 strict-identity prerequisite and repair — 2026-10-19

[Historical probes](../cross_identity_runtime.md) found ambiguous overloads for all
96 signatures, with 32 same-family controls compiling. The subsequently authorized
[runtime dispatch repair and compiler proof](../cross_identity.md) complete those
96 signatures. Historical failure evidence is preserved; ordinary equality and
mixed payloads remain separate.

## E4 mixed numeric strict identity — 2026-10-19

[The mixed-identity proof](../mixed_identity.md) verifies 96 carrier signatures plus
four scalar prerequisites without numeric promotion or runtime changes. Present
int/float values remain distinct; matching normalized states can compare identical.
Ordinary mixed equality remains a separate 96-signature decision.

## E5 bool/numeric strict identity — 2026-10-19

[The bool/numeric identity proof](../bool_numeric_identity.md) verifies 192 carrier
signatures plus eight scalar prerequisites. Each operand normalizes independently,
so bool payloads can match bool sentinel states without numeric truth conversion.
Runtime code is unchanged. Ordinary equality, strings and logical mixing remain
separate; none is silently counted as an implementation obligation.

## E5 heterogeneous string strict identity — 2026-10-20

[The string identity proof](../string_other_identity.md) verifies 288 carrier
signatures plus twelve scalar prerequisites for string versus int/float/bool in
both orders. Successful payload kinds stay distinct; matching null, bool sentinel
and structured error states normalize independently. Source order, complete byte
storage, snapshots and recovery pass on dev002 without runtime changes. E5 now
contains only the 480 remaining heterogeneous ordinary-equality decisions.

## E4 nullable/Result mixed ordinary equality — 2026-10-20

[The E4NR proof](../mixed_equality.md) verifies 24 carrier signatures and four scalar
prerequisites. Present payloads compare with int-to-float promotion; two nullable
absences or two Result failures compare equal. Error metadata remains significant
for strict identity only. Whole-state snapshots, eager source effects, storage
preservation and retained recovery pass on dev002 without runtime changes.
At that checkpoint E4 retained 72 sentinel-carrier/cross-family signatures; E4S below reduces this to 48.

## E4 sentinel mixed ordinary equality — 2026-10-20

[The E4S proof](../sentinel_mixed_equality.md) verifies 24 carrier signatures and
reruns four existing scalar controls. Matching sentinels compare equal; distinct
sentinels and sentinel/payload pairs are unequal. Present numeric values compare
by int-to-float promotion. A bounded runtime generator repair admits mixed
canonical numeric sentinel/plain comparisons while preserving existing bool,
string, fixed-width and boxed behavior. State preservation, eager snapshots and
recovery pass on dev002. At that checkpoint E4 retained 48 cross-family mixed ordinary comparisons; the E4 proof below completes them.

## E3 matching non-bool cross-family ordinary equality — 2026-10-20

[The E3NB proof](../cross_equality.md) verifies 72 cross-family `==`/`!=`
signatures for matching int, float and string payloads. Present values compare
by scalar equality; matching false sentinels compare equal across sentinel
families. Distinct unavailable states and unavailable/payload pairs are unequal.
A bounded generated runtime path resolves competing carrier overloads using
explicit family/state classification. Whole states, eager effects and snapshots
are preserved. At that checkpoint E3 retained 24 bool comparisons and E4 retained 48 mixed-numeric
cross-family comparisons. E4 is completed below; bool comparisons remain separate.

## E4 cross-family mixed numeric ordinary equality — 2026-10-20

[The E4 proof](../cross_mixed_equality.md) verifies the last 48 canonical mixed
int/float carrier ordinary-equality signatures. E3 state comparison is unchanged;
present payloads use int-to-float equality. The runtime pair predicate shares
its canonical numeric/matching-string domain with sentinel/plain dispatch.
Source snapshots, error metadata, numeric representations and effects are preserved.
There are no new scalar prerequisites. E4 has no remaining signatures; cross-family
bool comparisons remain the separate 24-signature E3 decision.
