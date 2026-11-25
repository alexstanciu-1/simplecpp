# E4I mixed canonical numeric strict identity
Doc Status: supporting

Completed 2026-10-19 on `alex-ai@dev002.travelfuse.ro`, snapshot
`/home/alex-ai/mixed-identity/20261019-01`.

## Scope and semantics

96 carrier signatures: `===` and `!==` × both canonical int/float orders × 24
ordered carrier/plain arrangements. Four both-plain scalar prerequisites are
counted separately. Nullable, result, result_or_false and result_or_bool participate,
including same-family and cross-family pairs.

The [language amendment](../../../../specs/expression_evaluation.md#mixed-canonical-numeric-strict-identity--2026-10-19)
preserves each numeric kind. Successful int and float payloads are never identical,
even for 1 and 1.0 or at rounding boundaries. No numeric promotion occurs. Carriers
still compare normalized states: empty nullable matches empty nullable; matching
bool sentinels match; Result errors compare structured identity metadata. Null,
bool, error and numeric kinds remain distinct. `!==` negates `===`.

Both operands are eagerly snapshotted left-to-right as complete values. Unequal
or unavailable states do not suppress RHS evaluation; source exceptions do.
RHS mutation cannot alter the saved left state. Comparison performs no write-back.
Ordinary mixed equality, other heterogeneous payloads, fixed-width/nested extensions,
float spaceship and updates remain separate.

## Ownership and implementation

Runtime support was probed first and passed without changes. The focused proof
repeats this gate against the final source snapshot.

`Scalar_Identity` now owns scalar strict-identity preparation and the shared canonical
int/float pair rule. The existing bool/string path moved from `Operator_Preparation`
into this owner. Each operand converts to its own type, preserving its representation;
identity no longer routes scalar operands toward the left operand's type. Same-int
identity retains its existing integer path; scalar float/float admission is unchanged.
`Wrapper_Equality` admits the shared mixed numeric pair only for strict identity,
retaining whole-state identity conversions and no payload extraction facts.
No runtime, emitter or retained-model changes were needed.

The existing cross-family identity proof is generalized to independent left/right
payload types and optional plain operands. Its state oracle does not use the runtime
identity helper to compute expectations. Stale negatives in earlier numeric proofs
were retired only for the newly admitted strict-identity signatures; ordinary
mixed equality and unrelated rejection controls remain.

## Verification

Runtime support, all four scalar prerequisites and all 96 carrier signatures pass
on the first dev002 attempt. Native probes cover all 25 family/plain arrangements
in both numeric directions. Tests verify:

- Exact signature/result types, identity conversion of each original operand type,
  no guarded extraction or promotion, and cleanup of prepared facts.
- Int64 extrema, values around ±2^53 and neighboring representable floats, fractions,
  signed zeros, infinities, subnormal values and distinct NaN bit patterns. Independent
  typed integer/float values stay distinct; storage observations preserve float bits.
- Every present/empty/error/sentinel combination, both bool sentinels and error
  variants differing independently in message, line and file. State comparisons are
  not replaced by a constant payload-kind answer.
- Eager evaluation, once-only counters and traces, distinct left/right source failures,
  local/reference and property RHS replacement, old-left snapshots, retained RHS
  effects on failure and unchanged other operands.
- 100 retained failure/retry/repair/no-op/fresh recovery sequences, one per signature.
- 400 source rejection controls for ordinary mixed equality and other payload domains.

The selected focused proof records **994,152 runtime/source observations**. All
**866 selected runner commands** return expected exits. All **20 focused regression
suites** pass, including the prior cross-family identity proof, scalar bool/string
identity, matching-payload wrapper equality, mixed ordering, numeric XOR, guarded
operators and eager binary snapshots.

Toolchain: PHP 8.5.11 (CLI fallback for CLI/FPM mismatch), Clang 18.1.3 and Python
3.12.3. All compiler/native execution ran on dev002. No runtime changes, full matrix
rerun or native compiler rebuild. No blocker remains within this slice.

## Evidence and progress

[Results/signatures](mixed_identity_results.json), [runner archive](mixed_identity_evidence.json.gz),
[final source manifest](mixed_identity_source_final.json), [baseline](mixed_identity_baseline.json),
[completion audit](mixed_identity_completion_audit.json).
Remote integrity verifies 6,243 source files. Historical evidence is preserved.

Reproduce on dev002:
`python3 compiler/my-try/tests/mixed_identity.py --results FRESH`.
Use `--runtime-only` for the prerequisite gate.

V-L07 now has **27 completed slices: 2,064 binary, 64 unary and 432 compound
signatures — 2,560 total**. Four new scalar prerequisites are counted separately.
The original core remains **2,080/2,080 (100%)**, plus 480 approved O1/C2W/E3I/E4I
signatures. There are **1,392 design/scope candidates** remaining, including 96
ordinary mixed numeric equality signatures. Overall issue closure remains 19/64
(29.7%); V-L07 is still partial.
