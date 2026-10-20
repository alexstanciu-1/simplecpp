# V-L07 canonical-carrier completion
Doc Status: supporting

Completed and verified on dev002, 2026-10-20. The approved finite canonical-carrier
scope is complete: **3,376 supported signatures across 37 slices**, with **576
intentional rejections** and no unresolved canonical candidates. This does not
claim complete language support or all indexed/fixed-width/payload capabilities.

## What changed

| Group | Carrier signatures | Contract and implementation |
| --- | ---: | --- |
| E3 bool | 24 | Cross-family ordinary equality compares storage states; a sentinel is distinct from a wrapped bool payload. The generator's existing cross-carrier dispatch now admits matching bool payloads. |
| O2 | 72 | Float/float and both mixed numeric spaceship orders promote int to float and return -1/0/1. New scalar `scpp::numeric_compare` throws `unordered_comparison` for either NaN. |
| C3 | 24 | Canonical-int `**=` tokenization/parsing feeds the existing compound preparation and checked `scpp::pow` path. Negative exponents and overflow prevent compound write-back. |
| S1 | 48 | String-carrier `.` and `.=` use explicit string casts. Empty nullable, false sentinel and both bool sentinel states become empty text; Result failure throws. Successful write-back stores a string payload. |
| Total new support | 168 | 120 binary + 48 compound; no new unary signatures |

Three scalar spaceship and one scalar power prerequisite are verified separately;
two scalar text controls preserve existing behavior. E5's 480 heterogeneous ordinary
equalities and C2's 96 implicit narrowing compounds reject in preparation, with
retained recovery checks. Explicit conversion controls show supported alternatives;
nullable float must first unwrap as float before a separate int cast.

Semantics belong to `specs/expression_evaluation.md` and `specs/integer_power.md`.
Preparation publishes decisions; emission only spells the settled operation.
Existing conversion, extraction, target capture and write-back owners remain in
charge. No new retained protocol or wide architecture change was needed.

Text conversion deliberately uses `scpp::cast<string_t>` rather than the PHP display
adapter: the latter prints a true sentinel as `1` and a Result error as text, which
would violate the approved contract. The display adapter remains unchanged.
String-carrier concatenation is bounded to string/string-carrier operands.

## Verification and provenance

Host: `alex-ai@dev002.travelfuse.ro`, snapshot
`/home/alex-ai/vl07-completion/20261020-01`; starting revision `f4f350a3`.
PHP 8.5.11 CLI fallback (FPM version mismatch), Clang 18.1.3 and Python 3.12.3.
Shared runners use 12 refilled task slots per suite; independent suites overlap.
No full operator matrix or native compiler rebuild ran.

- [Results and exact signatures](vl07_completion_results.json): 174 new-operation
  source rows, comprising 168 carriers, four scalar prerequisites and two controls.
- [Evidence archive](vl07_completion_evidence.json.gz): 13,162 records, including
  command logs, summaries, launchers, diagnostics and failed attempts.
- 44 accepted suites account for 4,115 expected command exits and 10,305,306 recorded
  observations, including preservation regressions. Observation totals are not
  distinct signatures and include runtime/source/control proof dimensions.
- [Completion audit](vl07_completion_audit.json) checks exact inventory/evidence
  agreement and all 6,151 non-documentation source hashes locally.
- [Final tested source manifest](vl07_completion_source_final.json): 6,260 files,
  verified remotely with zero mismatches. Documentation closeout follows that
  snapshot; source code is checked against the same hashes before commit.
- [Initial source manifest](vl07_completion_source_initial.json) and
  [intermediate manifests](vl07_completion_source_phases.json.gz) preserve attempt
  provenance as reconstructible deltas against the initial manifest. The unused `runtime_prerequisites` draft is marked as not executed.
- Runtime generation ran twice and matched after normalizing only the existing
  timestamp line. The checked-in generated header matches the tested final hash.

The native observers check state/error metadata and float bit preservation, byte
strings including NUL/high bytes, int-to-float rounding, NaN/infinities/signed zero,
source exceptions, extraction precedence and RHS reachability. Local, property,
static and receiver-rebinding checks verify saved operands and the original target.
Successful compounds write back the computed payload; failures preserve earlier
RHS effects without compound write-back. Scalar power tests cover grouping,
right-associative compound parsing and postfix snapshots. Retained invalid edits
clear artifacts and recover to fresh/no-op output.

The final 150-row operation run passes; the final bool runtime/source run passes
all 24 targets plus same-family controls. All 27 prior regression families pass
after the explicitly recorded fixture repairs. Additional power/float/widening
compound, carrier-update, indexed-compound and spaceship suites pass.

## Historical failures retained

The initial bool runtime probe fails 12 cross-family pair compilations, blocking
24 signatures; eight same-family controls pass. The repaired dispatch passes both
runtime and source probes. Initial NaN comparison fallback returns zero and is
explicitly not acceptance; the checked helper passes the final runtime probe.

Early source attempts exposed the missing `**=` lexical/parser prerequisite and
fixture defects: lifecycle initialization, generic-type spacing and nullable-error
classification. A text observer initially caught only `scpp::runtime_error`, while
Result extraction throws `std::runtime_error`; the corrected observer preserves
that contract. Earlier regression fixtures expected newly admitted spaceship/power
to reject, or matched the superseded concatenation diagnostic. Only those stale
expectations changed. A missing staged inventory input and one wrong recheck script
name are retained as harness failures, not compiler defects. No failed run is
silently replaced by a green summary.

## Existing paths and wider gaps

The prior 129 “separate existing paths” label was not an admission claim. The audit
proves **17 supported paths** (16 nullable strict-null comparisons and nullable
string negation) and **112 preparation rejections**. The latter remain a separately
owned presence-policy boundary; this goal does not authorize new null semantics.

| Follow-up | Explicit disposition and owner |
| --- | --- |
| W1: indexed `++`/`--` | Still reject for scalar and carrier elements. `Mutation_Preparation` / `Indexing_Preparation` need an agreed stable-place protocol. |
| W2: indexed numeric compounds | Guarded numeric and floating indexed compounds remain explicitly deferred in those owners. Stable local/property/static proofs do not cover indexed targets. |
| W3: other null comparisons | 112 observed rejections stay under `Presence_Operators` and the presence contract. Separate review must distinguish intended restrictions from future support. |
| W4: wider payload/domain grid | Fixed-width, nested, object/container, boxed/dynamic/table and selector domains retain their own contracts and existing issues. Explicit error-sentinel syntax also remains separate. |

Existing indexed integer/string compound controls pass. All four string-carrier
indexed `.=` probes also pass through the existing index protocol, with independent
state/byte checks; no indexed implementation was added. These bounded checks do not
certify every nested path, alias pattern or container family.

The inventory retains all 10,767 wider/excluded grid cells. They are not all newly
proved rejections or permanent language incompatibilities. V-L07 closes only its
agreed canonical scope; the wider follow-ups above remain visible. Overall original
issue closure is **20/64 (31.25%)**, with this scope qualification. Historical audit
findings and previous dated progress reports are preserved.
