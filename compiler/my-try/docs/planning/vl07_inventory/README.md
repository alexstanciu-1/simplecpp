# V-L07 canonical-carrier signature inventory
Doc Status: supporting

Snapshot: 2026-10-20, after the [approved completion goal](../vl07_completion.md).
The finite canonical-carrier scope is complete. This does not claim complete
language/operator coverage or support for every writable target.

[Remaining signatures](remaining.csv) has **zero unresolved canonical candidates**.
[All signatures](signatures.csv) preserves all 14,848 grid identities;
[summary](summary.json) and [matrix reconciliation](matrix_reconciliation.json)
retain exact counts and the mapping to existing matrix definitions. Rebuild using
`python3 compiler/my-try/docs/planning/vl07_inventory/build.py`. The builder performs
local data reconciliation only; it does not run compiler tests or the full matrix.

## Counts and meaning

| Disposition | Signatures | Meaning |
| --- | ---: | --- |
| Verified completed slices | 3,376 | 2,832 binary + 64 unary + 480 compound, across 37 slices |
| Intentional rejection | 576 | E5 heterogeneous ordinary equality 480; C2 implicit narrowing compounds 96 |
| Verified existing paths | 17 | 16 nullable strict-null comparisons; nullable-string negation |
| Observed existing-path rejection | 112 | Other literal-null comparisons; separate presence-policy boundary |
| Unresolved canonical candidates | 0 | All 744 prior design candidates now have an agreed disposition |
| Wider scope exclusions | 10,767 | Outside the approved finite payload/operator domain |
| Total grid | 14,848 | Includes negative and out-of-scope cells; not a support percentage denominator |

The completion adds E3 bool equality 24, O2 numeric spaceship 72, C3 integer power
compounds 24 and S1 string concatenation/assignment 48: **168 carrier signatures**.
Three scalar spaceship and one scalar power prerequisite, plus two scalar text
controls, are tested separately. The 576 exclusions have exact preparation rejection
and retained edit/recovery proofs. They are not counted as implementations.

The old label “129 separate existing paths” did not prove admission. Reconciliation
now establishes 17 supported operations and 112 rejections. Ordinary nullable/null
comparison and non-nullable Result/sentinel null comparison remain with the presence
contract; no semantics for those 112 rows were invented during this goal.

## Universe

Canonical payloads: `int`, `float`, `bool`, `string`. Carriers: `?T`, `result<T>`,
`result_or_false<T>`, `result_or_bool<T>`. For each ordered payload pair there are
24 ordered arrangements with at least one carrier: 16 carrier/carrier plus eight
carrier/plain. Both-plain signatures are scalar prerequisites/controls.

The grid enumerates 26 binary spellings, 12 compound spellings, eight unary/update
forms and literal-null comparisons in both orders. Word aliases and prefix/postfix
are distinct spellings. Values, states and writable targets are proof dimensions,
not additional type signatures. Sentinel literals are not wrapped bool payloads.

## Proof and authority

Top-level `specs/expression_evaluation.md` and `specs/integer_power.md` own source
semantics; runtime spec/config and the runtime generator/helper implement their
bounded contracts. The matrix is a derived inventory, not semantic authority.
[Completion evidence](../vl07_completion_results.json) records exact new signatures,
rejections, existing-path dispositions and focused dev002 regressions. Earlier
reports below remain historical evidence; their counts are dated checkpoints.

## Wider gaps and owners

| Boundary | Disposition / owner |
| --- | --- |
| Indexed `++`/`--` | Deferred stable-place support in `Mutation_Preparation` / `Indexing_Preparation`; scalar and carrier probes reject |
| Guarded numeric indexed compounds | Deferred read/extract/write-back integration in the same owners; not implied by 480 stable-target compound signatures |
| Indexed float compounds | Explicit existing preparation gate; separate indexing slice |
| Indexed text compounds | Existing protocol verified for scalar string and all four string carriers; bounded state/byte proof, not all nested paths |
| Other null comparisons | 112 observed rejections; `Presence_Operators` and source presence contract need separate review before any expansion |
| Fixed-width and nested wrappers | Separate capability/conversion policy; canonical proofs do not cover them |
| Object/container payloads | Separate payload-capability and lifetime owners |
| `mixed`, `dynamic`, tables | Boxed/table owners and existing issues; table semantics remain separate |
| Selection, conditions, presence, `take` | Their own contracts/checklists; operator closure does not close these families |
| Explicit error-sentinel comparison syntax | Separate source-contract review; existing Result failure-state equality is already verified |

The 10,767 wider grid cells are neither a blanket permanent prohibition nor freshly
verified rejections. X1/X2 preserve bounded exclusions, X3 follows canonical integer
power, and X4 remains a residual review boundary. No full matrix rerun occurred.

The original proposed core remains **2,080/2,080 (100%)**. All subsequently agreed
canonical candidates are now disposed, with 3,376 supported carrier signatures.
This is bounded scope completion, not a percentage of all language capabilities or
engineering effort.

## Historical checkpoints

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
