# E4 cross-family mixed numeric ordinary equality
Doc Status: supporting

Completed 2026-10-20 on `alex-ai@dev002.travelfuse.ro`, using isolated snapshot
`/home/alex-ai/cross-mixed-equality/20261020-01`.

## Scope and semantics

48 signatures: `==` and `!=` × int/float in both orders × 12 ordered distinct
pairs of nullable, structured Result, false-sentinel and bool-sentinel carriers.
There are no new scalar prerequisites. Cross-family bool equality remains separate.

Both present payloads compare by int-to-float numeric equality, including rounding
near ±2^53 and int64 limits. Signed zeros compare equal and NaNs compare unequal.
The existing E3 state policy is unchanged: matching false sentinels compare equal,
different unavailable states compare unequal, and unavailable/payload comparisons
are unequal even for zero or one. `!=` negates `==`. No truth conversion or
unavailable-payload extraction occurs; stored representations and error metadata
remain intact.

Complete operands are snapshotted eagerly left-to-right. Unavailable states do not
skip RHS evaluation; source exceptions stop later evaluation. RHS replacement cannot
alter the saved left operand, and earlier RHS effects survive a later failure.
Comparison performs no write-back. The [source contract](../../../../specs/expression_evaluation.md)
records these rules independently of the runtime implementation.

## Baseline and bounded repair

The baseline runtime probe failed all 24 cross-family mixed numeric pairs through
ambiguous competing carrier overloads, blocking 48 operator signature targets.
Those diagnostics and original source hashes are preserved separately from passing
acceptance evidence.

The operator generator's cross-family pair predicate now admits canonical numeric
pairs as well as matching strings. Its numeric/string payload rule is shared with
sentinel/plain equality rather than duplicated. Existing generic overload exclusions
therefore select E3's single state-comparison route for the newly admitted pairs.
The state-comparison algorithm is unchanged; present values delegate to the existing
scalar numeric equality. Bool, fixed-width, boxed, nested and other heterogeneous
payload domains remain outside this route.

Runtime configuration and prose describe the extension. The header was regenerated
twice on dev002, with identical output after normalizing only its existing timestamp
line. Generated output was not patched by hand.

`Wrapper_Equality` now admits canonical mixed numeric pairs across supported
carrier/plain families through the existing payload predicate. Obsolete per-family
branches were removed. Preparation still retains complete-state snapshot conversions
and no checked extractions; no emitter or retained-model changes were needed.

## Verification

Run `python3 compiler/my-try/tests/mixed_equality.py --group cross --results FRESH`
on dev002. Add `--runtime-only` for the prerequisite gate. `--probe-runtime`
records baseline failures and is not an acceptance mode.

The mixed-equality harness adds a cross-family group and retains its independent
numeric/state oracle. Cases include integer extrema, ±2^53 rounding boundaries,
fractions, infinities, subnormals, signed zeros and distinct NaN representations.
Every selected payload/state combination is checked without changing original
representations. Source proofs cover bool result types, whole-state preparation,
cleanup, retained recovery, once-only source order and failures. Local/reference
and property replacement observers verify saved-left snapshots, surviving effects,
unchanged operands and structured error metadata.

E3's former mixed-pair negative controls are now positive E4 proofs; all 24
cross-family bool ordinary comparisons remain explicit negatives. Existing equality
and identity regressions retain independent contracts. Historical evidence is not
rewritten to turn prior failures into passes.

No full matrix rerun or native compiler rebuild. All compiler/native execution runs
on dev002 with PHP 8.5.11 (CLI fallback for CLI/FPM version mismatch), Clang 18.1.3
and Python 3.12.3. Report and inventory closeout follow the tested source snapshot.

All **27 focused regression suites** pass on their first attempts, including
E3's 72 matching-payload cross-family signatures, both same-family/plain mixed
numeric groups, sentinel runtime boundaries, existing equality/identity, ordering,
XOR, guarded operators, eager binary snapshots and scalar admission.

The selected proof records **514,116 value/state observations**, **48 retained
recovery sequences**, and **168 negative source checks**, including all 24
cross-family bool ordinary comparisons. All **2,851 selected runner commands**
returned expected exits. Baseline ambiguity failures are archived but excluded
from these passing totals. Repaired runtime and source acceptance passed on their
first attempts; no blocker remains in scope.

Integrity checks cover **6,247 source files**. Generated-header hashes and exact
inventory signatures match the verified results. Report and inventory closeout
introduce no changes to the tested implementation or historical evidence.

## Evidence and progress

[Results and exact signatures](cross_mixed_equality_results.json),
[baseline and acceptance logs](cross_mixed_equality_evidence.json.gz),
[baseline revision](cross_mixed_equality_baseline.json),
[baseline source](cross_mixed_equality_source.json),
[repaired runtime source](cross_mixed_equality_source_runtime.json),
[final tested source](cross_mixed_equality_source_final.json),
[completion audit](cross_mixed_equality_completion_audit.json).

V-L07 reaches **33 completed slices: 2,712 binary, 64 unary and 432 compound
signatures — 3,208 total**. The original core remains 2,080/2,080, with 1,128 approved
signatures beyond that denominator. **744 design/scope candidates** remain.
E4 has no remaining signatures; E3 still has **24 cross-family bool comparisons**.
Overall issue closure remains 19/64 (29.7%); V-L07 remains partial.
