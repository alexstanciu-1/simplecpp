# E3NB matching non-bool cross-family ordinary equality
Doc Status: supporting

Completed 2026-10-20 on `alex-ai@dev002.travelfuse.ro`, using isolated snapshot
`/home/alex-ai/cross-equality/20261020-01`.

## Scope and semantics

72 signatures: `==` and `!=` × matching canonical int, float and string payloads
× 12 ordered distinct pairs of nullable, structured Result, false-sentinel and
bool-sentinel carriers. There are no new scalar prerequisites. Matching-bool and
mixed-numeric cross-family ordinary equality remain separate.

Both present payloads compare by scalar equality. Signed zeros compare equal,
NaNs compare unequal, and strings compare complete bytes including embedded zeros.
False sentinels compare equal across the two sentinel families. Distinct unavailable
states (null, error, false, true) and unavailable/payload pairs are unequal, even
for zero or empty strings. `!=` negates `==`. No truth conversion or unavailable
payload extraction occurs. Error metadata and all stored states remain unchanged.

The [source contract](../../../../specs/expression_evaluation.md) requires eager
left-to-right snapshots of complete operands. An unavailable state does not skip
the RHS; a source exception prevents later evaluation. RHS replacement cannot
alter the saved left operand, and earlier effects survive a later failure.
Comparison performs no write-back.

## Baseline and bounded runtime repair

Before implementation, all 36 cross-family pair probes failed compilation because
left-carrier and right-carrier generic equality overloads competed. These probes
cover 72 blocked operator signatures. All 24 same-family runtime controls passed.
The original failures and baseline source hashes are preserved separately from
acceptance runs.

The operator generator now owns an explicit equality carrier family/state model.
A constrained pair domain admits distinct families with matching canonical int,
float or string payloads. One public equality path compares state tags before
reading present payloads; inequality negates it. Generic carrier/other overloads
exclude exactly this domain, eliminating ambiguity without a per-pair overload
list or changes to same-family/plain dispatch. The domain excludes bool, mixed
numeric, fixed-width, boxed and nested payloads.

Configuration and runtime prose record the approved contract. The header was
regenerated on dev002 twice; the output matches after normalizing only the existing
timestamp line. Generated code was not hand-patched.

`Wrapper_Equality` owns matching non-bool pair admission in preparation. It retains
whole-state identity conversions and zero checked extractions. No new retained
model or backend path is needed. Strict identity stays in its existing owner.

## Verification

Run `python3 compiler/my-try/tests/cross_equality.py --results FRESH` on dev002.
`--runtime-only` selects the prerequisite gate; `--probe-runtime` records baseline
failures and is not an acceptance mode.

The runtime gate checks all 72 targets and 24 same-family controls. The source
proof checks the 72 exact signatures and bool result types, preparation cleanup,
independent state/value expectations, eager source order, once-only effects,
source failures and retained recovery. Local/reference and property replacement
cases verify saved-left snapshots, preserved float bits and complete string bytes,
error metadata and surviving RHS effects.

The shared source fixture has a separate ordinary-cross mode; its independent
oracle compares storage-state tags without strict-identity normalization. Existing
state constructors and storage observers remain shared. Explicit source negatives
cover all 24 bool and 48 mixed-numeric cross-family comparisons, in addition to
other excluded payloads. Existing identity and equality fixtures retain their
own contracts, with newly valid non-bool cross-family rejections removed through
the shared test-domain predicate.

No full matrix rerun or native compiler rebuild. All compiler/native execution
runs on dev002 with PHP 8.5.11 (CLI fallback for CLI/FPM version mismatch), Clang
18.1.3 and Python 3.12.3. Report closeout follows the tested source snapshot.

All **26 focused regression suites** pass on their first attempts. These cover
same-family/plain equality, matching and heterogeneous strict identity, both mixed
numeric ordinary-equality groups, sentinel runtime boundaries, ordering, numeric
XOR, guarded operators, eager binary snapshots and scalar admission.

The selected proof records **775,144 value/state observations**, **72 retained
recovery sequences**, and **360 negative source checks**. All **2,730 selected
runner commands** returned expected exits. Baseline ambiguity failures are archived
but excluded from passing totals. Both repaired runtime and source acceptance
passed on their first attempts; no blocker remains in the approved slice.

Integrity checks cover **6,247 source files**. Generated-header hashes and exact
inventory signatures match the verified results. The only subsequent edits are
report and inventory closeout; historical evidence remains unchanged.

## Evidence and progress

[Results and exact signatures](cross_equality_results.json),
[baseline and acceptance logs](cross_equality_evidence.json.gz),
[baseline revision](cross_equality_baseline.json),
[baseline source](cross_equality_source.json),
[repaired runtime source](cross_equality_source_runtime.json),
[final tested source](cross_equality_source_final.json),
[completion audit](cross_equality_completion_audit.json).

V-L07 reaches **32 completed slices: 2,664 binary, 64 unary and 432 compound
signatures — 3,160 total**. The original core remains 2,080/2,080, with 1,080 approved
signatures beyond that denominator. **792 design/scope candidates** remain,
including **24 E3 bool** and **48 E4 mixed-numeric** cross-family ordinary comparisons.
Overall issue closure remains 19/64 (29.7%); V-L07 remains partial.
